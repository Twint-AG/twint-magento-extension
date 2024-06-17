class TwintCertificateUpload {
    constructor($, $t) {
        this.$ = $;
        this.$t = $t;

        this.fileInput = document.getElementById('certificate-file');
        this.fileNameLabel = document.getElementById('certificate-file-name');
        this.inputContainer = document.getElementById('twint-certificate-input-container');
        this.loadedContainer = document.getElementById('twint-certificate-loaded-container');
        this.hiddenInput = document.getElementById('twint_credential_certificate');
        this.uploadNewLabel = document.getElementById('twint-upload-new');
        this.passwordInput = document.getElementById('certificate-password');
        this.errorContainer = document.getElementById('certificate-error-container');
        this.merchantInput = document.getElementById('twint_credential_merchantID');
        this.envSelect = document.getElementById('twint_credential_environment');

        this.saveButton = document.getElementById('save');

        this.testing = false;
    }

    init() {
        this.cloneSaveButton();
        this.registerEvents();
        this.showContainers();
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
            url: this.baseUrl() + '/index.php/admin/twint/certificate/upload',
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

    baseUrl() {
        let fullUrl = window.location.href;

        // Create a new URL object
        let url = new URL(fullUrl);

        // Extract the protocol, hostname, and port (if any) to form the base URL
        return url.protocol + "//" + url.hostname + (url.port ? ':' + url.port : '');
    }

    isValidUUIDv4(uuid) {
        // Regular expression to match UUID v4 format
        var uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

        // Check if the string matches the UUID v4 format
        return uuidRegex.test(uuid);
    }

    validateMerchantId() {
        if (!this.merchantError) {
            const label = document.createElement('label');

            // Set the attributes
            label.id = 'twint_credential_merchantID-error';
            label.className = 'mage-error';
            label.setAttribute('for', 'twint_credential_merchantID');

            // Set the inner text
            label.textContent = this.$t('Invalid Merchant ID. Merchant ID needs to be a UUIDv4');

            this.merchantInput.insertAdjacentElement('afterend', label);
            this.merchantError = label;
        }

        let merchantId = this.merchantInput.value;
        if (!this.isValidUUIDv4(merchantId)) {
            this.show(this.merchantError);
            return false;
        }

        this.hide(this.merchantError);

        return true;
    }

    validateCredential() {
        if (!this.validateMerchantId()) {
            return;
        }

        this.testing = true;
        this.clonedSaveButton.innerHTML = '<span>' + this.$t('Validating credentials') + '</span>';
        this.$.ajax({
            url: this.baseUrl() + '/index.php/admin/twint/credential/validation', type: 'POST', data: {
                certificate: JSON.parse(this.hiddenInput.value),
                testMode: this.envSelect.value,
                merchantId: this.merchantInput.value,
                form_key: window.FORM_KEY
            }, showLoader: true,
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
            const containerDiv = document.getElementById('twint_credential');
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
    }
}

define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    const twintUpload = new TwintCertificateUpload($, $t);
    twintUpload.init();
});
