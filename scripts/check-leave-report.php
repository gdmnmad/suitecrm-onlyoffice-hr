<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require 'custom/HrDocumentSupport.php';global $current_user;$current_user=BeanFactory::getBean('Users','1');
$person=BeanFactory::getBean('HR_People','22222222-2222-4222-8222-222222222222');
$leave=['type'=>'отпуска по личным обстоятельствам','start'=>'2026-07-20','end'=>'2026-07-29','recipient'=>'Начальнику курса','applicant_dative'=>'капитану Орлову Михаилу Андреевичу','reason'=>'необходимостью присутствия при заключении сделки по недвижимому имуществу','location'=>'г. Санкт-Петербург','transport'=>'железнодорожным','following_start'=>'2026-07-30','following_end'=>'2026-08-28','return_date'=>'2026-08-28','return_time'=>'16:00','return_place'=>'воинскую часть','report_date'=>'2026-05-11','endorsement_recipient'=>'Начальнику факультета','final_recipient'=>'Начальнику академии','final_author'=>'Начальник факультета __________________'];
$id=hrGenerate($person,'leave',$leave);$zip=new ZipArchive();$zip->open(hrDir().'/'.$id.'.docx');$xml=$zip->getFromName('word/document.xml');$zip->close();$text=html_entity_decode(strip_tags($xml),ENT_QUOTES,'UTF-8');
foreach(['Орлову Михаилу Андреевичу','10 суток','20 июля 2026 года','28 августа 2026 года','16:00','Санкт-Петербург','железнодорожным','Начальнику академии'] as $expected)if(!str_contains($text,$expected))throw new RuntimeException('Missing '.$expected);
if(str_contains($text,'{{'))throw new RuntimeException('Unfilled controls');
if(!str_starts_with(file_get_contents(hrDir().'/'.$id.'.pdf'),'%PDF-'))throw new RuntimeException('Invalid PDF');
file_put_contents(hrDir().'/leave-report-test-id.txt',$id);echo "PASS leave report dates, inclusive duration, custom fields and endorsements: $id\n";
