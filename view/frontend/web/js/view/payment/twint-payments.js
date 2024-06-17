define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'twint_regular',
                component: 'Twint_Core/js/view/payment/method-renderers/twint_regular'
            },
            {
                type: 'twint_express',
                component: 'Twint_Core/js/view/payment/method-renderers/twint_express'
            }
        );
        return Component.extend({});
    }
);
