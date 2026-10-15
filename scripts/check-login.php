<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';
$u=BeanFactory::getBean('Users','1');echo $u->user_name,' ',User::checkPassword(getenv('CRM_ADMIN_PASSWORD'),$u->user_hash)?'PASSWORD_OK':'PASSWORD_INVALID',PHP_EOL;
