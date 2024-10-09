# Avoid di compile issue when running setup:di:compile
rm -rf /var/www/html/vendor/twint-ag/twint-magento-extension/zinfra

bin/magento maintenance:enable

bin/magento module:enable Twint_Magento

# Update Database schema and classes prototype
bin/magento setup:upgrade

# Build Dependency injection objects
bin/magento setup:di:compile

# Build client resources (JS, CSS ...)
bin/magento setup:static-content:deploy -f

bin/magento maintenance:disable

# Clear cache again
bin/magento cache:clean && bin/magento cache:flush
