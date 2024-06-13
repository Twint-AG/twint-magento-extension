class TwintCertificateUpload {
    constructor($) {
        this.$ = $;
        this.fileInput = document.getElementById('certificate-file');
        this.fileNameLabel = document.getElementById('certificate-file-name');
        this.inputContainer = document.getElementById('twint-certificate-input-container');
        this.loadedContainer = document.getElementById('twint-certificate-loaded-container');
        this.hiddenInput = document.getElementById('twint_general_credential_certificate');
        this.uploadNewLabel = document.getElementById('twint-upload-new');
        this.passwordInput = document.getElementById('certificate-password');
        this.errorContainer = document.getElementById('certificate-error-container');
        this.merchantInput = document.getElementById('twint_general_credential_merchantID');
        this.envSelect = document.getElementById('twint_general_credential_environment');

        this.saveButton = document.getElementById('save');
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

    showMerchantIdError() {
        if (!this.merchantError) {

            const label = document.createElement('label');

            // Set the attributes
            label.id = 'twint_general_credential_merchantID-error';
            label.className = 'mage-error';
            label.setAttribute('for', 'twint_general_credential_merchantID');

            // Set the inner text
            label.textContent = 'This is a required field.';

            this.merchantInput.insertAdjacentElement('afterend', label);
            this.merchantError = label;
        } else {
            this.show(this.merchantError);
        }
    }

    validateCredential() {
        let merchantId = this.merchantInput.value;
        if (!this.isValidUUIDv4(merchantId)) {
            this.showMerchantIdError();
            return;
        }

        this.hide(this.merchantError);

        this.$.ajax({
            url: this.baseUrl() + '/index.php/admin/twint/credential/validation',
            type: 'POST',
            data: {
                certificate: JSON.parse(this.hiddenInput.value),
                testMode: this.envSelect.value,
                merchantId: this.merchantInput.value
            },
            contentType: false,
            processData: false,
            showLoader: true,
            success: function (data) {

            },
            error: this.handleError.bind(this)
        });
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
    }
}

define(['jquery', 'domReady!'], function ($) {
    'use strict';

    const twintUpload = new TwintCertificateUpload($);
    twintUpload.init();
});
