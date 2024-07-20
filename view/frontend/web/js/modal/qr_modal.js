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
class Connector{
  constructor($) {
    this.$ = $;
  }

  showMobileQrCode() {
    let blocks = document.querySelectorAll('.default-hidden');

    blocks.forEach(block => {
      block.classList.remove('hidden');
    });
  }
}

class AndroidConnector extends Connector{
  constructor($) {
    super($);

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

class IosConnector extends Connector{
  constructor($) {
    super($);

    this.container = document.getElementById('twint-ios-container');
  }

  init(values) {
    this.values = values;

    if (!this.container)
      return;

    this.banks = this.container.querySelectorAll('img');
    if (this.banks) {
      this.banks.forEach((bank) => {
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
      link = link.replace('--TOKEN--', this.values.token);

      try {
        window.location.replace(link);

        const checkLocation = setInterval(() => {
          if (window.location.href !== link) {
            this.showMobileQrCode();
          }
          clearInterval(checkLocation);
        }, 2000);
      } catch (e) {
        this.showMobileQrCode();
      }
    }
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

  getElements(){
    this.guideApp = document.getElementById('twint-guide-app');
    this.guideContact = document.getElementById('twint-guide-contact');
  }

  hideGuide(){
    if(this.values.mode === 'regular'){
      this.guideApp.classList.remove('hidden');
      this.guideContact.classList.add('hidden');
    }else {
      this.guideContact.classList.remove('hidden');
      this.guideApp.classList.add('hidden');
    }
  }

  init() {
    this.getElements();
    this.hideGuide();
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

class StatusRefresher {
  constructor($, storage) {
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

define([
  "jquery",
  'text!Twint_Magento/template/modal/qr-modal-popup.html',
  'clipboard',
  'mage/storage',
  "Magento_Ui/js/modal/modal",
  'qrcodejs',
], function ($, template, Clipboard, storage) {

  return {
    copier: new CopyToken($, 'qr-token', 'twint-copy', Clipboard),
    androidConnector: new AndroidConnector($),
    iosConnector: new IosConnector($),
    modal: new QrGenerator($, template),
    statusRefresher: new StatusRefresher($, storage),
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
      this.statusRefresher.onProcessing();
    },
    open: function () {
      this.modal.open();
    }
  };
});
