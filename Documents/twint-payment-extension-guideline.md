# TWINT Magento Extension Guide

## Install the module

1. Install the Module via Composer:
```bash
composer require twint-ag/twint-magento-extension
```
2. Enable the Module
```bash
bin/magento module:enable Twint_Magento
```
3. Run setup upgrade and recompile dependencies
```bash
bin/magento setup:upgrade && bin/magento setup:di:compile
```
4. Deploy static content (if in production mode)
```bash
bin/magento setup:static-content:deploy
```
5. Clear cache (if needed):
```bash
bin/magento cache:clean && bin/magento cache:flush
```

## Configure the module

### Enter the Credentials

#### 1. Login to the Admin console panel

#### 2. Go to `TWINT -> Credentials` or `STORE -> Configuration -> TWINT -> TWINT Credentials`

<img src="./Screenshots/twint-settings.png" alt="TWINT Credentials" width="900" height="auto">

- Enter the `Store UUID`.
- Under the `Certificate file` click `Choose file` and browse to the `*.p12` certificate file.
- Enter the `Certificate password`.
- **For test environment:** please select the `Test` option under the `Environment` dropdown.

<img src="./Screenshots/twint-credentials.png" alt="TWINT Credentials" width="900" height="auto">

> 🚩 **Note:** 
>
> After entering the certification password, please wait for the message `Certificate encrypted and stored` to shows up in the `Certificate` field before clicking `Save Config`

<img src="./Screenshots/twint-credentials-saved.png" alt="TWINT Credentials saved" width="900" height="auto">

### Configure the payment methods

#### 1. Go to `TWINT -> TWINT Checkout`

- Ensure that `TWINT Checkout` payment method is enabled (`Yes` option is selected for `Enabled` dropdown).

<img src="./Screenshots/twint-checkout-setting.png" alt="TWINT Checkout setting" width="900" height="auto">

#### 2. Go to `TWINT -> TWINT Express Checkout`

- Ensure that `TWINT Express Checkout` payment method is enabled (`Yes` option is selected for `Enabled` dropdown).
- Under the `Display screens` section -> Choose the placement for displaying the `TWINT Express Checkout` button.

> ⚠️ Disclaimer
> 
> Merchandise with variable shipping cost (e.g. Table Rate shipping) may not be eligible for `TWINT Express Checkout`.

<img src="./Screenshots/twint-express-checkout-setting.png" alt="TWINT Expesss Checkout setting" width="900" height="auto">

## Country and Currency

### Country

Under `Stores -> Configuration -> General`, ensure that:

- `Use system value` checkbox is unchecked.
- And `Switzerland` is selected for `Default Country`.

<img src="./Screenshots/default-country.png" alt="Default Country" width="900" height="auto">

### Currency

Under `Stores -> Configuration -> General -> Currency Setup`, ensure that:

- `Use system value` checkbox is unchecked.
- `Swiss Franc` is selected for Base (and Default) currency.

<img src="./Screenshots/currency-setting.png" alt="Add CHF currency" width="900" height="auto">
