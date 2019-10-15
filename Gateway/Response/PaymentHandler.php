<?php
/**
 * Copyright (c) 2013-2019 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
