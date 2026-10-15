<?php
if (!defined('sugarEntry')) die('Invalid entry');
function hrDir() { $dir='upload/hr-documents'; if(!is_dir($dir)) mkdir($dir,0770,true);file_put_contents($dir.'/.htaccess','Require all denied');return $dir; }
function hrSecret() { $secret=getenv('ONLYOFFICE_JWT_SECRET');if(!$secret)throw new RuntimeException('JWT secret not configured');return $secret; }
function hrB64($s){return rtrim(strtr(base64_encode($s),'+/','-_'),'=');}
function hrJwt($payload) { $h=hrB64(json_encode(['alg'=>'HS256','typ'=>'JWT']));$p=hrB64(json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));return "$h.$p.".hrB64(hash_hmac('sha256',"$h.$p",hrSecret(),true)); }
function hrVerifyJwt($token) {
 $parts=explode('.',$token);if(count($parts)!==3)return false;
 $header=json_decode(base64_decode(strtr($parts[0],'-_','+/')),true);if(($header['alg']??null)!=='HS256')return false;
 if(!hash_equals(hrB64(hash_hmac('sha256',$parts[0].'.'.$parts[1],hrSecret(),true)),$parts[2]))return false;
 $data=json_decode(base64_decode(strtr($parts[1],'-_','+/')),true);if(isset($data['exp'])&&$data['exp']<time())return false;return $data;
}
function hrResourceUrl($name,$mode='file') {
 $expires=time()+7200;$sig=hash_hmac('sha256',"$mode:$name:$expires",hrSecret());
 return 'http://crm/legacy/index.php?entryPoint=HrResource&mode='.$mode.'&name='.rawurlencode($name).'&expires='.$expires.'&sig='.$sig;
}
function hrOfficeUrl(){return 'http://onlyoffice';}
function hrOfficePublicUrl(){return rtrim(getenv('ONLYOFFICE_PUBLIC_URL')?:'http://localhost:8082','/');}
function hrHttp($url,$body=null,$timeout=120) {
 $public=hrOfficePublicUrl();if(str_starts_with($url,$public.'/'))$url=hrOfficeUrl().substr($url,strlen($public));
 $parts=parse_url($url);if(($parts['scheme']??'')!=='http'||($parts['host']??'')!=='onlyoffice'||isset($parts['port'])&&$parts['port']!==80)throw new RuntimeException('Unexpected ONLYOFFICE URL');
 $curl=curl_init($url);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_FOLLOWLOCATION=>false]);
 if($body!==null)curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.hrJwt(['payload'=>$body])],CURLOPT_POSTFIELDS=>json_encode($body)]);
 $result=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_HTTP_CODE);$error=curl_error($curl);curl_close($curl);
 if($result===false||$status!==200)throw new RuntimeException('ONLYOFFICE HTTP '.$status.' '.$error);return $result;
}
function hrBuild($script,$destination) {
 $job=bin2hex(random_bytes(16)).'.js';file_put_contents(hrDir().'/'.$job,$script);
 $payload=['async'=>false,'url'=>hrResourceUrl($job)];$payload['token']=hrJwt($payload);
 try {$response=json_decode(hrHttp(hrOfficeUrl().'/docbuilder',$payload),true,512,JSON_THROW_ON_ERROR);
  if(!empty($response['error'])||empty($response['end'])||empty($response['urls']))throw new RuntimeException('Document Builder response: '.json_encode($response));
  foreach($destination as $output=>$local) {
   if(empty($response['urls'][$output]))throw new RuntimeException('Builder output missing: '.$output);
   $bytes=hrHttp($response['urls'][$output]);if(strlen($bytes)<50)throw new RuntimeException('Empty output');file_put_contents(hrDir().'/'.$local,$bytes);
  }
  return $response['urls'];
 } finally {unlink(hrDir().'/'.$job);}
}
function hrTemplateRegistry(){return json_decode(@file_get_contents(hrDir().'/templates.registry'),true)?:[];}
function hrTemplates() {
 $templates=['leave'=>'Рапорт на отпуск','awards'=>'Список наград','service'=>'Послужной список'];
 foreach(hrTemplateRegistry() as $key=>$item)if(preg_match('/^custom-[a-f0-9]{32}$/D',$key)&&is_string($item['title']??null))$templates[$key]=$item['title'];
 return $templates;
}
function hrTemplateKind($key){return hrTemplateRegistry()[$key]['kind']??$key;}
function hrCloneTemplate($source,$title){
 global $current_user;
 if(!$current_user->isAdmin())throw new RuntimeException('Создавать шаблоны может только администратор.');
 $title=trim($title);if($title===''||mb_strlen($title)>120)throw new RuntimeException('Название шаблона: от 1 до 120 символов.');
 if(!isset(hrTemplates()[$source]))throw new RuntimeException('Выберите существующий шаблон-основу.');
 hrInitTemplates();$lock=fopen(hrDir().'/templates.lock','c');flock($lock,LOCK_EX);
 try {$registry=hrTemplateRegistry();$key='custom-'.bin2hex(random_bytes(16));if(!copy(hrDir().'/template-'.$source.'.docx',hrDir().'/template-'.$key.'.docx'))throw new RuntimeException('Не удалось скопировать шаблон.');
 $registry[$key]=['title'=>$title,'kind'=>hrTemplateKind($source),'created_by'=>$current_user->id,'created_at'=>date(DATE_ATOM)];$path=hrDir().'/templates.registry';file_put_contents($path.'.tmp',json_encode($registry,JSON_UNESCAPED_UNICODE));rename($path.'.tmp',$path);
 $subject=BeanFactory::newBean('HR_People');$subject->id=$key;$subject->name=$title;hrDocAudit($subject,'create_template',['template'=>$key,'source'=>$source,'title'=>$title]);return $key;
 }finally{flock($lock,LOCK_UN);fclose($lock);}
}
function hrInitTemplates() {
 foreach(hrTemplates() as $key=>$title) if(!file_exists(hrDir().'/template-'.$key.'.docx')) {
  $seed='/opt/demo-data/templates/template-'.$key.'.docx';
  if(is_file($seed)){copy($seed,hrDir().'/template-'.$key.'.docx');continue;}
  $script='builder.CreateFile("docx");' . "\n" . file_get_contents('/opt/hr/templates/common.js').file_get_contents('/opt/hr/templates/'.$key.'.js')."\nbuilder.SaveFile(\"docx\",\"output.docx\");\nbuilder.CloseFile();";
  hrBuild($script,['output.docx'=>'template-'.$key.'.docx']);
 }
}
function hrSnapshot($person,$leave) {
 $data=['person'=>[],'manager'=>null,'children'=>[],'leave'=>$leave,'generated_at'=>(new DateTimeImmutable('now',new DateTimeZone(getenv('HR_TIMEZONE')?:'Europe/Moscow')))->format('Y-m-d')];
 $schema=require '/opt/hr/schema.php';foreach($schema['HR_People'][1] as $key=>$def)$data['person'][$key]=html_entity_decode($person->$key??'',ENT_QUOTES,'UTF-8');
 if($person->manager_id) {$manager=BeanFactory::getBean('HR_People',$person->manager_id);$data['manager']=[];foreach(['name','rank_name','position','department'] as $key)$data['manager'][$key]=html_entity_decode($manager->$key??'',ENT_QUOTES,'UTF-8');}
 foreach(['HR_Awards','HR_Service','HR_Education','HR_Relatives'] as $module) {
  $bean=BeanFactory::newBean($module);$rows=$bean->get_full_list($module==='HR_Service'?'start_date':'name',$bean->table_name.'.person_id='.$GLOBALS['db']->quoted($person->id));$data['children'][$module]=[];
  foreach($rows??[] as $row) {$record=[];foreach($schema[$module][1] as $key=>$def)$record[$key]=html_entity_decode($row->$key??'',ENT_QUOTES,'UTF-8');$data['children'][$module][]=$record;}
 }
 return $data;
}
function hrDocAudit($person,$operation,$payload){require_once '/opt/hr/HrAuditSupport.php';hrAuditWrite($person,$operation,$payload);}
function hrGenerate($person,$template,$leave) {
 global $current_user;
 if(!array_key_exists($template,hrTemplates())||!$person->ACLAccess('edit'))throw new RuntimeException('Нет доступа к генерации');
 $lock=fopen(hrDir().'/.generation.lock','c');flock($lock,LOCK_EX);
 try {
  hrInitTemplates();$documentId=bin2hex(random_bytes(16));$data=hrSnapshot($person,$leave);
  $script='builder.OpenFile('.json_encode(hrResourceUrl('template-'.$template.'.docx'),JSON_UNESCAPED_SLASHES).', "");' . "\n" . 'var data='.json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).';' . "\n" .file_get_contents('/opt/hr/templates/common.js').file_get_contents('/opt/hr/templates/fill.js')."\nbuilder.SaveFile(\"docx\",\"output.docx\");\nbuilder.SaveFile(\"pdf\",\"output.pdf\");\nbuilder.CloseFile();";
  hrBuild($script,['output.docx'=>$documentId.'.docx','output.pdf'=>$documentId.'.pdf']);
  $meta=['person_id'=>$person->id,'template'=>$template,'template_title'=>hrTemplates()[$template],'created_at'=>(new DateTimeImmutable('now',new DateTimeZone(getenv('HR_TIMEZONE')?:'Europe/Moscow')))->format(DATE_ATOM),'author'=>$current_user->id,'author_name'=>$current_user->user_name,'template_hash'=>hash_file('sha256',hrDir().'/template-'.$template.'.docx'),'revision'=>0,'key_version'=>0,'snapshot'=>$data];
  file_put_contents(hrDir().'/'.$documentId.'.json',json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));hrDocAudit($person,'generate_document',['document'=>$documentId,'template'=>$template,'template_hash'=>$meta['template_hash']]);return $documentId;
 } finally {flock($lock,LOCK_UN);fclose($lock);}
}

function hrCallbackActor($data){
 foreach(array_reverse($data['actions']??[]) as $action)if(!empty($action['userid']))return $action['userid'];
 $users=$data['users']??[];return count($users)===1?$users[0]:null;
}
function hrCallbackContributors($data){
 $names=[];foreach($data['users']??[] as $id){$user=BeanFactory::getBean('Users',$id);if($user->id)$names[]=$user->user_name;}return $names;
}
