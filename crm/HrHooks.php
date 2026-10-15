<?php
if (!defined('sugarEntry')) die('Invalid entry');
require_once '/opt/hr/HrAuditSupport.php';
class HrHooks {
 public function afterRetrieve($bean,$event,$args) {
  global $db;
  foreach(['person_id'=>'person_name','manager_id'=>'manager_name'] as $idField=>$nameField){
   if(!empty($bean->$idField))$bean->$nameField=$db->getOne("SELECT name FROM hr_people WHERE deleted=0 AND id=".$db->quoted($bean->$idField))?:'';
  }
  if ($bean->module_dir==='HR_People') $bean->documents_url=getenv('CRM_URL').'/legacy/index.php?entryPoint=HrDocuments&person='.$bean->id;
 }
 private function snapshot($bean) {
  global $db;
  if(empty($bean->id))return null;
  $result=$db->query('SELECT * FROM '.$bean->table_name.' WHERE id='.$db->quoted($bean->id));
  $row=$db->fetchByAssoc($result,false);if(!$row)return null;
  unset($row['date_modified'],$row['modified_user_id']);return $row;
 }
 public function beforeSave($bean, $event, $args) {
  $bean->hr_before=$this->snapshot($bean);
  if ($bean->module_dir==='HR_People' && !empty($bean->manager_id)) {
   $seen=[$bean->id]; $id=$bean->manager_id;
   while ($id) {
    if (in_array($id,$seen,true)) throw new RuntimeException('Подчинённость не должна содержать цикл.');
    $seen[]=$id; $manager=BeanFactory::getBean('HR_People',$id);
    if (empty($manager->id) || $manager->deleted) throw new RuntimeException('Руководитель не найден.');
    $id=$manager->manager_id;
   }
  }
  if (!empty($bean->start_date) && !empty($bean->end_date) && $bean->end_date<$bean->start_date) throw new RuntimeException('Окончание раньше начала.');
 }
 public function afterSave($bean,$event,$args) {
  $after=$this->snapshot($bean)??[];$before=$bean->hr_before??null;$diff=[];
  foreach($after as $key=>$value)if((string)($before[$key]??'')!==(string)($value??''))$diff[$key]=['before'=>$before[$key]??null,'after'=>$value];
  if($before===null)$this->record($bean,'create',$after);
  elseif($diff)$this->record($bean,'update',$diff);
 }
 public function relationChanged($bean,$event,$args){
  $payload=[];foreach(['id','module','related_id','related_module','relationship','link'] as $key)if(isset($args[$key])&&is_scalar($args[$key]))$payload[$key]=$args[$key];
  $related=$args['related_bean']??null;if(is_object($related))$payload['related_name']=$related->name??$related->user_name??$related->id;
  $this->record($bean,$event,$payload);
 }
 public function beforeDelete($bean,$event,$args) {
  $bean->hr_delete_snapshot=$this->snapshot($bean)??[];
  $bean->hr_children=[];
  if($bean->module_dir==='HR_People') foreach(['HR_Awards','HR_Service','HR_Education','HR_Relatives'] as $module) {
   $child=BeanFactory::newBean($module);
   foreach($child->get_full_list('name',$child->table_name.'.person_id='.$GLOBALS['db']->quoted($bean->id))??[] as $row) $bean->hr_children[]=$row;
  }
 }
 public function afterDelete($bean,$event,$args) {
  $this->record($bean,'delete',$bean->hr_delete_snapshot??$this->snapshot($bean)??[]);
  foreach($bean->hr_children??[] as $row) $row->mark_deleted($row->id);
 }
 private function record($bean,$operation,$payload){hrAuditWrite($bean,$operation,$payload);}
}
