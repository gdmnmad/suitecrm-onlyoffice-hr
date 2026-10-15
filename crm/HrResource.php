<?php
require_once 'custom/HrDocumentSupport.php';
$mode=$_GET['mode']??'file';$name=$_GET['name']??'';$expires=(int)($_GET['expires']??0);
if(!in_array($mode,['file','callback'],true)||!preg_match('/^(?:[a-f0-9]{32}|template-(?:leave|awards|service|custom-[a-f0-9]{32}))\.(?:docx|pdf|js)$/D',$name)||$expires<time()||!hash_equals(hash_hmac('sha256',"$mode:$name:$expires",hrSecret()),$_GET['sig']??'')) {http_response_code(403);exit;}
$path=hrDir().'/'.$name;if(!is_file($path)){http_response_code(404);exit;}
if($mode==='file') {header('Content-Type: '.(str_ends_with($name,'.js')?'text/plain':(str_ends_with($name,'.pdf')?'application/pdf':'application/vnd.openxmlformats-officedocument.wordprocessingml.document')));readfile($path);exit;}
header('Content-Type: application/json');
try {
 $data=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
 $headers=function_exists('getallheaders')?array_change_key_case(getallheaders(),CASE_LOWER):[];
 $authorization=$_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??$headers['authorization']??'';
 $token=$data['token']??preg_replace('/^\s*Bearer\s+/i','',$authorization);
 $claims=hrVerifyJwt($token);if(!$claims)throw new RuntimeException('Invalid callback JWT');$data=$claims['payload']??$claims;
 if(in_array($data['status']??0,[2,6],true)) {
  $lock=fopen($path.'.lock','c');flock($lock,LOCK_EX);
  try {
  $bytes=hrHttp($data['url']);$temp=$path.'.tmp';file_put_contents($temp,$bytes);rename($temp,$path);
  $metaPath=hrDir().'/'.pathinfo($name,PATHINFO_FILENAME).'.json';
  if(is_file($metaPath)) {
   $meta=json_decode(file_get_contents($metaPath),true);$meta['revision']=($meta['revision']??0)+1;if($data['status']===2)$meta['key_version']=($meta['key_version']??0)+1;file_put_contents($metaPath,json_encode($meta,JSON_UNESCAPED_UNICODE));
   $actor=hrCallbackActor($data);$GLOBALS['current_user']=$actor?BeanFactory::getBean('Users',$actor):null;
   $person=BeanFactory::getBean('HR_People',$meta['person_id']);hrDocAudit($person,'save_document',['document'=>pathinfo($name,PATHINFO_FILENAME),'revision'=>$meta['revision'],'contributors'=>hrCallbackContributors($data)]);
  } elseif(str_starts_with($name,'template-')) {
   $actor=hrCallbackActor($data);
   $GLOBALS['current_user']=$actor?BeanFactory::getBean('Users',$actor):null;
   $subject=BeanFactory::newBean('HR_People');$subject->id=$name;$subject->name=$name;
   hrDocAudit($subject,'save_template',['template'=>$name,'hash'=>hash_file('sha256',$path)]);
  }
  } finally {flock($lock,LOCK_UN);fclose($lock);}
 }
 echo '{"error":0}';
} catch(Throwable $e){http_response_code(400);echo '{"error":1}';error_log($e->getMessage());}
exit;
