<?php
/**
 * Copyright (c) On Tap Networks Limited.
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
