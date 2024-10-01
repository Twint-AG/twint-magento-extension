<p align="center" style="font-size:150%"><b>TWINT Payment Module Guideline</b></p>

## Install the module

Please refer to the `README.md` for the installation steps.

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
