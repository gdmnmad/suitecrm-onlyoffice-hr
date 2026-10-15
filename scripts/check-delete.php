<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';global $current_user;$current_user=BeanFactory::getBean('Users','1');
$p=BeanFactory::newBean('HR_People');$p->name='INTEGRATION DELETE TEST';$p->save();$personId=$p->id;
$a=BeanFactory::newBean('HR_Awards');$a->name='TEST CHILD';$a->person_id=$personId;$a->save();$awardId=$a->id;$p->mark_deleted($personId);
$deleted=BeanFactory::newBean('HR_Awards');$deleted->retrieve($awardId,true,false);
if(!$deleted->deleted)throw new RuntimeException('Child award not soft-deleted');
echo "PASS person deletion soft-deletes child records\n";
