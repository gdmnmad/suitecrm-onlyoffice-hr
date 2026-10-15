<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require 'custom/HrDocumentSupport.php';
$script="builder.CreateFile(\"docx\");\ntry {\n".file_get_contents('/opt/hr/templates/common.js').file_get_contents('/opt/hr/templates/leave.js')."\n} catch(e) {Api.GetDocument().GetElement(0).AddText('ERROR: '+e.message);}\nbuilder.SaveFile(\"docx\",\"output.docx\");\nbuilder.CloseFile();";
hrBuild($script,['output.docx'=>'00000000000000000000000000000001.docx']);$zip=new ZipArchive();$zip->open(hrDir().'/00000000000000000000000000000001.docx');echo html_entity_decode(strip_tags($zip->getFromName('word/document.xml'))),PHP_EOL;
