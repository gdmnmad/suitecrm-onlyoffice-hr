<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';global $current_user;
foreach(['hr_editor'=>true,'hr_viewer'=>false] as $login=>$canEdit) {
 $current_user=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>$login]);
 if(!$current_user) throw new RuntimeException('Missing user '.$login);
 ACLAction::getUserActions($current_user->id,true);
 $p=BeanFactory::getBean('HR_People','22222222-2222-4222-8222-222222222222');
 if(!$p->ACLAccess('view') || $p->ACLAccess('edit')!==$canEdit)throw new RuntimeException('Bad person permissions '.$login);
 if(BeanFactory::newBean('HR_Audit')->ACLAccess('edit'))throw new RuntimeException('Audit writable '.$login);
 echo "PASS $login view/edit permissions and read-only audit\n";
}
