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
                type: 'paytm',
                component: 'One97_Paytm/js/view/payment/method-renderer/one97-paytm'
            }
        );
        return Component.extend({});
    }
 );