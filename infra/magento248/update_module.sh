rm -rf composer.lock
composer update --no-progress --no-interaction

bin/magento maintenance:enable

# Avoid di compile issue when running setup:di:compile
rm -rf /var/www/html/vendor/twint-ag/twint-magento-extension/zinfra

bin/magento module:enable Twint_Magento

# Update Database schema and classes prototype
bin/magento setup:upgrade

# Build Dependency injection objects
bin/magento setup:di:compile

# Build client resources (JS, CSS ...)
bin/magento setup:static-content:deploy -f de_CH
bin/magento setup:static-content:deploy -f de_DE
bin/magento setup:static-content:deploy -f en_GB
bin/magento setup:static-content:deploy -f en_US
bin/magento setup:static-content:deploy -f fr_CH
bin/magento setup:static-content:deploy -f fr_FR
bin/magento setup:static-content:deploy -f it_CH
bin/magento setup:static-content:deploy -f it_IT

bin/magento maintenance:disable

# Clear cache again
bin/magento cache:clean
bin/magento cache:flush
