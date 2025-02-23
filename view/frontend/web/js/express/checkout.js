define([
  'jquery',
  'Twint_Magento/js/modal/modal',
  'Twint_Magento/js/express/loader',
  'Twint_Magento/js/express/status-refresh',
  'uiRegistry'
], function ($, TwintModal, loader, refresher, uiRegistry) {

  class TwintExpressCheckoutClass {
    constructor($, modal, refresher) {
      this.$ = $;
      this.url = window.checkout.expressCheckoutUrl;
      this.modal = modal;
      this.loader = loader;
      this.refresher = refresher;
    }

    openMiniCart() {
      try {
        this.$('[data-block="minicart"]').find('[data-role="dropdownDialog"]').dropdownDialog("open");
      }catch (e) {
        window.location.href = window.checkout.shoppingCartUrl;
      }
    }

    checkout(formData, onSuccess = null, onError = null) {
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

          if (res.backUrl) {
            window.location = res.backUrl;
          }

          if (res.reload) {
            window.location.reload();
          }

          if (res.showMiniCart) {
            self.openMiniCart();
          }

          if (res.success) {
            self.showQR(res);
          }

          if (typeof onSuccess == 'function') {
            onSuccess(res);
          }
        },

        error: function (res) {
          self.loader.stop();
          console.error("Express checkout error: " + res.responseText);

          if (typeof onError == 'function') {
            onError(res);
          }
        },

        complete: function (res) {
          if (res.state() === 'rejected') {
            console.error("cannot perform express checkout")
          }
        }
      });
    }

    showQR(data) {
      this.refresher.restart();
      this.modal.setStatusRefresher(this.refresher)
      this.modal.init(
        {
          id: data.pairingId,
          token: data.token,
          amount: data.amount,
          modal: data.modal,
          mode: 'express'
        }
      );
      this.modal.open();
    }
  }

  return new TwintExpressCheckoutClass($, TwintModal, refresher);
});
