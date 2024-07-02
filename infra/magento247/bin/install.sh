#!/bin/bash
set -xe

export INSTALL_MAGENTO=1

# https://github.com/magento/magento2/issues/34566
mv app/etc/config.php app/etc/config.php.bak

yes | php bin/magento setup:install \
    --no-interaction \
    --backend-frontname=$ADMIN_URI \
    --key=$KEY \
    --admin-user=$ADMIN_USER \
    --admin-password=$ADMIN_PASSWORD \
    --admin-firstname=$ADMIN_FIRSTNAME \
    --admin-lastname=$ADMIN_LASTNAME \
    --admin-email=$ADMIN_EMAIL \
    --db-host=$DB_HOST \
    --db-user=$DB_USER \
    --db-password=$DB_PASS \
    --db-name=$DB_NAME \
    --search-engine=elasticsearch7 \
    --elasticsearch-host=$ELASTICSEARCH_HOST \
    --elasticsearch-index-prefix=magento2

mv app/etc/config.php.bak app/etc/config.php

php bin/magento setup:db:status || php bin/magento setup:upgrade --keep-generated
php bin/magento app:config:status || php bin/magento app:config:import
