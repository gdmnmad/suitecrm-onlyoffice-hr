<?php
if(!defined('sugarEntry')||!sugarEntry)die('Invalid entry');
require_once 'custom/HrDocumentSupport.php';require_once '/opt/hr/HrDocumentForm.php';
function hrBatchRead($id){
 global $current_user;
 if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))throw new RuntimeException('Пакет не найден.');
 $path=hrDir().'/batch-'.$id.'.json';if(!is_file($path))throw new RuntimeException('Пакет не найден.');$batch=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
 if(($batch['author']??'')!==$current_user->id&&!$current_user->isAdmin())throw new RuntimeException('Нет доступа к этому пакету.');
 foreach($batch['people'] as $personId){$person=BeanFactory::getBean('HR_People',$personId);if(!$person->id||!$person->ACLAccess('view'))throw new RuntimeException('Нет доступа к одному из сотрудников пакета.');}
 return $batch;
}
function hrBatchSave($id,$batch){$path=hrDir().'/batch-'.$id.'.json';file_put_contents($path.'.tmp',json_encode($batch,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),LOCK_EX);rename($path.'.tmp',$path);}
function hrBatchCreate($ids,$template,$leave,$id=null){
 global $current_user;
 if(!is_array($ids))throw new RuntimeException('Выберите сотрудников.');$ids=array_values(array_unique($ids));
 if(count($ids)<1||count($ids)>20)throw new RuntimeException('Выберите от 1 до 20 сотрудников за один раз.');
 if(!isset(hrTemplates()[$template]))throw new RuntimeException('Выберите шаблон из списка.');
 // A personal override must never replace every recipient with the same name.
 $leave['applicant_dative']='';$errors=hrValidateLeave($leave,$template);if($errors)throw new RuntimeException(implode(' ',$errors));
 $people=[];foreach($ids as $personId){if(!is_string($personId)||!preg_match('/^[a-f0-9-]{36}$/Di',$personId))throw new RuntimeException('Некорректный сотрудник.');$person=BeanFactory::getBean('HR_People',$personId);if(!$person->id||!$person->ACLAccess('view')||!$person->ACLAccess('edit'))throw new RuntimeException('Нет прав на генерацию для одного из выбранных сотрудников.');$people[]=$person;}
 $id??=bin2hex(random_bytes(16));if(!preg_match('/^[a-f0-9]{32}$/D',$id))throw new RuntimeException('Некорректный пакет.');
 $batch=['id'=>$id,'author'=>$current_user->id,'author_name'=>$current_user->user_name,'created_at'=>date(DATE_ATOM),'template'=>$template,'title'=>hrTemplates()[$template],'people'=>$ids,'results'=>[],'status'=>'running'];hrBatchSave($id,$batch);set_time_limit(300);
 foreach($people as $person){try{$document=hrGenerate($person,$template,$leave);$batch['results'][]=['person_id'=>$person->id,'person_name'=>html_entity_decode($person->name,ENT_QUOTES,'UTF-8'),'document'=>$document];}catch(Throwable $e){$batch['results'][]=['person_id'=>$person->id,'person_name'=>html_entity_decode($person->name,ENT_QUOTES,'UTF-8'),'error'=>$e->getMessage()];}hrBatchSave($id,$batch);}
 $batch['status']='complete';hrBatchSave($id,$batch);return $id;
}
function hrBatchArchive($batch){
 $path=tempnam(sys_get_temp_dir(),'hr-batch-');$zip=new ZipArchive();
 try{
  if($zip->open($path,ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Не удалось создать архив.');$count=0;
  foreach($batch['results'] as $index=>$row){if(empty($row['document']))continue;$id=$row['document'];if(!preg_match('/^[a-f0-9]{32}$/D',$id))continue;
   $metaPath=hrDir().'/'.$id.'.json';$meta=is_file($metaPath)?json_decode(file_get_contents($metaPath),true):[];if(($meta['person_id']??null)!==$row['person_id'])throw new RuntimeException('Документ не принадлежит сотруднику.');
   $name=trim(preg_replace('/[^\p{L}\p{N} ._-]/u','_',($index+1).'-'.$row['person_name'].'-'.$batch['title']));$name=mb_substr($name,0,150);
   foreach(['docx','pdf'] as $ext){$file=hrDir().'/'.$id.'.'.$ext;if(!is_file($file))throw new RuntimeException('Файл документа не найден.');$zip->addFile($file,$name.'.'.$ext);$count++;}
  }
  if(!$count)throw new RuntimeException('В пакете пока нет готовых документов.');$zip->close();return $path;
 }catch(Throwable $e){$zip->close();@unlink($path);throw $e;}
}
