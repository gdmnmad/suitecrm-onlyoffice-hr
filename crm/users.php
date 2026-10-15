<?php
require_once 'modules/ACLRoles/ACLRole.php';
foreach (['hr_editor'=>'Кадровик','hr_viewer'=>'Просмотр'] as $login=>$title) {
 $found=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>$login]);
 if (!$found) {
 $user=BeanFactory::newBean('Users');$user->user_name=$login;$user->last_name=$title;$user->status='Active';$user->employee_status='Active';$user->is_admin=0;$user->user_hash=User::getPasswordHash(getenv('HR_DEMO_PASSWORD'));$user->save();
 }
 $user=$found?:$user;
 $role=BeanFactory::newBean('ACLRoles')->retrieve_by_string_fields(['name'=>$title]);
 if(!$role){$role=BeanFactory::newBean('ACLRoles');$role->name=$title;$role->save();}
 foreach ($role->getRoleActions('') as $module=>$types) foreach ($types as $actions) foreach ($actions as $actionName=>$action) {
  $access=ACL_ALLOW_NONE;
  if (array_key_exists($module,$schema)||isset($navModules[$module])) {
   $access=ACL_ALLOW_ALL;
   if ($module==='HR_Audit' || $login==='hr_viewer'||isset($navModules[$module])) if (in_array($actionName,['edit','delete','import','massupdate'])) $access=ACL_ALLOW_NONE;
  }
  $role->setAction($role->id,$action['id'],$access);
 }
 $role->load_relationship('users');$role->users->add($user->id);
}
require_once 'modules/MySettings/TabController.php';
$tabs=new TabController();$previousSystem=$tabs->get_system_tabs();$display=TabController::get_key_array(['HR_People','HR_Documents','HR_Templates','HR_Awards','HR_Service','HR_Audit','HR_Education','HR_Relatives','HR_Help']);
if(!file_exists('/var/www/html/.hr-previous-system-tabs'))file_put_contents('/var/www/html/.hr-previous-system-tabs',json_encode(array_values($previousSystem)));
$tabs->set_system_tabs(array_merge(['Home'],array_values($display)));$system=$tabs->get_system_tabs();$hidden=[];
foreach(['admin','hr_editor','hr_viewer'] as $login){
 $user=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>$login]);if(!$user||$user->getPreference('hr_ui_version')==='4')continue;
 $user->setPreference('hr_previous_display_tabs',$user->getPreference('display_tabs'));$user->setPreference('hr_previous_hide_tabs',$user->getPreference('hide_tabs'));
 $user->setPreference('display_tabs',$display);$user->setPreference('hide_tabs',$hidden);$user->setPreference('max_tabs',9);$user->setPreference('navigation_paradigm','m');$user->setPreference('hr_ui_version','4');$user->savePreferencesToDB();
}
