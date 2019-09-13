<?php
/**
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
