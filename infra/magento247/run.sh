rm -rf /var/www/html/vendor/twint/magento-2/zinfra

bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:clean
bin/magento cache:flush
