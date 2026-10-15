<?php
chdir('/var/www/html/public/legacy'); define('sugarEntry',true); require 'include/entryPoint.php';
global $current_user,$db; $current_user=BeanFactory::getBean('Users','1');
function check($condition,$message) { if (!$condition) throw new RuntimeException($message); echo "PASS $message\n"; }
$people=BeanFactory::newBean('HR_People')->get_full_list('name'); check(count($people??[])>=3,'Three demo people');
$bean=BeanFactory::newBean('HR_Awards');$bean->name='INTEGRATION TEST';$bean->person_id='22222222-2222-4222-8222-222222222222';$bean->order_number='TEST-BEFORE';$bean->save();$id=$bean->id;
$bean=BeanFactory::getBean('HR_Awards',$id);$bean->order_number='TEST-AFTER';$bean->save();$bean->mark_deleted($id);
$res=$db->query("SELECT operation, description, actor_name FROM hr_audit WHERE entity_id=".$db->quoted($id));$events=[];while ($row=$db->fetchByAssoc($res)) $events[$row['operation']]=$row;
check(isset($events['create'],$events['update'],$events['delete']),'Create/update/delete audit');
$diff=json_decode(html_entity_decode($events['update']['description']),true);
check(($diff['order_number']['before']??null)==='TEST-BEFORE' && ($diff['order_number']['after']??null)==='TEST-AFTER','Audit old and new field values');
check($events['create']['actor_name']===$current_user->user_name,'Audit author');
$person=BeanFactory::getBean('HR_People','11111111-1111-4111-8111-111111111111');$person->manager_id='22222222-2222-4222-8222-222222222222';
$caught=false;try {$person->save();} catch (RuntimeException $e) {$caught=true;}check($caught,'Hierarchy cycle blocked');
echo "CRM integration checks passed\n";
