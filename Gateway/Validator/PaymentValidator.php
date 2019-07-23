<?php
/**
 * Copyright (c) On Tap Networks Limited.
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
