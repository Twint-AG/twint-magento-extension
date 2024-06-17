define(['Magento_Checkout/js/view/payment/default'], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Twint_Core/payment/customtemplate'
        },

        getCode: function () {
            return 'twint_regular';
        },

        isActive: function () {
            return true;
        },
    });
});
