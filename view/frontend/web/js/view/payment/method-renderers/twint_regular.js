define([
  'jquery',
  'Magento_Checkout/js/view/payment/default',
  'Magento_Checkout/js/model/payment/additional-validators',
  'ko',
  'Twint_Magento/js/modal/qr_modal',
  'Twint_Magento/js/model/checkout'
], function ($, Component, additionalValidators, ko, QrModal, TwintCheckout) {
  'use strict';

  return Component.extend({
    redirectAfterPlaceOrder: false,
    orderId: null,
    defaults: {
      template: 'Twint_Magento/payment/twint'
    },

    initialize: function () {
      this._super();
    },

    afterPlaceOrder: function () {
      TwintCheckout(this.orderId);
    },

    showModal: function () {
      QrModal.init(
        {
          token: 122342,
          amount: 'CHF 23.92'
        }
      );
      QrModal.open();
    },

    getCode: function () {
      return 'twint_regular';
    },

    getInstruction: function () {
      return $.mage.__('Payment method offered by TWINT');
    },

    isActive: function () {
      return true;
    },

    getLogoUrl: function () {
      return require.toUrl('Twint_Magento/images/twint.svg');
    },

    getExpressUrl: function () {
      return require.toUrl('Twint_Magento/images/express.svg');
    },

    placeOrder: function (data, event) {
      var self = this;

      if (event) {
        event.preventDefault();
      }

      if (this.validate() &&
        additionalValidators.validate() &&
        this.isPlaceOrderActionAllowed() === true
      ) {
        this.isPlaceOrderActionAllowed(false);

        this.getPlaceOrderDeferredObject()
          .done(
            function (data) {
              console.log(data);
              self.orderId = data;
              self.afterPlaceOrder();

              if (self.redirectAfterPlaceOrder) {
                redirectOnSuccessAction.execute();
              }
            }
          ).always(
          function () {
            self.isPlaceOrderActionAllowed(true);
          }
        );

        return true;
      }

      return false;
    },
  });
});
