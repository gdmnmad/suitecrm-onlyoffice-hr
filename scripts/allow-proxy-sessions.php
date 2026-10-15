<?php
// CLI-only: retain cookie authentication and CSRF; allow a session across changing client IPs.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$path='/var/www/html/public/legacy/config_override.php';$backup='/var/www/html/.hr-config-before-proxy-sessions.php';
$sugar_config=[];if(is_file($path)){if(!is_file($backup)&&!copy($path,$backup))throw new RuntimeException('Cannot back up configuration');require $path;}
$sugar_config['verify_client_ip']=false;$code="<?php\n";
foreach($sugar_config as $key=>$value)$code.='$sugar_config['.var_export($key,true).']='.var_export($value,true).";\n";
if(file_put_contents($path,$code,LOCK_EX)===false)throw new RuntimeException('Cannot save configuration');
echo "Session IP binding disabled; login and CSRF remain enabled.\n";
