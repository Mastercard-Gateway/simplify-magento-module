<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

namespace MasterCard\SimplifyCommerce\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface;

class AvailabilityChecker implements AvailabilityCheckerInterface
{
    /**
     * Checks if payment method may be used for instant purchase.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return true;
    }
}
