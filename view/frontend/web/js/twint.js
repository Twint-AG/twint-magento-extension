class ExpressCheckout{
  constructor($, modal){
    this.$ = $;
    this.modal = modal;
  }

  init(){
    // this.getButtons();
    // this.registerEvents();
  }

  showModal() {
    this.modal.init(
      {
        token: 122342,
        amount: 'CHF 23.92'
      }
    );
    this.modal.open();
  }

  getButtons() {
    this.buttons = document.querySelectorAll('.twint-button');
  }

  registerEvents() {
    if(this.buttons.length > 0){
      for (let button of this.buttons){
        button.addEventListener('click', this.showModal.bind(this));
      }
    }
  }
}

define([
  'jquery', 'Twint_Magento/js/modal/qr_modal',
], function ($, modal) {
  'use strict';

  console.log("loaded");
  $(document).ready(function () {
    (new ExpressCheckout($, modal)).init();
  });
});
