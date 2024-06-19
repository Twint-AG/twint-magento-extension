define(['Magento_Checkout/js/view/payment/default', 'ko'], function (Component, ko) {
    'use strict';

    return Component.extend({
        redirectAfterPlaceOrder: false,
        defaults: {
            template: 'Twint_Core/payment/customtemplate'
        },

        initialize: function () {
            this._super();
            this.qrTokenValue = ko.observable(null);

            // Retrieve the QR code URL from the payment information
            // var additionalInfo = window.checkoutConfig.payment[this.getCode()].additional_data;
            // if (additionalInfo && additionalInfo.pairingToken) {
            //     this.qrTokenValue(additionalInfo.pairingToken);
            // }
        },

        afterPlaceOrder: function () {
            console.log("after play order")
        },

        getCode: function () {
            return 'twint_regular';
        },

        isActive: function () {
            return true;
        },
    });
});
