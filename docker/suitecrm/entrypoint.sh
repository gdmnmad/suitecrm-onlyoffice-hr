#!/bin/sh
set -eu
cd /var/www/html
if [ ! -f bin/console ]; then
 cp -a /opt/suitecrm/. /var/www/html/
 chown -R www-data:www-data /var/www/html
fi
if [ ! -f .hr-installed ]; then
 php bin/console suitecrm:app:install -u "$CRM_ADMIN_USER" -p "$CRM_ADMIN_PASSWORD" -U suitecrm -P "$DB_PASSWORD" -H db -N suitecrm -S "$CRM_URL" -d no --no-interaction
 touch .hr-installed
fi
php /opt/hr/bootstrap.php
chown -R www-data:www-data /var/www/html
su -s /bin/sh www-data -c "php bin/console cache:clear"
chown -R www-data:www-data /var/www/html
exec docker-php-entrypoint "$@"
