<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
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
