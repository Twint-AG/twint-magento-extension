define(
  [
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'underscore',
    'Twint_Magento/js/modal/modal',
    'Magento_Checkout/js/action/redirect-on-success'
  ],
  function (storage, errorProcessor, fullScreenLoader, _, TwintModal, RedirectOnSuccess) {
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
          TwintModal.init(
            {
              id: response.id,
              token: response.token,
              amount: response.amount,
              mode: 'regular'
            }
          );
          TwintModal.setOnSuccess(RedirectOnSuccess);
          TwintModal.open();
        }
      ).always(
        function () {
          fullScreenLoader.stopLoader();
        }
      );
    };
  }
);
