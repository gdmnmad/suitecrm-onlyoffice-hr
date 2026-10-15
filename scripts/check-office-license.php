<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require 'custom/HrDocumentSupport.php';
$data=['c'=>'license'];$data['token']=hrJwt($data);$reply=json_decode(hrHttp(hrOfficeUrl().'/coauthoring/CommandService.ashx',$data),true);echo json_encode($reply,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
