class CopyToken {
  constructor(inputId, buttonId, Clipboard) {
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
    this.button.innerHTML = 'Copied!';
    this.button.classList.add('copied');
    this.input.disabled = true
  }

  onError(e) {
    console.error('Action:', e.action);
    console.error('Trigger:', e.trigger);
  }
}

define([
  "jquery",
  'text!Twint_Core/template/modal/qr-modal-popup.html',
  'clipboard',
  "Magento_Ui/js/modal/modal",
  'qrcodejs',
], function ($, template, Clipboard) {
  let options = {
    type: 'popup',
    innerScroll: true,
    responsive: true,
    popupTpl: template,
    title: $.mage.__('TWINT'),
    clickableOverlay: false,
    twintLogo: require.toUrl('Twint_Core/images/twint_logo.png'),
    modalClass: 'twint-modal-slide',
    closeText: $.mage.__('Cancel checkout'),
    closed: function () {
      console.log("Closed");
    }
  }

  return {
    copier: new CopyToken('qr-token', 'btn-copy-token', Clipboard),

    initModal: function (config) {
      this.$target = $(config.target);

      let qr = document.getElementById("qrcode");
      qr.innerHTML = '';
      new QRCode(qr, {
        text: config.token,
        width: 300,
        height: 300,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
      });

      $('#twint-amount').html(config.amount);
      $('#qr-token').val(config.token);

      this.$target.modal(options);
    },
    openModal: function () {
      if (this.$target) {
        this.$target.modal('openModal');
      } else {
        console.error('Modal is not initialized.');
      }
    }
  };
});
