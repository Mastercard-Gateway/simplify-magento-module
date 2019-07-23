<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class CustomerValidator extends AbstractValidator
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
        $paymentDO = SubjectReader::readPayment($validationSubject);

        $simplifyDO = $response['object'];

        $isValid = true;
        $fails = [];

        if (!is_object($simplifyDO)) {
            return $this->createResult(false, [__('Invalid response from gateway.'), ]);
        }

        $order = $paymentDO->getOrder();

        $statements = [
            [
                $order->getCustomerId() == $simplifyDO->reference,
                __('Customer ID does\'t match.')
            ],
//            [
//                count($simplifyDO->cards) !== 0,
//                __('Customer has no payments stored.')
//            ]
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
