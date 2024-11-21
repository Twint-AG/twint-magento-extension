define([
  'jquery',
  'catalogAddToCart-original',
  'Magento_Catalog/js/product/view/product-ids-resolver',
  'Twint_Magento/js/express/checkout'
], function ($, ParentComponent, idsResolver, TwintExpressCheckout ) {
  'use strict';

  $.widget('mage.twintExpress', $.mage.catalogAddToCart, {
    options: {
      ...$.mage.catalogAddToCart.prototype.options,
      expressButtonSelector: 'button.tw-button.express'
    },
    _create: function () {
      this._super();
      $(this.options.expressButtonSelector).prop('disabled', false);

      this.TwintCheckout = TwintExpressCheckout;
    },
    submitForm: function (form, button) {
      if (!button || !$(button).is(this.options.expressButtonSelector)) {
        return this.ajaxSubmit(form);
      }

      this.checkout(form);
    },

    checkout: function (form) {
      let self = this,
        productIds = idsResolver(form),
        productInfo = self.options.productInfoResolver(form),
        formData;

      $(self.options.minicartSelector).trigger('contentLoading');
      self.disableAddToCartButton(form);
      formData = new FormData(form[0]);

      this.TwintCheckout.checkout(formData, function (res) {
        self.enableAddToCartButton(form);
        self.triggerEventOnSuccess(form, productIds, productInfo, res);
      });
    },

    triggerEventOnSuccess(form, productIds, productInfo, res) {
      $(document).trigger('ajax:addToCart', {
        'sku': form.data().productSku,
        'productIds': productIds,
        'productInfo': productInfo,
        'form': form,
        'response': res
      });
    },

    _bindSubmit: function () {
      const self = this;

      if (this.element.data('catalog-addtocart-initialized')) {
        return;
      }

      this.element.data('catalog-addtocart-initialized', 1);
      this.element.on('submit', function (e) {
        e.preventDefault();
        self.submitForm($(this), e?.originalEvent?.submitter);
      });

      let button = this._getExpressButton();
      button && button.addEventListener('click', function (e){
        e.preventDefault();
        self.submitForm(self.element, e.currentTarget);
      });
    },

    _getExpressButton: function (){
        let container = this.element.closest('.product-item-actions').get(0);
        if(container){
          return container.querySelector('.tw-button.express');
        }

        return null;
    },

    disableAddToCartButton: function (form) {
      this._super(form);
      $(this.options.expressButtonSelector).prop('disabled', true);
    },

    enableAddToCartButton: function (form) {
      this._super(form);
      $(this.options.expressButtonSelector).prop('disabled', false);
    }
  });

  return $.mage.twintExpress;
});
