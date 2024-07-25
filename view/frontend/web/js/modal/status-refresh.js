define([
  'jquery',
  'mage/storage',
], function ($, storage) {
  class StatusRefresher {
    constructor() {
      this.$ = $;
      this.storage = storage;

      this.count = 0;
    }

    setOnSuccess(onSuccess){
      this.redirectAction = onSuccess;
    }

    setId(value) {
      this.id = value;
    }

    check() {
      const self = this;
      this.count++;
      let serviceUrl = window.checkoutConfig.payment.twint.getPairingStatusUrl + '?id=' + this.id;

      return this.storage.get(serviceUrl).done(
        function (response) {
          if (response.finish === true)
            return self.onPaid();
          return self.onProcessing();
        }
      );
    }

    onPaid() {
      this.redirectAction.execute();
    }

    onProcessing() {
      setTimeout(this.check.bind(this), 5000);
    }
  }


  return StatusRefresher;
});
