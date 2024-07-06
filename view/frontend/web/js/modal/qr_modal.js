class CopyToken {
  constructor($, inputId, buttonId, Clipboard) {
    this.$ = $;

    this.input = document.getElementById(inputId);
    this.button = document.getElementById(buttonId);

    this.button.addEventListener('click', this.onClick.bind(this));


    this.clipboard = new Clipboard('#' + buttonId);
    this.clipboard.on('success', this.onCopied.bind(this));
    this.clipboard.on('error', this.onError.bind(this));
  }

  onClick(event) {
    event.preventDefault();
    this.input.disabled = false;
  }

  onCopied(e) {
    e.clearSelection();
    this.button.innerHTML = this.$.mage.__('Copied!')
    this.button.classList.add('copied');
    this.button.classList.add('border-green-500');
    this.button.classList.add('text-green-500');
    this.input.disabled = true
  }

  onError(e) {
    console.error('Action:', e.action);
    console.error('Trigger:', e.trigger);
  }

  reset() {
    this.button.innerHTML = this.$.mage.__('Copy code')
    this.button.classList.remove('copied');
    this.button.classList.remove('border-green-500');
    this.button.classList.remove('text-green-500');
  }
}

class AndroidConnector {
  constructor($, buttonId) {
    this.$ = $;

    this.button = document.getElementById(buttonId);
  }

  init(token) {
    if (!this.button)
      return;

    this.button.href = this.button.href.replace('--TOKEN--', token);

    this.button.click();
    this.showMobileQrCode();
  }

  showMobileQrCode(){
    let blocks = document.querySelectorAll('.default-hidden');

    blocks.forEach(block => {
      block.classList.remove('hidden');
    });
  }
}

class QrGenerator {
  constructor($, template) {
    this.$ = $;
    this.template = template;
  }

  setValues(values) {
    this.values = values;
  }

  options() {
    return {
      type: 'popup',
      innerScroll: true,
      responsive: true,
      popupTpl: this.template,
      clickableOverlay: false,
      twintLogo: require.toUrl('Twint_Magento/images/twint_logo.png'),
      modalClass: 'twint-modal-slide',
      closeText: this.$.mage.__('Cancel checkout'),
      closed: function () {
        console.log("Closed");
      }
    };
  }

  init() {
    this.$target = this.$('#qr-modal-content');

    let qr = document.getElementById("qrcode");
    qr.innerHTML = '';

    new QRCode(qr, {
      text: this.values.token,
      width: 300,
      height: 300,
      colorDark: "#000000",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H
    });

    this.$('#twint-amount').html(this.values.amount);
    this.$('#qr-token').val(this.values.token);

    this.$target.modal(this.options());
  }

  open() {
    this.$target.modal('openModal');
  }
}

class IosConnector {
  constructor($) {
    this.$ = $;

    this.container = document.getElementById('twint-ios-container');
  }

  init(values) {
    if (!this.container)
      return;

    this.banks = this.container.querySelectorAll('img');
    if (this.banks) {
      this.banks.forEach((bank) => {
        let link = bank.getAttribute('data-link').replace('--TOKEN--', values.token);
        bank.setAttribute('data-link', link);

        bank.addEventListener('touchend', (event) => {
          this.onClickBank(event, bank);
        });
      });
    }

    this.$appLinks = this.container.querySelector('select');
    if (this.$appLinks)
      this.$appLinks.addEventListener('change', this.onChangeAppList.bind(this))
  }

  onChangeAppList(event) {
    const select = event.target;
    let link = select.options[select.selectedIndex].value;
    this.openAppBank(link);
  }

  onClickBank(event, bank) {
    var link = bank.getAttribute('data-link');
    this.openAppBank(link);
  }

  openAppBank(link) {
    if (link) {
      try {
        window.location.replace(link);

        const checkLocation = setInterval(() => {
          if (window.location.href !== link) {
            this.showMobileQrCode();
          }
          clearInterval(checkLocation);
        }, 5000);
      } catch (e) {
        this.showMobileQrCode();
      }
    }
  }

  showMobileQrCode(){
    let blocks = document.querySelectorAll('.default-hidden');

    blocks.forEach(block => {
      block.classList.remove('hidden');
    });
  }
}

class StatusRefresher {
  constructor($, storage, redirectOnSuccess) {
    this.$ = $;
    this.storage = storage;
    this.redirectAction = redirectOnSuccess;

    this.count = 0;
  }

  setId(value){
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

define([
  "jquery",
  'text!Twint_Magento/template/modal/qr-modal-popup.html',
  'clipboard',
  'mage/storage',
  'Magento_Checkout/js/action/redirect-on-success',

  "Magento_Ui/js/modal/modal",
  'qrcodejs',
], function ($, template, Clipboard, storage, redirectOnSuccess) {

  return {
    copier: new CopyToken($, 'qr-token', 'btn-copy-token', Clipboard),
    androidConnector: new AndroidConnector($, 'twint-addroid-button'),
    iosConnector: new IosConnector($),
    modal: new QrGenerator($, template),
    statusRefresher: new StatusRefresher($, storage, redirectOnSuccess),
    init: function (config) {
      this.modal.setValues(config);
      this.modal.init();

      this.copier.reset();
      this.androidConnector.init(config.token);
      this.iosConnector.init(config);

      this.statusRefresher.setId(config.id);
      this.statusRefresher.onProcessing();
    },
    open: function () {
      this.modal.open();
    }
  };
});
