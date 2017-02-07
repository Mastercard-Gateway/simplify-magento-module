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
                type: 'simplifycommerce',
                component: 'MasterCard_SimplifyCommerce/js/view/payment/method-renderer/simplifycommerce-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
