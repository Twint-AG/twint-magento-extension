define([
], function () {
  class TwintLoader{
    constructor() {
      this.element = document.getElementById('twint-loading');
    }

    start(){
      this.element.classList.add('active');
    }

    stop(){
      this.element.classList.remove('active');
    }
  }

  return new TwintLoader();
});
