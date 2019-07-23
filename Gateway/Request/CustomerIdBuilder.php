<?php
/**
 * Copyright (c) On Tap Networks Limited.
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
