<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require 'custom/HrDocumentSupport.php';global $current_user;$current_user=BeanFactory::getBean('Users','1');
$results=[];
foreach(json_decode(file_get_contents('/opt/demo-data/people.json'),true) as $row) {
 $person=BeanFactory::getBean('HR_People',$row['id']);
 foreach(hrTemplates() as $template=>$title) {
  $id=hrGenerate($person,$template,['type'=>'ежегодный отпуск','start'=>'2026-11-01','end'=>'2026-11-14']);
  $zip=new ZipArchive();if($zip->open(hrDir().'/'.$id.'.docx')!==true)throw new RuntimeException('Invalid DOCX');
  $xml=$zip->getFromName('word/document.xml');$zip->close();$text=html_entity_decode(strip_tags($xml),ENT_QUOTES,'UTF-8');
  if(!str_contains($text,$person->name))throw new RuntimeException('Name missing in '.$template);
  if(str_contains($text,'{{'))throw new RuntimeException('Unfilled placeholders in '.$template);
  if($template==='awards' && $row['id']==='33333333-3333-4333-8333-333333333333' && !str_contains($text,'Наград нет'))throw new RuntimeException('Empty awards not handled');
  if($template==='service' && !str_contains($text,'по настоящее время'))throw new RuntimeException('Current service not handled');
  if(!str_starts_with(file_get_contents(hrDir().'/'.$id.'.pdf'),'%PDF-'))throw new RuntimeException('Invalid PDF');
  $results[]=['id'=>$id,'person'=>$person->id,'template'=>$template];echo "PASS $template for $person->name ($id)\n";
 }
}
file_put_contents(hrDir().'/test-results.json',json_encode($results,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
