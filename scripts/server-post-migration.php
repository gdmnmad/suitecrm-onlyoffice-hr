<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';global $current_user;$current_user=BeanFactory::getBean('Users','1');
foreach(['admin'=>'CRM_ADMIN_PASSWORD','hr_editor'=>'HR_EDITOR_PASSWORD','hr_viewer'=>'HR_VIEWER_PASSWORD'] as $login=>$variable){$user=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>$login]);$password=getenv($variable);if(!$user||!$password)throw new RuntimeException('Missing server credential for '.$login);$user->setNewPassword($password);echo "Updated credential: $login\n";}
