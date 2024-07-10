class TwintBase {
  constructor($, $t, urlBuilder) {
    this.$ = $;
    this.$t = $t;
    this.urlBuilder = urlBuilder;

    this.urlBuilder.setBaseUrl(this.baseUrl());

    this.getElements();
  }

  getElements() {
  }

  baseUrl() {
    let fullUrl = window.location.href;

    // Create a new URL object
    let url = new URL(fullUrl);

    // Extract the protocol, hostname, and port (if any) to form the base URL
    return url.protocol + "//" + url.hostname + (url.port ? ':' + url.port : '');
  }
}

class TwintConfigInherit extends TwintBase {
  getElements() {
    this.merchantIdCheckbox = document.getElementById('twint_credentials_merchantID_inherit');
    this.envCheckbox = document.getElementById('twint_credentials_environment_inherit');
    this.certCheckbox = document.getElementById('twint_credentials_certificate_inherit');
  }

  init() {
    if (this.canInherit()) {
      this.getInheritValues();
    }
  }

  canInherit() {
    return this.merchantIdCheckbox || this.envCheckbox || this.certCheckbox;
  }

  isCertificateInherited() {
    return this.certCheckbox && this.certCheckbox.checked;
  }

  getScope() {
    let label = document.querySelector(`label[for="twint_credentials_merchantID_inherit"]`);
    if (label) {
      return label.innerText === this.$t("Use Default") ? '' : 'websites';
    }
    return '';
  }

  getFinalValues(current) {
    if (!this.values) {
      return current;
    }

    if (this.merchantIdCheckbox.checked) {
      current['merchantId'] = this.values.merchant_id;
    }

    if (this.certCheckbox.checked) {
      current['certificate'] = JSON.parse(this.values.certificate);
    }

    if (this.envCheckbox.checked) {
      current['testMode'] = this.values.test_mode;
    }

    return current;
  }

  getInheritValues() {
    this.$.ajax({
      url: this.urlBuilder.build('/index.php/admin/twint/credentials/values'),
      type: 'POST',
      data: {
        scope: this.getScope(),
        form_key: window.FORM_KEY
      },
      success: (function (data) {
        this.values = data;
      }).bind(this),
      error: (function () {
        this.values = null;
      }).bind(this)
    });
  }
}

class TwintCertificateHandler extends TwintBase {
  getElements() {
    this.fileInput = document.getElementById('certificate-file');
    this.fileNameLabel = document.getElementById('certificate-file-name');
    this.inputContainer = document.getElementById('twint-certificate-input-container');
    this.loadedContainer = document.getElementById('twint-certificate-loaded-container');
    this.hiddenInput = document.getElementById('twint_credentials_certificate');
    this.uploadNewLabel = document.getElementById('twint-upload-new');
    this.passwordInput = document.getElementById('certificate-password');
    this.errorContainer = document.getElementById('certificate-error-container');
    this.merchantInput = document.getElementById('twint_credentials_merchantID');
    this.envSelect = document.getElementById('twint_credentials_environment');

    this.saveButton = document.getElementById('save');
  }

  init() {
    this.testing = false;
    this.inherit = new TwintConfigInherit(this.$, this.$t, this.urlBuilder);
    this.inherit.init();

    this.merchantInput.setAttribute('placeholder', 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx');
    this.cloneSaveButton();
    this.registerEvents();
    this.showContainers();

    this.disableInputs();
  }

  cloneSaveButton() {
    this.clonedSaveButton = this.saveButton.cloneNode(true);
    this.clonedSaveButton.id = 'validate-button';
    this.clonedSaveButton.removeAttribute('data-ui-id');
    this.clonedSaveButton.removeAttribute('backend-button-widget-hook-id');

    this.saveButton.parentNode.insertBefore(this.clonedSaveButton, this.saveButton.nextSibling);
    this.hide(this.saveButton);
  }

  updateFileLabel(event) {
    let fileName = event.target.files.length ? event.target.files[0].name : '';

    if (fileName === '') {
      fileName = this.fileNameLabel.getAttribute('data-empty-title');
    }

    this.fileNameLabel.innerHTML = fileName;
  }

  uploadCertificate() {
    let password = this.passwordInput.value;
    let file = this.fileInput.value;
    if (password.trim() === '' || file.trim() === '') {
      return;
    }

    this.hide(this.errorContainer);

    // Get the FORM_KEY from the HTML element
    let formKey = window.FORM_KEY;
    if (!formKey) {
      alert('FORM_KEY is missing. Please refresh the page and try again.');
      return;
    }

    let formData = new FormData();
    formData.append('file', this.fileInput.files[0]);
    formData.append('password', password.trim());
    formData.append('form_key', formKey);

    this.$.ajax({
      url: this.urlBuilder.build('/index.php/admin/twint/certificate/upload'),
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false,
      showLoader: true,
      success: this.handleSuccess.bind(this),
      error: this.handleError.bind(this)
    });
  }

  handleSuccess(data) {
    if (data.success) {
      this.hiddenInput.value = JSON.stringify(data.data);
      this.hide(this.errorContainer);
      this.showContainers();
    } else {
      this.errorContainer.innerText = data.message;
      this.show(this.errorContainer);
    }
  }

  handleError(hrx, text, throwable) {
    console.log("Upload Certificate error: " + text);
  }

  isValidUUIDv4(uuid) {
    // Regular expression to match UUID v4 format
    var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

    // Check if the string matches the UUID v4 format
    return uuidRegex.test(uuid);
  }

  validateMerchantId(value) {
    if (!this.merchantError) {
      const label = document.createElement('label');

      // Set the attributes
      label.id = 'twint_credentials_merchantID-error';
      label.className = 'mage-error';
      label.setAttribute('for', 'twint_credentials_merchantID');

      // Set the inner text
      label.textContent = this.$t('Invalid Merchant ID. Merchant ID needs to be a UUIDv4');

      this.merchantInput.insertAdjacentElement('afterend', label);
      this.merchantError = label;
    }

    if (!this.isValidUUIDv4(value)) {
      this.show(this.merchantError);
      return false;
    }

    this.hide(this.merchantError);

    return true;
  }

  validateCredential() {
    let finalValues = this.inherit.getFinalValues({
      certificate: JSON.parse(this.hiddenInput.value),
      testMode: this.envSelect.value,
      merchantId: this.merchantInput.value,
      form_key: window.FORM_KEY
    });

    if (!this.validateMerchantId(finalValues.merchantId)) {
      return;
    }

    this.testing = true;
    this.clonedSaveButton.innerHTML = '<span>' + this.$t('Validating credentials') + '</span>';
    this.$.ajax({
      url: this.urlBuilder.build('/index.php/admin/twint/credentials/validation'),
      type: 'POST',
      data: finalValues,
      showLoader: true,
      success: (function (data) {
        this.testing = false;
        this.clonedSaveButton.innerHTML = this.saveButton.innerHTML;

        if (data.success) {
          this.clonedSaveButton.innerHTML = '<span>' + this.$t('Saving...') + '</span>';
          this.saveButton.click();
        } else {
          this.showValidationError(data.message);
        }
      }).bind(this), error: (function () {
        this.testing = false;
      }).bind(this)
    });
  }

  showValidationError(message) {
    if (!this.errorSummary) {
      const containerDiv = document.getElementById('twint_credentials');
      const table = containerDiv.querySelector('table');

      // Create the outer div element with class "messages"
      const errorSummary = document.createElement('div');
      errorSummary.className = 'messages';

      // Create the inner div element with class "message message-success success"
      const innerDiv = document.createElement('div');
      innerDiv.className = 'message message-error error';

      // Set the text content of the inner div
      innerDiv.textContent = message;

      // Append the inner div to the outer div
      errorSummary.appendChild(innerDiv);

      // Insert the outer div into the container div
      containerDiv.insertBefore(errorSummary, table);
      this.errorSummary = errorSummary;
    }
  }

  onFileChanged(event) {
    this.updateFileLabel(event);

    this.uploadCertificate();
  }

  onStartValidate(event) {
    event.preventDefault();
    event.stopImmediatePropagation();

    this.validateCredential();

    return false;
  }

  onChangeCertificate() {
    if (this.inherit.isCertificateInherited())
      return;

    this.hiddenInput.value = '{}';
    this.showContainers(true);
  }

  showContainers(force = false) {
    let certificate = this.hiddenInput.value;

    if (force || certificate === '') {
      this.show(this.inputContainer);
      this.hide(this.loadedContainer);
    } else {
      this.hide(this.inputContainer);
      this.show(this.loadedContainer);
    }
  }

  show(element) {
    element.style.display = 'block';
  }

  hide(element) {
    element.style.display = 'none';
  }

  registerEvents() {
    this.fileInput.addEventListener('change', this.onFileChanged.bind(this));
    this.uploadNewLabel.addEventListener('click', this.onChangeCertificate.bind(this));
    this.clonedSaveButton.addEventListener('click', this.onStartValidate.bind(this), true);
    this.passwordInput.addEventListener('change', this.uploadCertificate.bind(this));
    this.merchantInput.addEventListener('change', this.validateMerchantId.bind(this));

    if (this.inherit.certCheckbox)
      this.inherit.certCheckbox.addEventListener('change', this.disableInputs.bind(this));
  }

  disableInputs() {
    if (this.inherit.isCertificateInherited()) {
      this.fileInput.setAttribute('disabled', 'disabled');
      this.passwordInput.setAttribute('disabled', 'disabled');
      this.uploadNewLabel.classList.add('disabled');
    } else {
      this.fileInput.removeAttribute('disabled');
      this.passwordInput.removeAttribute('disabled');
      this.uploadNewLabel.classList.remove('disabled');
    }
  }
}

define(['jquery', 'mage/translate', 'mage/url'], function ($, $t, urlBuilder) {
  'use strict';

  const handler = new TwintCertificateHandler($, $t, urlBuilder);
  handler.init();
});
