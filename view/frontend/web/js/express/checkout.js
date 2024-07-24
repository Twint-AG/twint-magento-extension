define([
  'jquery',
  'Twint_Magento/js/modal/qr_modal',
  'mage/storage',
  'Twint_Magento/js/express/loader'
], function ($, TwintModal, storage, loader) {
  class ExpressStatusRefresh {
    constructor($, storage) {
      this.$ = $;
      this.storage = storage;

      this.url = window.checkout.expressStatusUrl;
      this.processing = false;
    }

    setId(value) {
      this.id = value;
    }

    onProcessing() {
      if (this.processing)
        return;
      setTimeout(this.check.bind(this), 5000);
    }

    onPaid() {
      alert("Order is created success. Check in admin")
    }

    check() {
      const self = this;
      this.processing = true;

      return this.storage.get(this.url + '?id=' + this.id).done(
        function (response) {
          self.processing = false;

          if (response.finish === true)
            return self.onPaid();

          return self.onProcessing();
        }
      );
    }
  }

  class TwintExpressCheckoutClass {
    constructor($, modal, storage) {
      this.$ = $;
      this.url = window.checkout.expressCheckoutUrl;
      this.modal = modal;
      this.storage = storage;
      this.loader = loader;
    }

    openMiniCart() {
      this.$('[data-block="minicart"]').find('[data-role="dropdownDialog"]').dropdownDialog("open");
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
      this.modal.setStatusRefresher(new ExpressStatusRefresh(this.$, this.storage))
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

  return new TwintExpressCheckoutClass($, TwintModal, storage);
});
