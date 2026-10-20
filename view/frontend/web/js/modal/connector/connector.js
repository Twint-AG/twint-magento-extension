define([], function () {
  class Connector{
    constructor(){

    }

    showMobileQrCode() {
      let blocks = document.querySelectorAll('.default-hidden');

      blocks.forEach(block => {
        block.classList.remove('hidden');
      });
    }
  }

  return Connector;
});
