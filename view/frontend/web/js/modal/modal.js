define([
  "jquery",
  'Twint_Magento/js/modal/token-copier',
  'Twint_Magento/js/modal/connector/android-connector',
  'Twint_Magento/js/modal/connector/ios-connector',
  'Twint_Magento/js/modal/qr-generator',
  'Twint_Magento/js/modal/status-refresh',
], function ($, TokenCopier, AndroidConnector, IosConnector, QrGenerator, StatusRefresher ) {

  return {
    copier: new TokenCopier('qr-token', 'twint-copy'),
    androidConnector: new AndroidConnector(),
    iosConnector: new IosConnector(),
    modal: new QrGenerator(),
    statusRefresher: new StatusRefresher(),
    setStatusRefresher: function (refresher){
      this.statusRefresher = refresher;
    },
    setOnSuccess: function (onSuccess){
      this.statusRefresher.setOnSuccess(onSuccess);
    },
    init: function (config) {
      this.modal.setValues(config);
      this.modal.init();

      this.copier.reset();
      this.androidConnector.init(config.token);
      this.iosConnector.init(config);

      this.statusRefresher.setId(config.id);
      this.statusRefresher.setModal(this.modal);
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
