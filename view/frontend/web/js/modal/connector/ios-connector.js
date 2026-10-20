define([
  'Twint_Magento/js/modal/connector/connector'
], function (Connector) {
  class IosConnector extends Connector{
    constructor() {
      super();

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

      this.appLinksElements = this.container.querySelector('select');

      if (this.appLinksElements) {
        this.appLinksElements.selectedIndex = 0;
        this.appLinksElements.addEventListener('change', this.onChangeAppList.bind(this));
      }

      this.showMobileQrCode();
    }

    onChangeAppList(event) {
      const select = event.target;
      let link = select.options[select.selectedIndex].value;

      this.openAppBank(link);
    }

    onClickBank(event, bank) {
      const link = bank.getAttribute('data-link');
      this.openAppBank(link);
    }

    openAppBank(link) {
      if (link) {
        link = link.replace('--TOKEN--', this.values.token);

        if(this.inIframe()){
          // Try to bubble up to the top window
          try {
            window.top.location.replace(link);
          } catch (e) {
            // Fallback: ask parent to redirect via postMessage
            window.parent.postMessage({ action: "openTwint", link }, "*");
          }

          return;
        }

        try {
          window.location.replace(link);

          const checkLocation = setInterval(() => {
            if (window.location.href !== link) {
              this.showMobileQrCode();
            }
            clearInterval(checkLocation);
          }, 2000);
        } catch (e) {

        }
      }
    }

    inIframe() {
      try {
        return window.self !== window.top;
      } catch (e) {
        // Cross-origin access throws an error → we know we're in an iframe
        return true;
      }
    }

  }

  return IosConnector;
});
