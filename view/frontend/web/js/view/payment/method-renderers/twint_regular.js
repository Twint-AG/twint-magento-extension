define(['jquery','Magento_Checkout/js/view/payment/default', 'ko', 'qr-modal'], function ($,Component, ko, QrModal) {
    'use strict';

    return Component.extend({
        redirectAfterPlaceOrder: false,
        defaults: {
            template: 'Twint_Core/payment/twint'
        },

        initialize: function () {
            this._super();
        },

        afterPlaceOrder: function () {
            console.log("after play order")
            QrModal.initModal({ target: '#qr-modal-content' });
            QrModal.openModal();
        },

        showModal: function (){
            QrModal.initModal(
                {
                    target: '#qr-modal-content',
                    token: 122342,
                    amount: 'CHF 23.92'
                }
            );
            QrModal.openModal();
        },

        getCode: function () {
            return 'twint_regular';
        },

        getInstruction: function (){
            return $.mage.__('Payment method supported by TWINT');
        },

        isActive: function () {
            return true;
        },

        getLogoUrl: function () {
            return require.toUrl('Twint_Core/images/twint.svg');
        },

        getExpressUrl: function (){
            return require.toUrl('Twint_Core/images/express.svg');
        },

        continueToTwint: function () {
            if (additionalValidators.validate()) {
                //update payment method information if additional data was changed
                setPaymentMethodAction(this.messageContainer).done(
                    function () {
                        customerData.invalidate(['cart']);
                        $.mage.redirect(
                            window.checkoutConfig.payment.paypalExpress.redirectUrl[quote.paymentMethod().method]
                        );
                    }
                );

                return false;
            }
        }
    });
});
