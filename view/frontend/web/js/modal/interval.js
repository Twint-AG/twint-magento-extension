define([
], function () {
  class TwintInterval {
    constructor(stages) {
      this.stages = stages;
      this.start = null;
    }

    begin(){
      this.start = new Date();
    }

    interval() {
      let now = new Date();
      const seconds = Math.floor((now - this.start) / 1000);

      let currentInterval = 1000; // Default to the first interval

      for (const [second, interval] of Object.entries(this.stages)) {
        if (seconds >= parseInt(second)) {
          currentInterval = interval;
        } else {
          break;
        }
      }

      return currentInterval;
    }
  }

  return new TwintInterval({
    0: 5000,
    5: 2000,
    600: 10000, //10 mins
    3600: 0 // 1 hour
  });
});
