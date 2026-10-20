define([
  'jquery',
  'Magento_Checkout/js/view/payment/default',
  'Magento_Checkout/js/model/payment/additional-validators',
  'ko',
  'Twint_Magento/js/action/regular-checkout'
], function ($, Component, additionalValidators, ko, TwintCheckout) {
  'use strict';

  return Component.extend({
    redirectAfterPlaceOrder: false,
    orderId: null,
    defaults: {
      template: 'Twint_Magento/payment/twint'
    },

    afterPlaceOrder: function () {
      TwintCheckout(this.orderId);
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
      const self = this;

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
