/*
 * Copyright (c) 2013-2019 Mastercard
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
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/place-order',
    'Magento_Customer/js/customer-data',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/action/set-payment-information',
], function (
    $,
    Component,
    quote,
    customer,
    urlBuilder,
    storage,
    errorProcessor,
    fullScreenLoader,
    placeOrderService,
    customerData,
    VaultEnabler,
    setPaymentInformationAction
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MasterCard_SimplifyCommerce/payment/simplifycommerce'
        },
        totals: quote.getTotals(),
        responseData: null,

        /**
         * @returns {exports}
         */
        initialize: function () {
            this._super();
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());

            return this;
        },

        /**
         * @returns {String}
         */
        getVaultCode: function () {
            return this.getConfig()['vault_code'];
        },

        /**
         * @returns {String}
         */
        getPublicKey: function () {
            return this.getConfig()['public_key'];
        },

        /**
         * @returns {Array}
         */
        getConfig: function () {
            return window.checkoutConfig.payment[this.getCode()];
        },

        /**
         * @returns {Boolean}
         */
        isModal: function () {
            return this.getConfig()['is_modal'];
        },

        getRedirectUrl: function () {
            return this.getConfig()['redirect_url'];
        },

        savePayment: function () {
            $.when(
                setPaymentInformationAction(this.messageContainer, this.getData())
            ).done(this.savePaymentCallback.bind(this));

            return false;
        },

        savePaymentCallback: function () {
            fullScreenLoader.startLoader();
            this.isPlaceOrderActionAllowed(false);
            setTimeout(function () {
                fullScreenLoader.stopLoader();
            }.bind(this), 1000);
            var button = $('button[data-role=' + this.getCode() + '_pay]');
            button.trigger('click');
        },

        /**
         * @returns {exports}
         */
        initChildren: function () {
            this._super();

            requirejs.load({
                contextName: '_',
                onScriptLoad: this.adapterLoaded.bind(this)
            }, this.getCode(), this.getConfig()['js_component_url']);

            return this;
        },

        /**
         * void
         */
        adapterLoaded: function () {
            SimplifyCommerce.hostedPayments(
                this.paymentCallback.bind(this),
                {
                    scKey: this.getConfig()['public_key'],
                    amount: this.totals().base_grand_total * 100,
                    currency: this.totals().quote_currency_code,
                    reference: quote.getQuoteId(),
                    operation: 'create.token'
                }
            ).closeOnCompletion();
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
            this.placeOrder();
        },

        /**
         * Get payment method data
         */
        getData: function () {
            var data = this._super();

            if (!('additional_data' in data) || data['additional_data'] === null) {
                data['additional_data'] = {};
            }

            data['additional_data']['response'] = this.responseData;

            this.vaultEnabler.visitAdditionalData(data);

            return data;
        },

        /**
         * Payment method code getter
         * @returns {String}
         */
        getCode: function () {
            return 'simplifycommerce';
        },

        /**
         * @returns {Boolean}
         */
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },
    });
});
