<p align="center" style="font-size:150%"><b>TWINT Payment Extension Guideline</b></p>

## Install the extension

Please refer to the `README.md` for the installation guide.

## Configure the extension

### Enter the Credential

#### 1. Login to the Admin console panel

#### 2. Go to `TWINT -> Credentials` or `STORE -> Configuration -> TWINT -> TWINT Credentials`

<img src="./Screenshots/twint-settings.png" alt="TWINT Credentials" width="900" height="auto">

- Enter the `Store UUID`.
- Under the `Certificate file` click `Choose file` and browse to the `*.p12` certificate file.
- Enter the `Certificate password`.
- **For test environment:** please select the `Test` option under the `Environment` dropdown.

<img src="./Screenshots/twint-credentials.png" alt="TWINT Credentials" width="900" height="auto">

>🚩 **Note:** 
>
> After entering the certification password, please wait for the message `Certificate encrypted and stored` to shows up in the `Certificate` field before clicking `Save Config`

<img src="./Screenshots/twint-credentials-saved.png" alt="TWINT Credentials saved" width="900" height="auto">

### Configure the payment methods

#### 1. Go to `TWINT -> TWINT Checkout`

- Ensure that `TWINT Checkout` payment method is enabled (`Yes` option is selected for `Enabled`downdown).

<img src="./Screenshots/twint-checkout-setting.png" alt="TWINT Checkout setting" width="900" height="auto">

#### 2. Go to `TWINT -> TWINT Express Checkout`

- Ensure that `TWINT Express Checkout` payment method is enabled (`Yes` option is selected for `Enabled`downdown).
- Under the `Display screens` section -> Choose the placement for displaying the `TWINT Express Checkout` button.

<img src="./Screenshots/twint-express-checkout-setting.png" alt="TWINT Expesss Checkout setting" width="900" height="auto">

## Languages and Currency

### Configure multi-lingual store

- To support multi-languages, the coressponding language packs should be installed first (in the backend system).
- Under `Stores -> All Stores` A dedicated store view must be created for each desired language (please see the screenshot below for your example).

<img src="./Screenshots/store-views.png" alt="Store views" width="900" height="auto">

- Go to `Stores -> Configuration` -> select the corresponding store view under `Scope` (please confirm the scope switching popup).
  - For each store view, under `General -> Locale` -> Uncheck the `Use system value` checkbox and select the corresponding country.

<img src="./Screenshots/store-view-locales.png" alt="Store view locale setting" width="900" height="auto">

### Currency

Under `Stores -> Configuration -> General -> Currency Setup`, ensure that:

- `Use system value` checkbox is unchecked.
- `Swiss Franc` is selected for at least base (and default) currency.

<img src="./Screenshots/currency-setting.png" alt="Add CHF currency" width="900" height="auto">