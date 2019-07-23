<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Zend_Json_Encoder;

class CustomerHandler implements HandlerInterface
{
    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        $simplifyDO = $response['object'];

        $customer = Zend_Json_Encoder::encode([
            'id' => $simplifyDO->id,
            'last4' => $simplifyDO->card->last4,
            'type' => $simplifyDO->card->type,
            'expMonth' => $simplifyDO->card->expMonth,
            'expYear' => $simplifyDO->card->expYear,
        ]);

        $payment->setAdditionalInformation('customer', $customer);
    }
}
