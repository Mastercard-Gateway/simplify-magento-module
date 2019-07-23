<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

namespace MasterCard\SimplifyCommerce\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class TokenFormatter implements PaymentTokenFormatterInterface
{
    /**
     * @var array
     */
    public static $baseCardTypes = [
        'AE' => 'American Express',
        'VI' => 'Visa',
        'MC' => 'MasterCard',
        'DI' => 'Discover',
        'JBC' => 'JBC',
        'CUP' => 'China Union Pay',
        'MI' => 'Maestro',
    ];

    /**
     * @inheritdoc
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        if (!isset($details['type'], $details['last4'], $details['expMonth'], $details['expYear'])) {
            throw new \InvalidArgumentException('Invalid Simplify credit card token details.');
        }

        if (isset(self::$baseCardTypes[$details['type']])) {
            $ccType = self::$baseCardTypes[$details['type']];
        } else {
            $ccType = $details['type'];
        }

        $formatted = sprintf(
            '%s: %s, %s: %s (%s: %s/%s)',
            __('Credit Card'),
            $ccType,
            __('ending'),
            $details['last4'],
            __('expires'),
            $details['expMonth'],
            $details['expYear']
        );

        return $formatted;
    }
}
