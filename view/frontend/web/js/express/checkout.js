define([
  'jquery',
  'Twint_Magento/js/modal/modal',
  'Twint_Magento/js/express/loader',
  'Twint_Magento/js/express/status-refresh'
], function ($, TwintModal, loader, refresher) {

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
        this.$('.showcart').click();
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
          console.log("Express checkout error: " + res.responseText);

          if (typeof onError == 'function') {
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
      this.modal.setStatusRefresher(this.refresher)
      this.modal.init(
        {
          id: data.pairingId,
          token: data.token,
          amount: data.amount,
          mode: 'express'
        }
      );
      this.modal.open();
    }
  }

  return new TwintExpressCheckoutClass($, TwintModal, refresher);
});
