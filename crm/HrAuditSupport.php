<?php
if(!defined('sugarEntry')||!sugarEntry)die('Invalid entry');
function hrAuditPayload($description){
 $payload=json_decode((string)$description,true);
 // SuiteCRM HTML-encodes text fields when retrieving beans.
 if(!is_array($payload))$payload=json_decode(html_entity_decode((string)$description,ENT_QUOTES,'UTF-8'),true);
 return is_array($payload)?$payload:[];
}
function hrAuditActor(){
 global $current_user;
 return ['id'=>$current_user->id??'','login'=>$current_user->user_name??'system','name'=>trim(($current_user->first_name??'').' '.($current_user->last_name??''))];
}
function hrAuditWrite($bean,$operation,$payload){
 $actor=hrAuditActor();$audit=BeanFactory::newBean('HR_Audit');
 $audit->name=$operation.' '.html_entity_decode((string)($bean->name??$bean->id),ENT_QUOTES,'UTF-8');
 $audit->entity_module=$bean->module_dir;$audit->entity_id=$bean->id;
 $audit->actor_id=$actor['id'];$audit->actor_name=$actor['login'];$audit->operation=$operation;
 $payload['_context']=['actor_login'=>$actor['login'],'actor_name'=>$actor['name'],'occurred_at'=>(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'),'record_name'=>html_entity_decode((string)($bean->name??''),ENT_QUOTES,'UTF-8')];
 foreach(['person_id','manager_id','assigned_user_id','created_by','modified_user_id','related_id'] as $field){
  if(!array_key_exists($field,$payload))continue;$value=$payload[$field];$values=is_array($value)?[$value['before']??null,$value['after']??null]:[$value];
  foreach($values as $id)if(is_scalar($id)&&(string)$id!=='')$payload['_context']['references'][$field][(string)$id]=hrAuditDisplayValue($field,$id);
 }
 $audit->description=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);$audit->save();
}
function hrAuditDisplayValue($key,$value){
 if($value===null||$value==='')return '—';
 if(is_array($value))return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
 if(in_array($key,['person_id','manager_id','related_id'],true)){
  global $db;$name=$db->getOne('SELECT name FROM hr_people WHERE id='.$db->quoted((string)$value));
  if($name)return html_entity_decode($name,ENT_QUOTES,'UTF-8');
 }
 if(in_array($key,['assigned_user_id','created_by','modified_user_id','actor_id'],true)){
  $user=BeanFactory::getBean('Users',(string)$value);if($user&&!empty($user->id))return $user->user_name;
 }
 return (string)$value;
}
