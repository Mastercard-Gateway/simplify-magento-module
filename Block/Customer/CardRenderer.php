<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

namespace MasterCard\SimplifyCommerce\Block\Customer;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;
use MasterCard\SimplifyCommerce\Ui\ConfigProvider;

class CardRenderer extends AbstractCardRenderer
{
    /**
     * @inheritDoc
     */
    public function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['last4'];
    }

    /**
     * @inheritDoc
     */
    public function getExpDate()
    {
        return sprintf('%s/%s', $this->getTokenDetails()['expMonth'], $this->getTokenDetails()['expYear']);
    }

    /**
     * @inheritDoc
     */
    public function getIconUrl()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['url'];
    }

    /**
     * @inheritDoc
     */
    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    /**
     * @inheritDoc
     */
    public function getIconWidth()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }

    /**
     * @inheritDoc
     */
    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === ConfigProvider::CODE;
    }
}
