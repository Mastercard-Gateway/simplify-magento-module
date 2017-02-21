<?php

namespace MasterCard\SimplifyCommerce\Model;

use \Exception;

/** 
 * Credit Card payments using Simplify Commerce API 
 */
class Payment extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'simplifycommerce';
    protected $_code = self::CODE;

    /** Feature availability */
    protected $_isGateway = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;

    protected $logger = null;
    protected $gateway = null;
    protected $developerMode = false;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \MasterCard\SimplifyCommerce\Model\PaymentGatewayFactory $gatewayFactory,
        \Magento\Framework\App\State $appState,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );

        $this->developerMode = $appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER;
        $this->logger = $logger;
        $this->gateway = $gatewayFactory->create();
        $this->log("Payment module initialized", $this->gateway->getVersion());
    }


    /** Outputs message and optional data to debug log */
    public function log($message, $data = null) {
        $this->logger->debug(["message" => $message, "data" => $data], null, $this->developerMode);
    }


    /** Handles error result of payment gateway transaction */
    public function handleError($operation, $result) {
        if ($operation && $result && $result["error"]) {
            $message = $operation . ": " . $result["status"];
            if ($result["details"]) {
                $message = $message . ", " . $result["details"];
            }
            $this->log($message);
            throw new \Magento\Framework\Exception\LocalizedException(__($operation . " failed"));
        }
    }

    /** 
     * Authorize payment 
     * Payment interface: /vendor/magento/module-sales/Model/Order/Payment.php
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $result = $this->gateway->authorize($payment, $amount);
        if ($result["error"]) {
            $this->handleError("Authorization", $result);
        } 
        else {
            $payment->setTransactionId($result["transactionId"]);
            $payment->setCcLast4($result["last4"]);
            $payment->setIsTransactionClosed(false);
        }
        return $this;
    }


    /** 
      * Authorize and capture the payment 
     */
    public function authorize_capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this->capture($payment, $amount);
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $result = $this->gateway->capture($payment, $amount);
        if ($result["error"]) {
            $this->handleError("Payment", $result);
        } 
        else {
            $payment->setTransactionId($result["transactionId"]);
            $payment->setParentTransactionId($result["parentTransactionId"]);
            $payment->setCcLast4($result["last4"]);
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);               
        }
        return $this;
    }


    /** 
     * Void the payment 
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $result = $this->gateway->void($payment);
        if ($result["error"]) {
            $this->handleError("Void", $result);
        } 
        else {
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
        }
        return $this;
    }

    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->void($payment);
    }


    /** 
     * Refund the payment 
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $result = $this->gateway->refund($payment, $amount);
        if ($result["error"]) {
            $this->handleError("Refund", $result);
        } 
        else {
            $payment
                ->setTransactionId($result["transactionId"])
                ->setParentTransactionId($result["parentTransactionId"])
                ->setIsTransactionClosed(true)
                ->setShouldCloseParentTransaction(true);
        }
        return $this;
    }


    /** 
     * Determine method availability based on quote amount and config data 
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
        // Simplify Commerce Gateway is not available?
        if (!$this->gateway) {
            $this->log("Simplify Commerce Payments Gateway not available");
            return false;
        }

        // Simplify Commerce Gateway is not configured properly?
        $configurationErrors = $this->gateway->validate();
        if ($configurationErrors) {
            $this->log($configurationErrors);
            return false;
        }

        // Order total must be within the specified limits
        if ($quote) {
            $total = $quote->getBaseGrandTotal();
            if (!$this->gateway->isOrderAmountValid($total)) {
                $this->log("Payment method not available: order value out of range");
                return false;
            }
        } 

        // Some credit card types must be selected
        $ccTypes = explode(',', $this->getConfigData("cctypes"));
        if (!($ccTypes || $ccTypes.length == 0)) {
            $this->log("No credit cards enabled in store configuration");
            return false;
        }

        // Some currencies must be selected
        $currencies = explode(',', $this->getConfigData("currencies"));
        if (!($currencies || $currencies.length == 0)) {
            $this->log("No currencies enabled in store configuration");
            return false;
        }

        // Order currency must be allowed
        if ($quote) {
            $currencyCode = $quote->getBaseCurrencyCode();
            if (!$this->isCurrencySupported($currencyCode)) {
                $this->log("Unsupported currency", $currencyCode);
                return false;
            }
        }

        return parent::isAvailable($quote);
    }


    /** 
     * Returns true if payment method supports the specified currency 
     */
    public function isCurrencySupported($currencyCode) {
        $supportedCurrencyCodes = explode(',', $this->getConfigData("currencies"));
        return in_array($currencyCode, $supportedCurrencyCodes);
    }


    /** 
     * Returns true if payment method supports the specified credit card type
     */
    public function isCardTypeSupported($cardType) {
        $supportedCardTypes = explode(',', $this->getConfigData("cctypes"));
        return in_array($cardType, $supportedCardTypes);
    }


    /** 
     * Assign card data from input form to payment instance 
     */
    public function assignData(\Magento\Framework\DataObject $data) {
        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }
        parent::assignData($data);
        $additionalData = $data->getData(\Magento\Quote\Api\Data\PaymentInterface::KEY_ADDITIONAL_DATA);
        if ($additionalData) {
            $payment = $this->getInfoInstance();
            // pass credit card from built-in payment form
            $payment->addData($additionalData);
            // pass card token from Simplify Hosted Payment form
            if (isset($additionalData["cc-token"])) {
                $payment->setAdditionalInformation("cc-token", $additionalData["cc-token"]);
            }
            if (isset($additionalData["cc-last4"])) {
                $payment->setAdditionalInformation("cc-last4", $additionalData["cc-last4"]);
            }
            if (isset($additionalData["cc-type"])) {
                $payment->setAdditionalInformation("cc-type", $additionalData["cc-type"]);
            }
            if (isset($additionalData["cc-expiration-month"])) {
                $payment->setAdditionalInformation("cc-expiration-month", $additionalData["cc-expiration-month"]);
            }
            if (isset($additionalData["cc-expiration-year"])) {
                $payment->setAdditionalInformation("cc-expiration-year", $additionalData["cc-expiration-year"]);
            }
            // pass instruction to save the card
            if (isset($additionalData["cc-save"])) {
                $payment->setAdditionalInformation("cc-save", $additionalData["cc-save"]);
            }
            // get the identifier of the saved card to use for payment operation
            if (isset($additionalData["cc-use-card"])) {
                $payment->setAdditionalInformation("cc-use-card", $additionalData["cc-use-card"]);
            }            
        }
        return $this;
    }


    /** 
     * Override validate to skip checks if token used 
     */
    public function validate() {
        $result = null;
        $info = $this->getInfoInstance();
        $token = $info->getAdditionalInformation("cc-token");
        $useSavedCard = $info->getAdditionalInformation("cc-use-card");
        if (isset($token) || isset($useSavedCard)) {
            $result = $this;
        } else {
            $result = parent::validate();
        }
        return $result;
    }


}

