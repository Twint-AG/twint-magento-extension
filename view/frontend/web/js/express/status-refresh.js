define([
  'jquery',
  'mage/storage',
  'Magento_Customer/js/customer-data'
], function ($, storage, customerData) {
    class ExpressStatusRefresh {
      constructor(storage) {
        this.storage = storage;
        this.customerData = customerData;

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

      onPaid(response) {
        let sections = ['cart'];
        this.customerData.invalidate(sections);
        this.customerData.reload(sections, true);

        this.showSuccess(response.order);
      }

      check() {
        const self = this;
        this.processing = true;

        return this.storage.get(this.url + '?id=' + this.id).done(
          function (response) {
            self.processing = false;

            if (response.finish === true)
              return self.onPaid(response);

            return self.onProcessing();
          }
        );
      }

      showSuccess(order){
        let modal = $('#qr-modal-content');
        let pay = modal.find('.to-pay');
        let success = modal.find('.on-success');

        pay.css('display', 'none');
        success.css('display', 'block');

        let span = success.find('span');
        span.html(order);

        let label = $('#twint-close span');
        label.html($.mage.__('Continue shopping'));
      }
    }

    return new ExpressStatusRefresh(storage);
});
