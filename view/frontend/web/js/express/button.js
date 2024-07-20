define([
  'jquery',
  'Twint_Magento/js/modal/qr_modal',
  'catalogAddToCart'
], function ($, TwintModal) {
  'use strict';

  $.widget('mage.twintExpress', $.mage.catalogAddToCart, {
    clickedButton: null,
    options: {
      ...$.mage.catalogAddToCart.prototype.options,
      expressButtonSelector: 'button.twint-button'
    },
    _create: function (){
      this._super();
      $(this.options.expressButtonSelector).prop('disabled', false);
    },
    submitForm: function (form, button) {
      if(!button || !$(button).is(this.options.expressButtonSelector)) {
        return this.ajaxSubmit(form);
      }

      this.express();
    },

    express: function (){
      TwintModal.init(
        {
          id: 'fake',
          token: 'hsg27sg6sx'.toUpperCase(),
          amount: "CHF 12.33",
          mode: 'express'
        }
      );
      TwintModal.open();
    },

    _bindSubmit: function (){
      const self = this;

      if (this.element.data('catalog-addtocart-initialized')) {
        return;
      }

      this.element.data('catalog-addtocart-initialized', 1);
      this.element.on('submit', function (e) {
        e.preventDefault();
        self.submitForm($(this), e?.originalEvent?.submitter);
      });
    },

    disableAddToCartButton: function (form){
      this._super(form);
      $(this.options.expressButtonSelector).prop('disabled', true);
    },

    enableAddToCartButton: function (form){
      this._super(form);
      $(this.options.expressButtonSelector).prop('disabled', false);
    }
  });

  return $.mage.twintExpress;
});
