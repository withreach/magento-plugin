define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
 
        rendererList.push(
            {
                type: 'reach_cc',
                component: 'Reach_Payment/js/view/payment/method-renderer/cc'
 
            },
            {
                type: 'reach_paypal',
                component: 'Reach_Payment/js/view/payment/method-renderer/paypal'
 
            },
        );

        
 
        /** Add view logic here if needed */
        return Component.extend({});
    });
