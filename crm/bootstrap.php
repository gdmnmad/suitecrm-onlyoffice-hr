<?php
// Executed only inside the SuiteCRM container; no direct SQL writes for HR records.
chdir('/var/www/html/public/legacy');
define('sugarEntry',true);
require_once 'include/entryPoint.php';
global $current_user,$moduleList,$beanList,$beanFiles,$app_list_strings,$mod_strings,$db;
$current_user=BeanFactory::getBean('Users','1');
$schema=require '/opt/hr/schema.php';
require '/opt/checks/configure-static-cache.php';
if(getenv('HR_ALLOW_PROXY_SESSIONS')==='true')require '/opt/checks/allow-proxy-sessions.php';
function writePhp($path,$code) { if (!is_dir(dirname($path))) mkdir(dirname($path),0775,true); file_put_contents($path,"<?php\n".$code."\n"); }
$include=''; $labels=[];
foreach ($schema as $module=>[$label,$fields]) {
 $table=strtolower($module); $dir="modules/$module";
 if (!is_dir($dir)) mkdir($dir,0775,true);
 $readonly=$module==='HR_Audit';
 $extra=$readonly?'public function ACLAccess($view, $is_owner = "not_set", $in_group = "not_set") { if (in_array(strtolower($view), ["edit","save","editview","popupeditview","delete","import","massupdate"])) return false; return parent::ACLAccess($view,$is_owner,$in_group); }':'';
 writePhp("$dir/$module.php",'require_once "include/SugarObjects/templates/basic/Basic.php"; #[\\AllowDynamicProperties] class '.$module.' extends Basic { public $module_dir="'.$module.'"; public $object_name="'.$module.'"; public $table_name="'.$table.'"; public $new_schema=true; public function fill_in_additional_list_fields() { require_once "custom/HrHooks.php"; (new HrHooks())->afterRetrieve($this,"after_retrieve",[]); } public function bean_implements($interface) { return $interface === "ACL"; } '.$extra.' }');
 $defs=[]; $strings=['LBL_MODULE_NAME'=>$label,'LBL_NAME'=>'Название','LBL_DESCRIPTION'=>'Описание','LBL_ASSIGNED_TO_NAME'=>'Ответственный','LBL_DATE_ENTERED'=>'Создано','LBL_DATE_MODIFIED'=>'Обновлено']; $layout=[];
 foreach ($fields as $name=>[$caption,$type]) {
  $vname='LBL_'.strtoupper($name); $strings[$vname]=$caption;
  if ($type==='person') {
   $defs[$name]=['name'=>$name,'vname'=>$vname,'type'=>'id','audited'=>true];
   $relate=str_replace('_id','_name',$name);
   $defs[$relate]=['name'=>$relate,'vname'=>$vname,'type'=>'relate','source'=>'non-db','module'=>'HR_People','id_name'=>$name,'rname'=>'name','save'=>true];
   $layout[]=$relate;
  } else {
   $defs[$name]=['name'=>$name,'vname'=>$vname,'type'=>$type,'audited'=>!$readonly,'required'=>$name==='name','len'=>$type==='text'?null:255];
   if ($name==='documents_url') {$defs[$name]['source']='non-db';$defs[$name]['linkText']='Документы сотрудника';$defs[$name]['target']='_self';}
   $defs[$name]['help']=$caption.($name==='name'?' — обязательное поле.':($type==='date'?' — выберите дату в календаре.':''));
   if($name==='end_date')$defs[$name]['help']='Оставьте пустым для текущего периода.';
   if($name!=='documents_url')$layout[]=$name;
  }
 }
 $relationships=[];
 if ($module==='HR_People') {
  foreach (['HR_Awards','HR_Service','HR_Education','HR_Relatives'] as $child) {
   $link=strtolower($child);$vname='LBL_'.strtoupper($link);$strings[$vname]=$schema[$child][0];
   $defs[$link]=['name'=>$link,'type'=>'link','relationship'=>$link.'_person','source'=>'non-db','module'=>$child,'bean_name'=>$child,'vname'=>$vname];
   $relationships[$link.'_person']=['lhs_module'=>'HR_People','lhs_table'=>'hr_people','lhs_key'=>'id','rhs_module'=>$child,'rhs_table'=>strtolower($child),'rhs_key'=>'person_id','relationship_type'=>'one-to-many'];
  }
 }
 $dictionary=['table'=>$table,'audited'=>!$readonly,'fields'=>$defs,'relationships'=>$relationships,'optimistic_locking'=>true];
 writePhp("$dir/vardefs.php",'$dictionary['.var_export($module,true).']='.var_export($dictionary,true).'; require_once "include/SugarObjects/VardefManager.php"; VardefManager::createVardef("'.$module.'","'.$module.'",["basic","assignable"]);');
 foreach (['en_us','ru_ru'] as $language) writePhp("$dir/language/$language.lang.php",'$mod_strings='.var_export($strings,true).';');
 if($module==='HR_People') {
  $groups=['LBL_HR_IDENTITY'=>['name','personnel_number','birth_date','birth_place','nationality','marital_status'],'LBL_HR_ASSIGNMENT'=>['rank_name','rank_date','position','department','manager_name'],'LBL_HR_CONTRACT'=>['contract_start','contract_end'],'LBL_HR_NOTES'=>['description']];
  $strings+=['LBL_HR_IDENTITY'=>'Личные данные','LBL_HR_ASSIGNMENT'=>'Служба и подчинённость','LBL_HR_CONTRACT'=>'Контракт','LBL_HR_NOTES'=>'Дополнительные сведения','LBL_HR_DOCUMENTS'=>'Документы'];
 } else {$groups=['LBL_HR_RECORD'=>$layout];$strings['LBL_HR_RECORD']=$label;}
 foreach (['en_us','ru_ru'] as $language) writePhp("$dir/language/$language.lang.php",'$mod_strings='.var_export($strings,true).';');
 foreach (['editview','detailview'] as $view) {
  $viewName=$view==='editview'?'EditView':'DetailView';$panels=[];
  foreach($groups as $group=>$fields)$panels[$group]=array_chunk($fields,2);
  if($module==='HR_People'&&$view==='detailview')$panels['LBL_HR_DOCUMENTS']=[[['name'=>'documents_url','linkText'=>'Документы сотрудника','target'=>'_self']]];
  $meta=['templateMeta'=>['maxColumns'=>2,'useTabs'=>false,'widths'=>[['label'=>'15%','field'=>'35%'],['label'=>'15%','field'=>'35%']]],'panels'=>$panels];
  writePhp("$dir/metadata/{$view}defs.php",'$viewdefs["'.$module.'"]["'.$viewName.'"]='.var_export($meta,true).';');
 }
 $columns=[
 'HR_People'=>['name','personnel_number','rank_name','position','department','manager_name'],
 'HR_Awards'=>['name','person_name','award_date','order_number'],
 'HR_Service'=>['name','person_name','position','start_date','end_date'],
 'HR_Education'=>['name','person_name','qualification','start_date','end_date'],
 'HR_Relatives'=>['name','person_name','relation_type','birth_date'],
 'HR_Audit'=>['name','actor_name','operation','date_entered','entity_module']
 ];
 $list=[];
 foreach($columns[$module] as $field)$list[strtoupper($field)]=['width'=>'16%','label'=>$defs[$field]['vname']??('LBL_'.strtoupper($field)),'default'=>true,'link'=>in_array($field,['name','person_name','manager_name'])];
 writePhp("$dir/metadata/listviewdefs.php",'$listViewDefs["'.$module.'"]='.var_export($list,true).';');
 writePhp("$dir/metadata/searchdefs.php",'$searchdefs["'.$module.'"]='.var_export(['layout'=>['basic_search'=>['name'=>['name'=>'name','default'=>true]],'advanced_search'=>['name'=>['name'=>'name','default'=>true]]],'templateMeta'=>['maxColumns'=>3,'widths'=>['label'=>10,'field'=>30]]],true).';');
 writePhp("$dir/metadata/SearchFields.php",'$searchFields["'.$module.'"]=["name"=>["query_type"=>"default"]];');
 $createLabel=['HR_People'=>'Создать сотрудника','HR_Awards'=>'Добавить награду','HR_Service'=>'Добавить период службы','HR_Education'=>'Добавить образование','HR_Relatives'=>'Добавить родственника'][$module]??'Создать запись';
 if(!$readonly)writePhp("custom/modules/$module/views/view.edit.php",'require_once "include/MVC/View/views/view.edit.php"; require_once "/opt/hr/HrUi.php"; class '.$module.'ViewEdit extends ViewEdit { public function display() { hrUiStyle();echo "<main class=hr-shell>";hrUiNav("people");hrUiRecordHelp("'.$module.'");parent::display();echo "</main>"; } }');
 if(!$readonly)writePhp("custom/modules/$module/views/view.detail.php",'require_once "include/MVC/View/views/view.detail.php"; require_once "/opt/hr/HrUi.php"; class '.$module.'ViewDetail extends ViewDetail { public function display() { hrUiStyle();echo "<main class=hr-shell>";hrUiNav("people");hrUiReturnLinks("'.$module.'");parent::display();echo "</main>"; } }');
 writePhp("$dir/Menu.php",$readonly?'$module_menu=[];':'$module_menu=[["index.php?module='.$module.'&action=EditView","'.$createLabel.'","Create"],["index.php?module='.$module.'&action=index","Список","List"]];');
 writePhp("custom/modules/$module/logic_hooks.php",$readonly?'$hook_array=[];':'$hook_version=1; $hook_array=["after_retrieve"=>[[1,"HR document link","custom/HrHooks.php","HrHooks","afterRetrieve"]],"before_save"=>[[1,"HR validation","custom/HrHooks.php","HrHooks","beforeSave"]],"after_save"=>[[1,"HR audit","custom/HrHooks.php","HrHooks","afterSave"]],"before_delete"=>[[1,"HR child deletion","custom/HrHooks.php","HrHooks","beforeDelete"]],"after_delete"=>[[1,"HR delete audit","custom/HrHooks.php","HrHooks","afterDelete"]],"after_relationship_add"=>[[1,"HR relationship audit","custom/HrHooks.php","HrHooks","relationChanged"]],"after_relationship_delete"=>[[1,"HR relationship audit","custom/HrHooks.php","HrHooks","relationChanged"]]];');
 $include.='$beanList["'.$module.'"]="'.$module.'"; $beanFiles["'.$module.'"]="modules/'.$module.'/'.$module.'.php"; $moduleList[]="'.$module.'";' . "\n";
 $beanList[$module]=$module; $beanFiles[$module]="$dir/$module.php"; $moduleList[]=$module;
 $labels[$module]=$label;
}
$subpanels=[];
foreach (['HR_Awards','HR_Service','HR_Education','HR_Relatives'] as $index=>$child) {
 $listFields=['name'=>['vname'=>'LBL_NAME','widget_class'=>'SubPanelDetailViewLink','width'=>'40%']];
 foreach ($schema[$child][1] as $field=>[$caption,$type]) if ($field!=='name' && $type!=='person') $listFields[$field]=['vname'=>'LBL_'.strtoupper($field),'width'=>'20%'];
 $listFields['edit_button']=['widget_class'=>'SubPanelEditButton','width'=>'5%'];
 writePhp("modules/$child/metadata/subpanels/default.php",'$subpanel_layout='.var_export(['top_buttons'=>[['widget_class'=>'SubPanelTopCreateButton']],'where'=>'','list_fields'=>$listFields],true).';');
 $link=strtolower($child);
 $subpanels[$link]=['order'=>10+$index,'module'=>$child,'subpanel_name'=>'default','sort_order'=>'asc','sort_by'=>'name','title_key'=>'LBL_'.strtoupper($link),'get_subpanel_data'=>$link,'top_buttons'=>[['widget_class'=>'SubPanelTopCreateButton']]];
}
writePhp('modules/HR_People/metadata/subpaneldefs.php','$layout_defs["HR_People"]='.var_export(['subpanel_setup'=>$subpanels],true).';');
foreach (['HrDocumentSupport','HrDocuments','HrResource','HrGuide','HrTemplates','HrLibrary','HrAudit'] as $file) copy('/opt/hr/'.$file.'.php','custom/'.$file.'.php');
writePhp('custom/modules/Home/index.php','require "/opt/hr/HrHome.php";');
copy('/opt/hr/ui.css','/var/www/html/public/hr-ui.css');
copy('/opt/hr/ui.js','/var/www/html/public/hr-ui.js');
$uiLabels=['LBL_SAVE_BUTTON_LABEL'=>'Сохранить','LBL_SAVE_BUTTON_TITLE'=>'Сохранить','LBL_CANCEL_BUTTON_LABEL'=>'Отмена','LBL_CANCEL_BUTTON_TITLE'=>'Отмена','LBL_EDIT_BUTTON_LABEL'=>'Редактировать','LBL_EDIT_BUTTON_TITLE'=>'Редактировать','LBL_DELETE_BUTTON_LABEL'=>'Удалить','LBL_CREATE_BUTTON_LABEL'=>'Создать','LBL_CREATE'=>'Создать','LBL_EDIT'=>'Редактировать','LBL_DELETE'=>'Удалить','LBL_SEARCH'=>'Поиск','LBL_ACTIONS'=>'Действия','LBL_RELATIONSHIPS'=>'Связи','LBL_QUICK_CREATE'=>'Создать'];
foreach(['en_us','ru_ru'] as $language){$code='';foreach($uiLabels as $key=>$value)$code.='$app_strings['.var_export($key,true).']='.var_export($value,true).';';writePhp('custom/Extension/application/Ext/Language/'.$language.'.hr_ui.php',$code);}

$entries='';foreach(['HrDocuments'=>true,'HrResource'=>false,'HrGuide'=>true,'HrTemplates'=>true,'HrLibrary'=>true,'HrAudit'=>true,'HrBatch'=>true] as $entry=>$auth)$entries.='$entry_point_registry["'.$entry.'"]=["file"=>"custom/'.$entry.'.php","auth"=>'.($auth?'true':'false').'];';
writePhp('custom/Extension/application/Ext/EntryPointRegistry/hr_documents.php',$entries);
copy('/opt/hr/HrHooks.php','custom/HrHooks.php');
copy('/opt/hr/HrBatch.php','custom/HrBatch.php');
require '/opt/hr/navigation.php';
writePhp('custom/Extension/application/Ext/Include/hr.php',$include);
writePhp('custom/Extension/application/Ext/Language/en_us.hr.php', '$app_list_strings["moduleList"]=array_merge($app_list_strings["moduleList"]??[],'.var_export($labels,true).');');
writePhp('custom/Extension/application/Ext/Language/ru_ru.hr.php', '$app_list_strings["moduleList"]=array_merge($app_list_strings["moduleList"]??[],'.var_export($labels,true).');');
require_once 'modules/Administration/QuickRepairAndRebuild.php';
$repair=new RepairAndClear(); $repair->repairAndClearAll(['rebuildExtensions'],array_keys($schema),true,false);
foreach (array_keys($schema) as $module) ACLAction::addActions($module);
foreach (array_keys($navModules) as $module) ACLAction::addActions($module);
if (!file_exists('/var/www/html/.hr-seeded')) {
 $people=json_decode(file_get_contents('/opt/demo-data/people.json'),true,512,JSON_THROW_ON_ERROR);
 foreach ($people as $row) {
  if (BeanFactory::getBean('HR_People',$row['id'])->id) continue;
  $bean=BeanFactory::newBean('HR_People'); $bean->id=$row['id']; $bean->new_with_id=true;
  foreach ($row['fields'] as $key=>$value) $bean->$key=$value;
  $bean->assigned_user_id='1'; $bean->save();
 }
 foreach ($people as $row) foreach ($row['children'] as $module=>$records) foreach ($records as $values) {
  $hash=md5($row['id'].$module.json_encode($values));
  $id=substr($hash,0,8).'-'.substr($hash,8,4).'-'.substr($hash,12,4).'-'.substr($hash,16,4).'-'.substr($hash,20);
  if(BeanFactory::getBean($module,$id)->id) continue;
  $bean=BeanFactory::newBean($module);$bean->id=$id;$bean->new_with_id=true; foreach ($values as $key=>$value) $bean->$key=$value;
  $bean->person_id=$row['id']; $bean->assigned_user_id='1'; $bean->save();
 }
 file_put_contents('/var/www/html/.hr-seeded','1');
}
require '/opt/hr/users.php';
require_once 'custom/HrDocumentSupport.php';
hrInitTemplates();
echo "HR modules ready\n";
