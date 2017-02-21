<?php

namespace MasterCard\SimplifyCommerce\Model;


/** Payment gateway */
interface PaymentGateway {
    /** Validates the gateway, if validation fails, returns error message */
    public function validate();

    /** Returns gateway configuration */
    public function getConfiguration();

    /** Returns gateway version */
    public function getVersion();

    /** Returns true if order amount is within the allowed ranges */
    public function isOrderAmountValid($amount);

    /** Returns true if saving customer cards is available */
    public function canSaveCard();

    /** Retrieves stored credit cards associated with the current customer */
    public function getSavedCreditCards();

    /** Finds a saved credit card with the specified last four digits */
    public function getSavedCreditCard($last4);

    /** Saves credit card associated with the specified payment in Simplify Commerce */
    public function saveCreditCard($payment);

    /** Authorizes payment */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount);

    /** Captures payment */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount);

    /** Void the payment */
    public function void(\Magento\Payment\Model\InfoInterface $payment);
    
    /** Refund the payment */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount);
}
 