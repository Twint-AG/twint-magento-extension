define([
  "jquery",
  'clipboard',
], function ($, Clipboard) {
  class TokenCopier {
    constructor(inputId, buttonId) {
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

  return TokenCopier;
});
