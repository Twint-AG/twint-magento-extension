define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Twint_Magento/payment/customtemplate'
            },
            getCode: function () {
                return 'twint_express';
            },

            isActive: function () {
                return true;
            },
        });
    }
);
