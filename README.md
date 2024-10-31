# TWINT Payment Module for Magento 2

This module provides seamless integration of the TWINT payment gateway with your Magento 2 store. It supports both **Regular** and **Express Checkout** payment methods, offering a flexible and secure payment solution for your customers.

### Key Features
 - **Regular Payment**: Customers can use TWINT to complete purchases through the standard checkout process.
 - **Express Checkout**: A fast checkout option for customers, to shorten the checkout process.

### Language Support

This module supports the following languages:

- English (en_US/en_GB)
- German (de_DE/de_CH)
- French (fr_FR/fr_CH)
- Italian (it_IT/it_CH)

#### Adding or Customizing Languages

To customize or add language support, follow these steps:

1. **Locate Language Files**: Translation files are located in the `i18n` folder.
2. **Edit or Add Translations**: use existing CSV files or create a new CSV for your desired language, following the **Magento 2** translation format.
3. **Deploy Static Content** (if in production mode):
   ```bash
   php bin/magento setup:static-content:deploy <language_code>

### Requirements
- PHP `>= 8.1`
- PHP extension: `soap`
- Magento 2.x (`magento/framework` version as `>=103.0.4`)
- TWINT account

## Installation
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
## Configuration
1. **Navigate to TWINT settings**:  
In your Magento admin panel, select **TWINT** from the main left sidebar.
2. **Set Up TWINT Credentials**:  
Under the **TWINT Credentials** section, upload your TWINT certificate file and provide the necessary account details, including API credentials, to complete the integration setup.
3. **Configure Payment Methods**:  
Navigate to **TWINT Checkout** and **TWINT Express Checkout** sections to configure the available payment options. Customize settings such as payment flow, button placement for Express Checkout, and other relevant details according to your store’s needs.
4. **Save Configuration**:  
After completing the setup in each section, click **Save** to apply the changes.

## Usage
Once installed and configured, TWINT will appear as a payment option in your Magento store during checkout. Customers can select TWINT, either for the full regular checkout process or via the streamlined **Express Checkout** button.


## Support
For any issues or feature requests, please submit a GitHub issue or contact our support team at [plugin@twint.ch](mailto:plugin@twint.ch).

## License
This module is licensed under the [MIT License](https://opensource.org/licenses/MIT). See the [LICENSE](https://opensource.org/licenses/MIT) file for more details.
