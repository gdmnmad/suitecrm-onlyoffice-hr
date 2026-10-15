<?php
if(!defined('sugarEntry')||!sugarEntry)die('Invalid entry');
function hrDocumentLabels(){
 return ['type'=>'Вид отпуска','start'=>'Начало отпуска','end'=>'Окончание отпуска','recipient'=>'Адресат','applicant_dative'=>'Звание и ФИО','reason'=>'Причина отпуска','location'=>'Место проведения отпуска','transport'=>'Транспорт','following_start'=>'Начало следующего отпуска','following_end'=>'Окончание следующего отпуска','return_date'=>'Дата возвращения','return_time'=>'Время возвращения','return_place'=>'Место возвращения','report_date'=>'Дата рапорта','endorsement_recipient'=>'Адресат первого ходатайства','endorsement_author'=>'Первый согласующий','final_recipient'=>'Адресат второго ходатайства','final_author'=>'Второй согласующий'];
}
function hrDocumentDefaults(){
$labels=hrDocumentLabels();$defaults=['template'=>'leave','details_open'=>''];foreach($labels as $key=>$label)$defaults['leave_'.$key]='';
$defaults['leave_type']='ежегодного отпуска';$defaults['leave_report_date']=(new DateTimeImmutable('now',new DateTimeZone(getenv('HR_TIMEZONE')?:'Europe/Moscow')))->format('Y-m-d');
 return $defaults;
}
function hrValidateLeave($leave,$template){
 $labels=hrDocumentLabels();$errors=[];
  foreach($labels as $key=>$label) {
   $leave[$key]=trim((string)($leave[$key]??''));$limit=$key==='type'?120:2000;
   if(mb_strlen($leave[$key])>$limit)$errors['leave_'.$key]=$label.': не более '.$limit.' символов.';
  }
  if(hrTemplateKind($template)==='leave') {
   if($leave['type']==='')$errors['leave_type']='Укажите вид отпуска.';
   foreach(['start','end','following_start','following_end','return_date','report_date'] as $key) {
    $v=$leave[$key];$required=in_array($key,['start','end']);
    if($v===''&&!$required)continue;
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
    if(!$date||$date->format('Y-m-d')!==$v)$errors['leave_'.$key]=$labels[$key].': '.($v===''?'укажите дату.':'укажите существующую дату.');
   }
   if(!isset($errors['leave_start'])&&!isset($errors['leave_end'])&&$leave['start']!==''&&$leave['end']!==''&&$leave['end']<$leave['start'])$errors['leave_end']='Окончание отпуска должно быть не раньше начала.';
   if(($leave['following_start']==='')!==($leave['following_end']==='')){$missing=$leave['following_start']===''?'following_start':'following_end';$errors['leave_'.$missing]=$labels[$missing].': укажите дату или очистите обе даты следующего отпуска.';}
   if($leave['following_start']!==''&&$leave['following_end']!==''&&$leave['following_end']<$leave['following_start'])$errors['leave_following_end']='Окончание следующего отпуска должно быть не раньше его начала.';
   if($leave['following_start']!==''&&$leave['end']!==''&&$leave['following_start']<=$leave['end'])$errors['leave_following_start']='Следующий отпуск должен начинаться после окончания основного.';
   if($leave['return_time']!==''&&!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$leave['return_time']))$errors['leave_return_time']='Время возвращения: укажите время от 00:00 до 23:59.';
  }
 return $errors;
}
