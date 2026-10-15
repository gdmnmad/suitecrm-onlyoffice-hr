<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';
foreach(['admin'=>'CRM_ADMIN_PASSWORD','hr_editor'=>'HR_EDITOR_PASSWORD','hr_viewer'=>'HR_VIEWER_PASSWORD'] as $login=>$variable){$u=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>$login]);if(!$u||!User::checkPassword(getenv($variable),$u->user_hash))throw new RuntimeException('Password failed: '.$login);echo "PASS new password: $login\n";}
