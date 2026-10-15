<?php
// Navigation-only modules expose existing authenticated pages through SuiteCRM.
$navModules=['HR_Documents'=>['Документы','HrLibrary'],'HR_Templates'=>['Шаблоны','HrTemplates'],'HR_Help'=>['Инструкции','HrGuide']];
foreach($navModules as $module=>[$label,$page]){
 writePhp("modules/$module/$module.php",'require_once "include/SugarObjects/templates/basic/Basic.php"; #[\\AllowDynamicProperties] class '.$module.' extends Basic {public $module_dir="'.$module.'";public $object_name="'.$module.'";public $table_name="hr_people";public function bean_implements($interface){return $interface==="ACL";}public function ACLAccess($view,$owner="not_set",$group="not_set"){if(in_array(strtolower($view),["edit","save","delete","import","massupdate"]))return false;return parent::ACLAccess($view,$owner,$group);}}');
 writePhp("modules/$module/vardefs.php",'$dictionary["'.$module.'"]=["table"=>"hr_people","fields"=>[]];require_once "include/SugarObjects/VardefManager.php";VardefManager::createVardef("'.$module.'","'.$module.'",["basic"]);');
 writePhp("modules/$module/Menu.php",'$module_menu=[["index.php?module='.$module.'&action=index",'.var_export('Открыть '.$label,true).',"List"]];');
 writePhp("modules/$module/index.php",'require "/opt/hr/'.$page.'.php";');
 writePhp("custom/modules/$module/views/view.list.php",'require_once "include/MVC/View/SugarView.php";class '.$module.'ViewList extends SugarView {public function display(){require "/opt/hr/'.$page.'.php";}}');
 foreach(['en_us','ru_ru'] as $lang)writePhp("modules/$module/language/$lang.lang.php",'$mod_strings=["LBL_MODULE_NAME"=>'.var_export($label,true).'];');
 $include.='$beanList["'.$module.'"]="'.$module.'";$beanFiles["'.$module.'"]="modules/'.$module.'/'.$module.'.php";$moduleList[]="'.$module.'";' . "\n";
 $beanList[$module]=$module;$beanFiles[$module]="modules/$module/$module.php";$moduleList[]=$module;$labels[$module]=$label;
}
require_once '/var/www/html/vendor/autoload.php';
$routingPath='/var/www/html/config/services/module/module_routing.yaml';
if(!file_exists($routingPath.'.hr-backup'))copy($routingPath,$routingPath.'.hr-backup');
$routing=Symfony\Component\Yaml\Yaml::parseFile($routingPath);
foreach($navModules as $module=>$info)$routing['parameters']['legacy.module_routing'][$module]=['index'=>false,'list'=>false,'record'=>false];
$routing['parameters']['legacy.module_routing']['HR_People']=['index'=>false,'list'=>false,'record'=>true];
$routing['parameters']['legacy.module_routing']['HR_Audit']=['index'=>false,'list'=>false,'record'=>true];
writePhp('custom/modules/HR_Audit/views/view.list.php','require_once "include/MVC/View/SugarView.php";class HR_AuditViewList extends SugarView {public function display(){require "/opt/hr/HrAudit.php";}}');
writePhp('custom/modules/HR_People/views/view.list.php','require_once "include/MVC/View/views/view.list.php";require_once "/opt/hr/HrUi.php";class HR_PeopleViewList extends ViewList {public function display(){hrUiStyle();echo "<main class=hr-shell>";hrUiNav("people");echo "<h1>Сотрудники</h1><p class=hr-hint>Нажмите на ФИО, чтобы открыть карточку. Для нового сотрудника используйте кнопку ниже.</p><div class=hr-actions>";if(BeanFactory::newBean("HR_People")->ACLAccess("edit"))echo "<a class=hr-button target=_top href=".hrUiEscape(getenv("CRM_URL")."/legacy/index.php?module=HR_People&action=EditView").">Создать сотрудника</a>";echo "<a target=_top href=".hrUiEscape(hrUiGuide("people")).">Инструкция и образец заполнения</a></div>";parent::display();echo "</main>";}}');
foreach(['HR_Awards','HR_Service','HR_Education','HR_Relatives'] as $module){
 $routing['parameters']['legacy.module_routing'][$module]=['index'=>false,'list'=>false,'record'=>false];
 writePhp("custom/modules/$module/views/view.list.php",'require_once "include/MVC/View/views/view.list.php";require_once "/opt/hr/HrUi.php";class '.$module.'ViewList extends ViewList {public function display(){hrUiListHeader("'.$module.'");parent::display();echo "</main>";}}');
}
$routing['parameters']['legacy.module_routing']['HR_People']['record']=false;
file_put_contents($routingPath,Symfony\Component\Yaml\Yaml::dump($routing,8,2));
