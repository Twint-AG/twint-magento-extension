define([
  'Twint_Magento/js/express/points'
], function (points) {
  class TwintLoader{
    static animation = null;
    static index = 0;
    static container = document.getElementById('twintAnimation');

    constructor() {
      this.element = document.getElementById('tw-loading')
    }

    start(){
      this.element.classList.add('active');

      if(!TwintLoader.animation){
        TwintLoader.animation = setInterval(this.changePoints.bind(this), 20);
      }
    }

    stop(){
      this.element.classList.remove('active');

      if (TwintLoader.animation) {
        clearInterval(TwintLoader.animation);
        TwintLoader.animation = null;
      }
    }

    changePoints() {
      TwintLoader.container.setAttribute('d', String(points[TwintLoader.index]));

      TwintLoader.index++;
      if (TwintLoader.index >= points.length) {
        TwintLoader.index = 0;
      }
    }
  }

  return new TwintLoader();
});
