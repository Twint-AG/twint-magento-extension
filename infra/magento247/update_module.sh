composer config repositories.twint-extension vcs https://git.nfq.asia/twint-ag/magento-extension.git
composer require twint/magento-2 --no-update
rm -rf composer.lock
composer install --no-progress --no-interaction

# Avoid di compile issue when running setup:di:compile
rm -rf /var/www/html/vendor/twint/magento-2/zinfra
bin/magento cache:clean
bin/magento cache:flush
bin/magento module:enable Twint_Magento

# Update Database schema and classes prototype
bin/magento setup:upgrade

# Build Dependency injection objects
bin/magento setup:di:compile

# Build client resources (JS, CSS ...)
bin/magento setup:static-content:deploy -f
