<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require_once 'modules/MySettings/TabController.php';global $current_user;
foreach(['admin','hr_editor','hr_viewer'] as $login){
 $current_user=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>$login]);$tabs=query_module_access_list($current_user);
 foreach(['HR_People','HR_Documents','HR_Templates','HR_Help'] as $module)if(!isset($tabs[$module]))throw new RuntimeException('Missing tab '.$module.' for '.$login);
 if(isset($tabs['Accounts']))throw new RuntimeException('Sales tabs still enabled for '.$login);
 echo "PASS persisted HR tabs for $login\n";
}
