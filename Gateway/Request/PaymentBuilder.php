<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class PaymentBuilder implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $amount = (int) ((float) SubjectReader::readAmount($buildSubject) * 100);

        return [
            'amount' => $amount,
            'currency' => $order->getCurrencyCode(),
            'reference' => $order->getOrderIncrementId()
        ];
    }
}
