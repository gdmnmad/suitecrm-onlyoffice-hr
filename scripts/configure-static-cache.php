<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$path='/var/www/html/public/.htaccess';$source=is_file($path)?file_get_contents($path):'';
$source=preg_replace('/\n?# BEGIN HR STATIC ASSETS.*?# END HR STATIC ASSETS\n?/s','',$source);
$source.= <<<'CONF'

# BEGIN HR STATIC ASSETS
<IfModule mod_headers.c>
 <FilesMatch "\.[a-f0-9]{8,}\.(js|css)$">
  Header set Cache-Control "public, max-age=31536000, immutable"
 </FilesMatch>
 <FilesMatch "^hr-ui\.(js|css)$">
  Header set Cache-Control "public, max-age=3600"
 </FilesMatch>
</IfModule>
<IfModule mod_deflate.c>
 AddOutputFilterByType DEFLATE application/javascript text/javascript text/css application/json image/svg+xml
</IfModule>
# END HR STATIC ASSETS
CONF;
if(file_put_contents($path,$source."\n",LOCK_EX)===false)throw new RuntimeException('Cannot update static cache rules');
echo "Static assets cache configured; PHP pages excluded.\n";
