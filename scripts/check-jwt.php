<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require 'custom/HrDocumentSupport.php';
$token=hrJwt(['status'=>2,'exp'=>time()+30]);$claims=hrVerifyJwt($token);
if(($claims['status']??null)!==2)throw new RuntimeException('Valid token rejected');
$parts=explode('.',$token);$parts[1]=hrB64(json_encode(['status'=>6]));if(hrVerifyJwt(implode('.',$parts)))throw new RuntimeException('Modified token accepted');
if(hrVerifyJwt(hrJwt(['exp'=>time()-1])))throw new RuntimeException('Expired token accepted');
echo "PASS JWT signature, tampering and expiry\n";
