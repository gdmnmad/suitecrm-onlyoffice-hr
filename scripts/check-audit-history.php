<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require '/opt/hr/HrAuditSupport.php';global $current_user,$db;
function auditCheck($value,$message){if(!$value)throw new RuntimeException($message);echo "PASS $message\n";}
function auditEvents($id){global $db;$rows=[];$result=$db->query('SELECT * FROM hr_audit WHERE entity_id='.$db->quoted($id).' AND deleted=0 ORDER BY date_entered,id');while($row=$db->fetchByAssoc($result,false))$rows[]=$row;usort($rows,fn($a,$b)=>strcmp(hrAuditPayload($a['description'])['_context']['occurred_at']??'',hrAuditPayload($b['description'])['_context']['occurred_at']??''));return $rows;}
$current_user=BeanFactory::getBean('Users','1');$testIds=[];
try{
 $p=BeanFactory::newBean('HR_People');$p->name='AUDIT QA <Иванов> & "Иван"';$p->save();$testIds[]=['HR_People',$p->id];
 $p->department='Первый отдел';$p->save();$p->department='Второй отдел';$p->save();$p->manager_id='11111111-1111-4111-8111-111111111111';$p->save();
 $p->manager_id='';$p->save();$p->description='Строка 1'."\n".'Строка 2';$p->save();$p->description='';$p->save();
 $events=auditEvents($p->id);$count=count($events);$p->save();auditCheck(count(auditEvents($p->id))===$count,'No event for unchanged save');
 $updates=array_values(array_filter($events,fn($e)=>$e['operation']==='update'));$changes=array_map(fn($e)=>hrAuditPayload($e['description']),$updates);
 auditCheck(count($updates)===6,'Repeated edits on same bean are tracked independently');
 auditCheck($changes[1]['department']['before']==='Первый отдел'&&$changes[1]['department']['after']==='Второй отдел','Correct previous persisted value');
 auditCheck($changes[3]['manager_id']['before']==='11111111-1111-4111-8111-111111111111'&&$changes[3]['manager_id']['after']==='','Clearing manager is visible');
 auditCheck(hrAuditPayload(htmlspecialchars($events[0]['description'],ENT_QUOTES,'UTF-8'))['name']==='AUDIT QA <Иванов> & "Иван"','Old HTML-encoded audit JSON remains readable');
 auditCheck(hrAuditDisplayValue('manager_id','11111111-1111-4111-8111-111111111111')==='Соколов Алексей Петрович','Related ID displays person name');
 $editor=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>'hr_editor']);$current_user=$editor;
 foreach(['HR_Awards','HR_Service','HR_Education','HR_Relatives'] as $module){$child=BeanFactory::newBean($module);$child->name='AUDIT QA '.$module;$child->person_id=$p->id;$child->save();$testIds[]=[$module,$child->id];$child->name.=' edited';$child->save();$child->person_id='22222222-2222-4222-8222-222222222222';$child->save();$child->mark_deleted($child->id);$childEvents=auditEvents($child->id);$ops=array_column($childEvents,'operation');auditCheck(in_array('create',$ops)&&in_array('update',$ops)&&in_array('delete',$ops),$module.' create/update/reassign/delete');foreach($childEvents as $event)auditCheck($event['actor_name']==='hr_editor'&&$event['actor_id']===$editor->id,$module.' author retained');}
 $p->mark_deleted($p->id);$events=auditEvents($p->id);$deletes=array_values(array_filter($events,fn($e)=>$e['operation']==='delete'));auditCheck(count($deletes)===1,'Deletion recorded');auditCheck(hrAuditPayload($deletes[0]['description'])['name']==='AUDIT QA <Иванов> & "Иван"','Deleted record snapshot retained');
 $current_user=BeanFactory::getBean('Users','1');$_SERVER['REQUEST_METHOD']='GET';$_GET=['record'=>$p->id];ob_start();require 'custom/HrAudit.php';$html=ob_get_clean();auditCheck(str_contains($html,'Автор: hr_editor')&&str_contains($html,'Второй отдел')&&str_contains($html,'Было'),'History renders author and before/after without expanding JSON');auditCheck(!str_contains($html,'AUDIT QA <Иванов>'),'History escapes record names');
}finally{$current_user=BeanFactory::getBean('Users','1');foreach($testIds as [$module,$id]){$bean=BeanFactory::getBean($module,$id);if($bean&&!empty($bean->id)&&!$bean->deleted)$bean->mark_deleted($id);}}
