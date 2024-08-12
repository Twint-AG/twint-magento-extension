define([
  'jquery',
  'mage/storage',
  'Magento_Customer/js/customer-data'
], function ($, storage, customerData) {
  class StatusRefresher {
    constructor() {
      this.$ = $;
      this.storage = storage;

      this.count = 0;
      this.stopped = false;
    }

    setOnSuccess(onSuccess){
      this.redirectAction = onSuccess;
    }

    setId(value) {
      this.id = value;
    }

    check() {
      if(this.stopped)
        return;

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
      let sections = ['cart'];
      customerData.invalidate(sections);
      customerData.reload(sections, true);

      this.redirectAction.execute();
    }

    onProcessing() {
      setTimeout(this.check.bind(this), 5000);
    }

    setModal(){

    }

    stop(){
      this.stopped = true;
    }
  }


  return StatusRefresher;
});
