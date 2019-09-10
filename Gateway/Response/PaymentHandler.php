<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

abstract class PaymentHandler implements HandlerInterface
{
    /**
     * @var array
     */
    private $additionalFields = [
        'source',
        'paymentStatus'
    ];

    /**
     * @var array
     */
    private $additionalCardFields = [
        'last4',
        'type',
        'expMonth',
        'expYear',
    ];

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $dataObject = $response['object'];

        $payment->setTransactionId($dataObject->id);
        $payment->setLastTransId($dataObject->id);
        $payment->setCcTransId($dataObject->id);
        $payment->setCcLast4($dataObject->card->last4);
        $payment->setCcExpMonth($dataObject->card->expMonth);
        $payment->setCcExpYear($dataObject->card->expYear);

        foreach ($this->additionalFields as $field) {
            if (!isset($dataObject->$field)) {
                continue;
            }
            $payment->setAdditionalInformation($field, $dataObject->$field);
        }

        $dataObject = $response['object']->card;
        foreach ($this->additionalCardFields as $field) {
            if (!isset($dataObject->$field)) {
                continue;
            }
            $payment->setAdditionalInformation($field, $dataObject->$field);
        }
    }
}
