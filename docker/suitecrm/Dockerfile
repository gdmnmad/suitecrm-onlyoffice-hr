FROM php:8.2-apache-bookworm@sha256:6d6e8fa0373692f588cf9f871f141eb6eba14a9260c346ffe7c93c51646a633f
RUN apt-get update && apt-get install -y --no-install-recommends curl unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev libldap2-dev libc-client2007e-dev libkrb5-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
 && docker-php-ext-install -j2 mysqli pdo_mysql intl zip gd mbstring soap ldap imap opcache \
 && a2enmod rewrite headers && rm -rf /var/lib/apt/lists/*
ARG SUITECRM_VERSION=8.10.2
RUN curl -fL "https://github.com/SuiteCRM/SuiteCRM-Core/releases/download/v${SUITECRM_VERSION}/SuiteCRM-${SUITECRM_VERSION}.zip" -o /tmp/crm.zip \
 && mkdir /opt/suitecrm && unzip -q /tmp/crm.zip -d /opt/suitecrm && rm /tmp/crm.zip
RUN apt-get update && apt-get install -y --no-install-recommends libcurl4-openssl-dev && docker-php-ext-install curl && rm -rf /var/lib/apt/lists/*
RUN printf 'memory_limit=512M\nupload_max_filesize=64M\npost_max_size=64M\nmax_execution_time=300\ndisplay_errors=Off\nlog_errors=On\n' > /usr/local/etc/php/conf.d/suitecrm.ini
COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY entrypoint.sh /usr/local/bin/hr-entrypoint
RUN chmod +x /usr/local/bin/hr-entrypoint && echo 'ServerName localhost' >> /etc/apache2/apache2.conf
ENTRYPOINT ["hr-entrypoint"]
CMD ["apache2-foreground"]
