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

namespace MasterCard\SimplifyCommerce\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class PaymentValidator extends AbstractValidator
{
    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $simplifyPayment = $response['object'];

        $paymentDO = SubjectReader::readPayment($validationSubject);
        $amount = (int) ((float) SubjectReader::readAmount($validationSubject) * 100);

        $order = $paymentDO->getOrder();

        $isValid = true;
        $fails = [];

        if (!is_object($simplifyPayment)) {
            return $this->createResult(false, [__('Invalid response from gateway.'), ]);
        }

        $statements = [
            [
                $paymentDO->getOrder()->getCurrencyCode() === $simplifyPayment->currency,
                __('Currency doesn\'t match.')
            ],
            [
                $amount === $simplifyPayment->amount,
                __('Amount doesn\'t match.')
            ],
            [
                in_array($simplifyPayment->paymentStatus, ['APPROVED', ]),
                __('Payment not approved.')
            ],
            [
                $simplifyPayment->reference == $order->getOrderIncrementId(),
                __('Order reference does\'t match.')
            ],
        ];

        foreach ($statements as $statementResult) {
            if (!$statementResult[0]) {
                $isValid = false;
                $fails[] = $statementResult[1];
            }
        }

        return $this->createResult($isValid, $fails);
    }
}
