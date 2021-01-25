/*
 * Copyright (c) 2013-2020 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
define([
    'jquery',
    'Magento_Checkout/js/action/set-payment-information',
    'MastercardPaymentGatewayServices_Simplify/js/view/payment/method-renderer/simplifycommerce-method'
], function (
    $,
    setPaymentInformationAction,
    Component
) {
    'use strict';

    return Component.extend({
        /**
         * Payment method code getter
         * @returns {String}
         */
        getCode: function () {
            return 'simplifycommerce_embedded';
        },

        /**
         * @param data
         */
        paymentCallback: function (data) {
            this.responseData = JSON.stringify(data);
            if (data.close && data.close === true) {
                fullScreenLoader.stopLoader();
                this.isPlaceOrderActionAllowed(true);
                return;
            }

            if (this.getCode() !== 'simplifycommerce_embedded') {
                return;
            }

            $.when(
                setPaymentInformationAction(this.messageContainer, this.getData())
            ).done(function () {
                window.location.href = this.getRedirectUrl() + '?cardToken=' + data.cardToken;
            }.bind(this));
        }
    });
});
