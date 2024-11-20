define([
  "jquery",
  'Twint_Magento/js/modal/token-copier',
  'Twint_Magento/js/modal/connector/android-connector',
  'Twint_Magento/js/modal/connector/ios-connector',
  'Twint_Magento/js/modal/qr-generator',
  'Twint_Magento/js/modal/status-refresh',
], function ($, TokenCopier, AndroidConnector, IosConnector, QrGenerator, StatusRefresher ) {

  return {
    statusRefresher: new StatusRefresher(),
    setStatusRefresher: function (refresher){
      this.statusRefresher = refresher;
    },
    setOnSuccess: function (onSuccess){
      this.statusRefresher.setOnSuccess(onSuccess);
    },
    init: function (config) {
      if (!document.getElementById('qr-modal-content')) {
        document.body.insertAdjacentHTML('beforeend', config.modal);
      }

      this.copier = new TokenCopier('qr-token', 'twint-copy');
      this.androidConnector = new AndroidConnector();
      this.iosConnector = new IosConnector();
      this.modal = new QrGenerator();

      this.modal.setValues(config);
      this.modal.init();

      this.copier.reset();
      this.androidConnector.init(config.token);
      this.iosConnector.init(config);

      this.statusRefresher.setId(config.id);
      this.statusRefresher.setModal(this.modal);
      this.statusRefresher.start();
      this.statusRefresher.onProcessing();

      this.modal.setRefresher(this.statusRefresher);
    },
    open: function () {
      this.modal.open();
    },
    close: function (){
      this.modal.close();
    }
  };
});
