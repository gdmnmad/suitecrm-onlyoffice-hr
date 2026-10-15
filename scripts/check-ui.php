<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';global $current_user;$current_user=BeanFactory::newBean('Users')->retrieve_by_string_fields(['user_name'=>$argv[2]??'admin']);
$_SERVER['REQUEST_METHOD']='GET';$_GET=[];$_SESSION['hr_csrf']='test';
$page=$argv[1]??'HrGuide';if(!in_array($page,['HrGuide','HrLibrary','HrTemplates','HrAudit']))throw new RuntimeException('Unsupported test');
ob_start();require 'custom/'.$page.'.php';$html=ob_get_clean();
foreach(['<nav','Инструкции','</html>'] as $needle)if(!str_contains($html,$needle))throw new RuntimeException('Page incomplete: '.$needle);
if($page==='HrTemplates'&&$current_user->user_name==='hr_viewer'&&str_contains($html,'<button>Создать копию'))throw new RuntimeException('Viewer sees template creation');
echo "PASS $page for $current_user->user_name\n";
