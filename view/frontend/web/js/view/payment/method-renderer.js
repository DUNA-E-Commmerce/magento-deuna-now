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
                type: 'deuna',
                component: 'Deuna_Now/js/view/payment/method-renderer/deuna'
            }
        );
        return Component.extend({});
    }
);
