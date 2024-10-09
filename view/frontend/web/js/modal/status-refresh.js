define([
  'jquery',
  'mage/storage',
  'Magento_Customer/js/customer-data',
  'Twint_Magento/js/modal/interval'
], function ($, storage, customerData, clock) {
  class StatusRefresher {
    constructor() {
      this.$ = $;
      this.storage = storage;

      this.count = 0;
      this.stopped = false;
      this.finished = false;
    }

    restart(){
      this.finished = false;
      this.stopped = false;
    }

    start(){
      this.restart();
      clock.begin();
    }

    setOnSuccess(onSuccess) {
      this.redirectAction = onSuccess;
    }

    setId(value) {
      this.id = value;
    }

    check(oneTime = false) {
      if (this.stopped && !oneTime)
        return;

      const self = this;
      this.count++;
      let serviceUrl = window.checkoutConfig.payment.twint.getPairingStatusUrl + '?id=' + this.id;

      return this.storage.get(serviceUrl).done(
        function (response) {
          if (response.finish === true) {
            return response.paid ? self.onPaid() : self.onCancelled();
          }
          return !oneTime && self.onProcessing();
        }
      );
    }

    cancelPayment(){
      const self = this;
      let serviceUrl = window.checkoutConfig.payment.twint.getCancelPaymentUrl + '?id=' + this.id;

      return this.storage.get(serviceUrl).done(
        function (response) {
          if (response.success !== true) {
            self.check(true);
          }
        }
      );
    }

    onPaid() {
      this.finished = true;
      this.redirectAction.execute();
    }

    onProcessing() {
      this.finished = false;
      let interval = clock.interval();

      if (interval > 0) {
        setTimeout(this.check.bind(this), interval);
      }
    }

    setModal(modal) {
      this.modal = modal;
    }

    onCancelled() {
      this.finished = true;
      this.modal.close();
    }

    stop() {
      this.stopped = true;

      if(!this.finished) {
        this.cancelPayment();
      }
    }
  }


  return StatusRefresher;
});
