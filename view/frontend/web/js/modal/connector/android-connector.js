define([
  'Twint_Magento/js/modal/connector/connector'
], function (Connector) {
  class AndroidConnector extends Connector{
    constructor() {
      super();
      this.button = document.getElementById('twint-addroid-button');
    }

    init(token) {
      this.token = token;
      if (!this.button)
        return;

      this.button.href = this.button.getAttribute('data-href').replace('--TOKEN--', this.token);

      this.button.click();
      this.showMobileQrCode();
    }
  }

  return AndroidConnector;
});
