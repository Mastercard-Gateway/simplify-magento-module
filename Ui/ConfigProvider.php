<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

namespace MasterCard\SimplifyCommerce\Ui;

use MasterCard\SimplifyCommerce\Model\Config\Source\FormType;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\UrlInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'simplifycommerce';
    const VAULT_CODE = 'simplifycommerce_vault';

    const PAYMENT_CODE = 'simplifycommerce';
    const JS_COMPONENT = 'js_component_url';
    const PUBLIC_KEY = 'public_key';
    const IS_MODAL = 'is_modal';
    const PAYMENT_FORM_TYPE = 'payment_form_type';
    const REDIRECT_URL = 'redirect_url';

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * ConfigProvider constructor.
     * @param ConfigInterface $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ConfigInterface $config,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return string
     */
    protected function getPlaceOrderUrl()
    {
        return $this->urlBuilder->getUrl('mastercard/simplify/placeOrder', [
            '_secure' => 1
        ]);
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::PAYMENT_CODE => [
                    self::JS_COMPONENT => $this->config->getValue(self::JS_COMPONENT),
                    self::PUBLIC_KEY => $this->config->getValue(self::PUBLIC_KEY),
                    self::IS_MODAL => $this->config->getValue(self::PAYMENT_FORM_TYPE) == FormType::MODAL,
                    self::REDIRECT_URL => $this->getPlaceOrderUrl(),
                    'vault_code' => self::VAULT_CODE,
                ]
            ]
        ];
    }
}
