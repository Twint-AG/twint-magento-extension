define([
  'jquery',
  'Magento_Catalog/js/validate-product-original',
  'catalogAddToCart',
], function ($) {
  'use strict';

  $.widget('mage.twintProductValidate', $.mage.productValidate, {
    options: {
      ...$.mage.productValidate.prototype.options,
      expressButtonSelector: 'button.tw-button.express'
    },

    _create: function (event) {
      const bindSubmit = this.options.bindSubmit;

      this.element.validation({
        radioCheckboxClosest: this.options.radioCheckboxClosest,

        submitHandler: function (form) {
          const jqForm = $(form).twintExpress({
            bindSubmit: bindSubmit
          });

          jqForm.twintExpress('submitForm', jqForm, this.submitButton);

          return false;
        }
      });

      $(this.options.addToCartButtonSelector).attr('disabled', false);
      $(this.options.expressButtonSelector).attr('disabled', false);
    }
  });

  return $.mage.twintProductValidate;
});
