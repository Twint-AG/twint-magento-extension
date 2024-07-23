define([
  'jquery',
  'Twint_Magento/js/modal/qr_modal',
], function ($, TwintModal) {
  class TwintLoaderClass{
    constructor($, container){
      this.$ = $;

      this.container = $(container);
    }

    start(){
      this.container.trigger('processStart');
    }

    stop(){
      let stop = this.container.trigger.bind(this.container, 'processStop');
      stop();
    }
  }

  class TwintExpressCheckoutClass{
    constructor($, modal) {
      this.$ = $;
      this.url = window.checkout.expressCheckoutUrl;
      this.modal = modal;
      this.loader = new TwintLoaderClass(this.$, 'body');
    }

    openMiniCart(){
      $('[data-block="minicart"]').find('[data-role="dropdownDialog"]').dropdownDialog("open");
    }

    checkout(formData, onSuccess = null, onError= null){
      let self = this;
      this.$.ajax({
        url: self.url,
        data: formData,
        type: 'post',
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,

        beforeSend: function () {
          self.loader.start();
        },

        success: function (res) {
          self.loader.stop();

          if(res.backUrl){
            window.location = res.backUrl;
          }

          if (res.showMiniCart) {
            self.openMiniCart();
          }

          if (res.success) {
            self.showQR(res);
          }

          if(typeof onSuccess == 'function'){
            onSuccess(res);
          }
        },

        error: function (res) {
          self.loader.stop();
          console.log("Express checkout error: " + res.responseText);

          if(typeof onError == 'function'){
            onError(res);
          }
        },

        complete: function (res) {
          if (res.state() === 'rejected') {
            location.reload();
          }
        }
      });
    }

    showQR(data) {
      this.modal.init(
        {
          id: data.id,
          token: data.token,
          amount: data.amount,
          mode: 'express'
        }
      );
      this.modal.open();
    }
  }

  return new TwintExpressCheckoutClass($, TwintModal);
});
