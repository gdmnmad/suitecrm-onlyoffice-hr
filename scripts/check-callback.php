<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require 'custom/HrDocumentSupport.php';global $current_user;$current_user=BeanFactory::getBean('Users','1');
$person=BeanFactory::getBean('HR_People','22222222-2222-4222-8222-222222222222');$id=hrGenerate($person,'awards',[]);
$probe=bin2hex(random_bytes(16)).'.docx';
$script='builder.OpenFile('.json_encode(hrResourceUrl($id.'.docx'),JSON_UNESCAPED_SLASHES).', "");'."\n".'var p=Api.CreateParagraph(); p.AddText("Проверка сохранения из ONLYOFFICE."); Api.GetDocument().Push(p);'."\nbuilder.SaveFile(\"docx\",\"output.docx\");\nbuilder.CloseFile();";
$urls=hrBuild($script,['output.docx'=>$probe]);$data=['status'=>6,'key'=>$id.'-0','url'=>str_replace(hrOfficeUrl(),hrOfficePublicUrl(),$urls['output.docx']),'users'=>['1']];
$ch=curl_init(hrResourceUrl($id.'.docx','callback'));curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.hrJwt(['payload'=>$data])],CURLOPT_POSTFIELDS=>json_encode($data)]);
$reply=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);if($status!==200 || (json_decode($reply,true)['error']??1)!==0)throw new RuntimeException('Callback rejected: '.$status.' '.$reply);
$meta=json_decode(file_get_contents(hrDir().'/'.$id.'.json'),true);if(($meta['key_version']??0)!==0)throw new RuntimeException('Force-save changed active session key');if($meta['revision']!==1)throw new RuntimeException('Revision not incremented');
$zip=new ZipArchive();$zip->open(hrDir().'/'.$id.'.docx');$xml=$zip->getFromName('word/document.xml');$zip->close();if(!str_contains($xml,'Проверка сохранения из ONLYOFFICE.'))throw new RuntimeException('Modified file not saved');
unlink(hrDir().'/'.$probe);echo "PASS signed callback saves real ONLYOFFICE output, public URL rewrite and revision; document $id\n";
