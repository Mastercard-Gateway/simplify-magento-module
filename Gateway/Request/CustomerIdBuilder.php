<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Zend_Json_Decoder;

class CustomerIdBuilder implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return array
     * @throws \Zend_Json_Exception
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $simplifyDO = Zend_Json_Decoder::decode(
            $payment->getAdditionalInformation('customer')
        );

        return [
            'customer' => $simplifyDO['id']
        ];
    }
}
