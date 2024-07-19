define(
  [
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'underscore',
    'Twint_Magento/js/modal/qr_modal'
  ],
  function (storage, errorProcessor, fullScreenLoader, _, QrModal) {
    'use strict';

    return function (order_id) {
      let serviceUrl = window.checkoutConfig.payment.twint.getPairingInformationUrl;
      let payload = {
        order: order_id
      }

      fullScreenLoader.startLoader();

      storage.post(
        serviceUrl, JSON.stringify(payload), true, 'application/json', {}
      ).fail(
        function (response) {
          console.log('error');
        }
      ).done(
        function (response) {
          QrModal.init(
            {
              id: response.id,
              token: response.token,
              amount: response.amount,
              mode: 'regular'
            }
          );
          QrModal.open();
        }
      ).always(
        function () {
          fullScreenLoader.stopLoader();
        }
      );
    };
  }
);
