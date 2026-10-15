<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require 'custom/HrDocumentSupport.php';global $current_user;$current_user=BeanFactory::getBean('Users','1');
$key=null;foreach(hrTemplateRegistry() as $id=>$item)if($item['title']==='Демо: отпуск учебного отдела')$key=$id;
$key??=hrCloneTemplate('leave','Демо: отпуск учебного отдела');
if(hrTemplateKind($key)!=='leave')throw new RuntimeException('Template kind missing');
$person=BeanFactory::getBean('HR_People','22222222-2222-4222-8222-222222222222');$id=hrGenerate($person,$key,['type'=>'ежегодного отпуска','start'=>'2026-11-01','end'=>'2026-11-14']);
$zip=new ZipArchive();if($zip->open(hrDir().'/'.$id.'.docx')!==true)throw new RuntimeException('Invalid DOCX');$text=html_entity_decode(strip_tags($zip->getFromName('word/document.xml')),ENT_QUOTES,'UTF-8');$zip->close();if(!str_contains($text,$person->name)||str_contains($text,'{{'))throw new RuntimeException('Template copy was not filled');
$current_user=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>'hr_editor']);$blocked=false;try{hrCloneTemplate('leave','Forbidden copy');}catch(RuntimeException $e){$blocked=true;}if(!$blocked)throw new RuntimeException('Non-admin created template');
echo "PASS custom template generation and admin-only creation: $key ($id)\n";
