define([
  'jquery',
  'Twint_Magento/js/express/checkout'
], function ($, TwintExpressCheckout) {
  'use strict';

  $.widget('mage.twintWholeCartCheckout', {
    _create: function (){
      this.TwintCheckout = TwintExpressCheckout;

      this.enable();
      this.registerEvents();
    },

    registerEvents: function(){
      let self = this;
      this.element.on('click', function (event) {
        event.preventDefault();
        self.checkout();
      })
    },

    checkout: function(){
      let self = this;
      let formData = new FormData();
      formData.append('whole_cart', 1);

      this.disable();
      this.TwintCheckout.checkout(formData, function (){
        self.enable();
      });
    },

    enable: function (){
      this.element.prop('disabled', false);
    },

    disable: function (){
      this.element.prop('disabled', true);
    },
  });

  return $.mage.twintWholeCartCheckout;
});
