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
                component: 'Twint_Magento/js/view/payment/method-renderers/twint_regular'
            }
        );
        return Component.extend({});
    }
);
