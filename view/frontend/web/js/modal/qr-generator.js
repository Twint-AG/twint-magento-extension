define([
  'jquery',
  'text!Twint_Magento/template/modal/qr-modal-popup.html',
  'qrcodejs',
  "Magento_Ui/js/modal/modal"
], function ($, template) {
  class QrGenerator {
    constructor() {
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
      this.showPaySection();

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

    showPaySection(){
      let pay = this.$target.find('.to-pay');
      let success = this.$target.find('.on-success');

      pay.css('display', 'block');
      success.css('display', 'none');

      let label = $('#twint-close span');
      label.html($.mage.__('Cancel checkout'));
    }

    open() {
      this.$target.modal('openModal');
    }

    close() {
      this.$target.modal('closeModal');
    }
  }

  return QrGenerator;
});
