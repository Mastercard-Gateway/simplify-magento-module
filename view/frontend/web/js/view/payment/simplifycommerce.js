/*
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

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
