<?php
require_once 'custom/HrDocumentSupport.php';require_once '/opt/hr/HrUi.php';require_once '/opt/hr/HrDocumentForm.php';
global $current_user,$hrDocumentForm,$hrDocumentFieldErrors;
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$personId=$_GET['person']??$_POST['person']??'';$person=BeanFactory::getBean('HR_People',$personId);
if(empty($person->id)||!$person->ACLAccess('view')){http_response_code(403);die('Нет доступа к карточке');}
$_SESSION['hr_csrf']??=bin2hex(random_bytes(24));$error='';$hrDocumentFieldErrors=[];$documentId=$_GET['document']??'';
$labels=hrDocumentLabels();$defaults=hrDocumentDefaults();
$draftKey=$current_user->id.':'.$person->id;
$hrDocumentForm=array_replace($defaults,$_SESSION['hr_document_drafts'][$draftKey]??[]);
function hrFormInput($key,$type='text',$max=2000) {
 global $hrDocumentForm,$hrDocumentFieldErrors;
 $id='field-'.$key;$message=$hrDocumentFieldErrors[$key]??'';
 return '<input id="'.h($id).'" name="'.h($key).'" type="'.h($type).'" value="'.h($hrDocumentForm[$key]??'').'" maxlength="'.$max.'"'.($message?' aria-invalid="true" aria-describedby="error-'.h($key).'"':'').'>'.($message?'<span class="field-error" id="error-'.h($key).'">'.h($message).'</span>':'');
}
if($_SERVER['REQUEST_METHOD']==='POST') {
 foreach($defaults as $key=>$value)if(isset($_POST[$key])&&is_string($_POST[$key]))$hrDocumentForm[$key]=$_POST[$key];
 try {
  if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['hr_csrf'],$_POST['csrf']))throw new RuntimeException('Сессия формы устарела. Обновите страницу и повторите отправку.');
  if(!$person->ACLAccess('edit'))throw new RuntimeException('У вас нет прав на генерацию документов.');
  $_SESSION['hr_document_drafts'][$draftKey]=$hrDocumentForm;
  $template=$hrDocumentForm['template'];if(!array_key_exists($template,hrTemplates()))throw new RuntimeException('Выбран неизвестный шаблон. Выберите шаблон из списка.');
  $leave=[];foreach($labels as $key=>$label)$leave[$key]=trim($hrDocumentForm['leave_'.$key]);
  $hrDocumentFieldErrors=hrValidateLeave($leave,$template);
  if($hrDocumentFieldErrors){$error='Исправьте указанные поля. Все введённые данные сохранены.';}
  else {
   $documentId=hrGenerate($person,$template,$leave);
   header('Location: '.getenv('CRM_URL').'/legacy/index.php?entryPoint=HrDocuments&person='.rawurlencode($person->id).'&document='.$documentId.'&generated=1',true,303);exit;
  }
 }catch(Throwable $e){$error=$e->getMessage();$documentId='';}
}
$config=null;
$editTemplate=$_GET['edit_template']??'';
if($editTemplate && $current_user->isAdmin() && array_key_exists($editTemplate,hrTemplates())) {
 try {
  hrInitTemplates();$file='template-'.$editTemplate.'.docx';
  $config=['documentType'=>'word','height'=>'800px','width'=>'100%','document'=>['fileType'=>'docx','key'=>substr(hash_file('sha256',hrDir().'/'.$file),0,40),'title'=>'Шаблон — '.hrTemplates()[$editTemplate].'.docx','url'=>hrResourceUrl($file),'permissions'=>['edit'=>true]],'editorConfig'=>['customization'=>['forcesave'=>true],'lang'=>'ru','mode'=>'edit','callbackUrl'=>hrResourceUrl($file,'callback'),'user'=>['id'=>$current_user->id,'name'=>$current_user->user_name]]];$config['token']=hrJwt($config);
 } catch(Throwable $e) {$error=$e->getMessage();}
}

if($documentId && preg_match('/^[a-f0-9]{32}$/D',$documentId)) {
 $metaPath=hrDir().'/'.$documentId.'.json';if(!is_file($metaPath)){http_response_code(404);exit;}$meta=json_decode(file_get_contents($metaPath),true);
 if($meta['person_id']!==$person->id){http_response_code(403);exit;}
 $config=['documentType'=>'word','height'=>'800px','width'=>'100%','document'=>['fileType'=>'docx','key'=>$documentId.'-'.($meta['key_version']??0),'title'=>hrTemplates()[$meta['template']].'.docx','url'=>hrResourceUrl($documentId.'.docx'),'permissions'=>['edit'=>$person->ACLAccess('edit')]],'editorConfig'=>['customization'=>['forcesave'=>true],'lang'=>'ru','mode'=>$person->ACLAccess('edit')?'edit':'view','callbackUrl'=>hrResourceUrl($documentId.'.docx','callback'),'user'=>['id'=>$current_user->id,'name'=>$current_user->user_name]]];$config['token']=hrJwt($config);
}
if($config){unset($config['token']);$config['editorConfig']['customization']['uiTheme']=($_COOKIE['hr_theme']??'light')==='dark'?'theme-dark':'theme-light';$config['token']=hrJwt($config);}
?><!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Документы — <?=h($person->name)?></title><?php hrUiStyle();?><body class="hr-page"><main class="hr-shell">
<?php hrUiNav('documents');?><p class="hr-step">Сотрудник → шаблон → параметры → документ</p><h1><?=h($person->name)?></h1><p class="hr-muted">Генерация документов по кадровой карточке. <a href="<?=h(getenv('CRM_URL'))?>/#/HR_People/record/<?=h($person->id)?>">Открыть карточку</a> · <a href="<?=h(hrUiGuide('documents'))?>">Как сформировать документ</a></p>
<?php if(!$person->ACLAccess('edit')):?><div class="hr-panel">Режим просмотра. Можно открыть и скачать готовые документы; генерация доступна кадровику.</div><?php endif?>
<?php if($error):?><div class="error" role="alert"><?=h($error)?><?php if($hrDocumentFieldErrors):?><ul><?php foreach($hrDocumentFieldErrors as $key=>$message):?><li><a href="#field-<?=h($key)?>"><?=h($message)?></a></li><?php endforeach?></ul><?php endif?></div><?php endif?>
<?php if(isset($_GET['generated'])&&$documentId):?><div class="success" role="status">Документ сформирован. Поля сохранены — измените параметры или сформируйте ещё один экземпляр.</div><?php endif?>
<form class="hr-panel" method="post" action="<?=h(getenv('CRM_URL'))?>/legacy/index.php?entryPoint=HrDocuments&amp;person=<?=h($person->id)?>" id="generation-form"><input type="hidden" name="csrf" value="<?=h($_SESSION['hr_csrf'])?>"><input type="hidden" name="person" value="<?=h($person->id)?>">
<h2>Параметры документа</h2><label>Шаблон <select name="template" id="template-select"><?php foreach(hrTemplates() as $key=>$title):?><option value="<?=h($key)?>" data-kind="<?=h(hrTemplateKind($key))?>" <?=$hrDocumentForm['template']===$key?'selected':''?>><?=h($title)?></option><?php endforeach?></select></label>
<p class="hr-hint">ФИО и связанные записи берутся из CRM. Каждый запуск создаёт отдельный документ; предыдущие остаются в истории.</p>
<div id="leave-fields"><fieldset><legend>Сроки отпуска</legend><div class="hr-form-grid"><label>Вид отпуска <?=hrFormInput('leave_type','text',120)?><small class="hr-hint">Например, «ежегодного отпуска».</small></label><label>Дата рапорта <?=hrFormInput('leave_report_date','date')?></label><label>Начало отпуска <?=hrFormInput('leave_start','date')?></label><label>Окончание отпуска <?=hrFormInput('leave_end','date')?></label></div><p class="hr-hint">Обе даты обязательны. Продолжительность считается включительно.</p></fieldset>
<input type="hidden" name="details_open" id="details-open" value="<?=h($hrDocumentForm['details_open'])?>"><details <?=($hrDocumentForm['details_open']||$hrDocumentFieldErrors)?'open':''?> ontoggle="document.getElementById('details-open').value=this.open?'1':''"><summary>Дополнительные поля рапорта по образцу</summary><p class="hr-hint">Заполните нужные реквизиты. Пустые необязательные поля останутся линиями для ручного заполнения.</p>
<fieldset><legend>Адресат и основание</legend><div class="hr-form-grid"><?php foreach(['recipient'=>'Адресат в дательном падеже','applicant_dative'=>'Звание и ФИО в дательном падеже','reason'=>'Причина отпуска'] as $key=>$label):?><label><?=h($label)?> <?=hrFormInput('leave_'.$key)?></label><?php endforeach?></div><p class="hr-hint">Пример: «начальнику курса», «капитану Петрову Дмитрию Сергеевичу», «семейными обстоятельствами».</p></fieldset>
<fieldset><legend>Место, транспорт и возвращение</legend><div class="hr-form-grid"><?php foreach(['location'=>'Место проведения отпуска','transport'=>'Транспорт','return_place'=>'Место возвращения на службу'] as $key=>$label):?><label><?=h($label)?> <?=hrFormInput('leave_'.$key)?></label><?php endforeach?><label>Дата возвращения <?=hrFormInput('leave_return_date','date')?></label><label>Время возвращения <?=hrFormInput('leave_return_time','time')?></label></div><p class="hr-hint">Пример: г. Санкт-Петербург; железнодорожным транспортом; 18:00. Если дата возвращения не задана, используется окончание следующего или основного отпуска.</p></fieldset>
<fieldset><legend>Следующий отпуск — при наличии</legend><div class="hr-form-grid"><label>Следующий отпуск: начало <?=hrFormInput('leave_following_start','date')?></label><label>Следующий отпуск: окончание <?=hrFormInput('leave_following_end','date')?></label></div><p class="hr-hint">Заполните обе даты или оставьте обе пустыми.</p></fieldset>
<fieldset><legend>Ходатайства</legend><div class="hr-form-grid"><?php foreach(['endorsement_recipient'=>'Адресат первого ходатайства','endorsement_author'=>'Должность, звание и ФИО первого согласующего','final_recipient'=>'Адресат второго ходатайства','final_author'=>'Должность, звание и ФИО второго согласующего'] as $key=>$label):?><label><?=h($label)?> <?=hrFormInput('leave_'.$key)?></label><?php endforeach?></div><p class="hr-hint">Первый согласующий по умолчанию — руководитель из CRM. Это заготовки для согласования и подписи.</p></fieldset></details></div>
<div class="hr-actions"><button id="generate-button" <?=!$person->ACLAccess('edit')?'disabled':''?>><?=$documentId?'Сформировать ещё один документ':'Сформировать документ'?></button><span class="hr-hint">Параметры сохраняются в текущей сессии.</span></div></form>
<?php if($config):?><section class="hr-panel"><h2><?=$editTemplate?'Редактирование шаблона':'Готовый документ'?></h2><?php if($documentId):?><div class="hr-actions"><a class="hr-button" href="<?=h(str_replace('http://crm',getenv('CRM_URL'),hrResourceUrl($documentId.'.docx')))?>">Скачать DOCX</a><a class="hr-button secondary" href="<?=h(str_replace('http://crm',getenv('CRM_URL'),hrResourceUrl($documentId.'.pdf')))?>">Скачать PDF</a></div><p class="hr-hint">Правки редактора сохраняются в DOCX. PDF содержит результат первоначальной генерации.</p><?php else:?><p class="hr-hint">Сохраняйте теги заполняемых блоков. <a href="<?=h(hrUiGuide('templates'))?>">Инструкция по шаблонам</a></p><?php endif?><div id="editor"></div></section>
<script src="<?=h(hrOfficePublicUrl())?>/web-apps/apps/api/documents/api.js"></script><script>var officeConfig=<?=json_encode($config,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?>;new DocsAPI.DocEditor('editor',officeConfig);</script><?php endif?>
<section class="hr-panel"><h2>История документов</h2><p class="hr-hint">Последние 30 документов сотрудника. Каждый экземпляр хранит снимок данных на момент генерации.</p><?php hrUiHistoryTable(array_slice(hrUiHistory($person->id),0,30));?></section>
<?php if($current_user->isAdmin()):?><p><a href="<?=h(hrUiUrl('HrTemplates'))?>">Управление шаблонами: создать копию или отредактировать форму</a></p><?php endif?>
<script>
const select=document.getElementById('template-select'),leaveFields=document.getElementById('leave-fields');function updateFields(){leaveFields.hidden=select.selectedOptions[0]?.dataset.kind!=='leave';}select.addEventListener('change',updateFields);updateFields();document.getElementById('generation-form').addEventListener('submit',()=>{const button=document.getElementById('generate-button');button.disabled=true;button.textContent='Формируется документ…';});
</script></main></body></html>
