define([
  'jquery',
  'mage/storage',
  'Twint_Magento/js/utils/storage',
  'Magento_Customer/js/customer-data',
  'Twint_Magento/js/modal/interval'
], function ($, storage, timeoutStorage, customerData, clock) {
  class ExpressStatusRefresh {
    constructor(storage) {
      this.storage = storage;
      this.customerData = customerData;

      this.url = window.checkout.expressStatusUrl;
      this.cancelUrl = window.checkout.cancelCheckoutUrl;
      this.processing = false;
      this.stopped = false;

      this.onClosedModalCallback = null;
      this.finished = false;
    }

    restart(){
      this.stopped = false;
      this.finished = false;
    }

    setId(value) {
      this.id = value;
    }

    start(){
      clock.begin();
    }

    onProcessing() {
      this.finished = false;
      if (this.processing)
        return;

      this.onClosedModalCallback = null;

      let interval = clock.interval();
      if (interval > 0) {
        setTimeout(this.check.bind(this), interval);
      }
    }

    isInCartPage(){
      return window.location.pathname.indexOf('checkout/cart') !== -1;
    }

    onPaid(response) {
      this.finished = true;
      this.showSuccess(response.order);

      if(this.isInCartPage()) {
        this.onClosedModalCallback = function () {
          location.reload();
        }
      }
    }

    setModal(modal) {
      this.modal = modal;
    }

    onCancelled() {
      this.finished = true;
      this.modal.close();
    }

    onFailed() {
      this.finished = true;

      let modal = $('#qr-modal-content');
      let pay = modal.find('.to-pay');
      let success = modal.find('.on-success');
      let failedDiv = modal.find('.on-failed');

      pay.css('display', 'none');
      success.css('display', 'none');
      failedDiv.css('display', 'block');

      let label = $('#twint-close span');
      label.html($.mage.__('Continue shopping'));
    }

    onFinish(response) {
      let sections = ['cart'];
      this.customerData.invalidate(sections);
      this.customerData.reload(sections, true);

      if (response.finish && response.status > 0) {
        return this.onPaid(response);
      }

      if (response.finish && response.status < 0) {
        if (response.status  === -2){
          return this.onFailed();
        }
        return this.onCancelled();
      }
    }

    check() {
      if (this.stopped)
        return;

      const self = this;
      this.processing = true;

      return timeoutStorage.get(this.url + '?id=' + this.id).done(
        function (response) {
          self.processing = false;

          if (response.finish === true)
            return self.onFinish(response);

          return self.onProcessing();
        }
      ).fail(function(jqXHR, textStatus) {
        if (textStatus === 'timeout') {
          self.check();
        } else {
          console.error('Request failed: ' + textStatus);
          // Handle other errors
        }
      });
    }

    showSuccess(order) {
      let modal = $('#qr-modal-content');
      let pay = modal.find('.to-pay');
      let success = modal.find('.on-success');
      let failedDiv = modal.find('.on-failed');

      pay.css('display', 'none');
      failedDiv.css('display', 'none');
      success.css('display', 'block');

      let span = success.find('span');
      span.html(order);

      let label = $('#twint-close span');
      label.html($.mage.__('Continue shopping'));
    }

    stop() {
      this.stopped = true;

      if(!this.finished) {
        this.cancelPayment();
      }

      if(typeof this.onClosedModalCallback === "function"){
        this.onClosedModalCallback();
      }
    }

    cancelPayment(){
      let serviceUrl = this.cancelUrl + '?id=' + this.id;

      return this.storage.get(serviceUrl).done(
        function (response) {
          if (response.success !== true) {
            console.error("cannot cancel payment");
          }
        }
      );
    }
  }

  return new ExpressStatusRefresh(storage);
});
