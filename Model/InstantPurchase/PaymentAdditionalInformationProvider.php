<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

namespace MasterCard\SimplifyCommerce\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\PaymentAdditionalInformationProviderInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class PaymentAdditionalInformationProvider implements PaymentAdditionalInformationProviderInterface
{
    /**
     * @return array
     */
    public function getAdditionalInformation(PaymentTokenInterface $paymentToken): array
    {
        return [];
    }
}
