<?php
chdir('/var/www/html/public/legacy');define('sugarEntry',true);require 'include/entryPoint.php';require 'custom/HrDocumentSupport.php';
hrBuild("builder.CreateFile(\"docx\");\nvar doc=Api.GetDocument();\ndoc.GetElement(0).AddText(\"Hello\");\nbuilder.SaveFile(\"docx\",\"output.docx\");\nbuilder.CloseFile();",['output.docx'=>'00000000000000000000000000000000.docx']);echo "PROBE OK\n";
