<?php

namespace MasterCard\SimplifyCommerce\Model;

require_once("Log.php");
require_once("Utilities.php");
require_once("Simplify.php");

use \Exception;
use Psr\Log\LoggerInterface;
use Simplify as SC;
use Simplify_Payment as SC_Payment;
use Simplify_Customer as SC_Customer;
use Simplify_ObjectNotFoundException as SC_ObjectNotFoundException;


/*
 * Payment request data, as required by Simplify Commerce APIs
*/
class SC_Request 
{
    private $order = null;
    private $orderId = null;
    private $billing = null;
    private $shipping = null;
    private $customerid = null;
    private $token = null;
    private $cardNumber = null;
    private $expirationYear = null;
    private $expirationMonth = null;
    private $cvc = null;
    private $currency = null;
    private $name = null;
    private $email = null;
    private $description = null;
    private $reference = null;
    private $amount = null;
    private $authorizationId = null;

    public function __construct(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        if ($payment) {
            $this->order = $payment->getOrder();      
            $this->orderId = $this->order->getIncrementId();
            $this->billing = $this->order->getBillingAddress();
            $this->shipping = $this->order->getShippingAddress();
            $this->customerid = $this->order->getCustomerId();
            $this->cardNumber = $payment->getCcNumber();
            $this->expirationYear = $payment->getCcExpYear();
            $this->expirationMonth = $payment->getCcExpMonth();
            $this->cvc = $payment->getCcCid();
            $this->currency = $this->order->getBaseCurrencyCode();
            $this->name = $this->billing->getName();
            $this->email = $this->order->getCustomerEmail();
            $this->description = $this->email;
            $this->reference = "#" . $this->orderId;
            $this->cardToken = $payment->getAdditionalInformation("cc-token");      
            $this->authorizationId = $payment->getAdditionalInformation("authorization-id");
        }
        $this->amount = intval(round($amount * 100));
    }


    /*
     * Returns data for Simplify Commerce CreatePayment request
     */
    public function getPaymentRequest() {
        $data = null;
        if ($this->amount && $this->currency) {
            if ($this->cardToken) {
                $data = array(
                    "amount" => $this->amount,
                    "description" => $this->description,
                    "token" => $this->cardToken,
                    "reference" => $this->reference,
                    "currency" => $this->currency
                );
            }
            else if ($this->cardNumber) {
                $data = array(
                    "amount" => $this->amount,
                    "description" => $this->description,
                    "card" => array(
                        "expYear" => $this->expirationYear,
                        "expMonth" => $this->expirationMonth, 
                        "cvc" => $this->cvc,
                        "number" => $this->cardNumber
                    ),
                    "reference" => $this->reference,
                    "currency" => $this->currency
                );
            } 
            else if ($this->authorizationId) {
                $data = array(
                    "amount" => $this->amount,
                    "description" => $this->description,
                    "authorization" => $this->authorizationId,
                    "reference" => $this->reference,
                    "currency" => $this->currency
                );
            }
        }
        return $data;
    }
}


/** 
 * Credit Card payments using Simplify Commerce API 
 */
class Payment extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'simplifycommerce';
    const RE_ANS = "/[^A-Z\d\-_',\.;:\s]*/i";
    const RE_AN = "/[^A-Z\d]/i";
    const RE_NUMBER = "/[^\d]/";

    protected $_code = self::CODE;
    protected $version = "1.0.0";

    /** Feature availability */
    protected $_isGateway = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
  
    /** Payment method configuration */
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_publicKey = null;
    protected $_privateKey = null;
    protected $_paymentAction = null;

    protected $_log = null;
    protected $_developerMode = false;
    protected $_storeManager = null;


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
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
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

        $this->_logger = $logger;
        $this->_storeManager = $storeManager;        

        // Initialize custom Simplify log
        $this->_developerMode = \MasterCard\SimplifyCommerce\Utilities::isDeveloperMode();
        $this->_log = new \MasterCard\SimplifyCommerce\Log($this->_developerMode, BP . "/var/log/simplify.log");

        // Fetch Simplify Commerce plugin configuration
        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
        $this->_publicKey = $this->getConfigData("public_key");
        $this->_privateKey = $this->getConfigData("private_key");
        $this->_paymentAction = $this->getConfigData("payment_action");        

        // Configure Simplify Commerce API
        SC::$publicKey = $this->_publicKey;
        SC::$privateKey = $this->_privateKey;

        $this->_log->info("Payment module initialized");
        // $this->validateSimplifyCommerceAccount();
    }


    /** 
     * Performs a test call to Simplify Commerce API to validate the keys 
     */     
    public function validateSimplifyCommerceAccount() {
        $result = true;
        try {
            $cid = "-invalid-customer-";
            $c = SC_Customer::findCustomer($cid);
        } 
        catch (SC_ObjectNotFoundException $e) {
        }
        catch (Exception $e) {
            $result = false;
        }
        if ($result) {
            $this->_log->info("Merchant account valid, public key: " . $this->_publicKey);
        } else {
            $this->_log->error("Cannot validate merchant account, public key: " . $this->_publicKey);
        }
        return $result;
    }


    /** 
     * Authorize payment 
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_log->info("Authorizing payment ...");

        $result = null;
        try {
            $request = new SC_Request($payment, $amount);
            if ($request) {
                $result = SC_Payment::createAuthorization($request->getPaymentRequest());
            }
        }
        catch (Exception $e) {
            $this->_log->error("Authorization failed:\n" . $e->getMessage());
            $result = null;
            throw new \Magento\Framework\Validator\Exception(__("Authorization error: " . implode(", ", $result->errors)));
        }

        if ($result) {
            if ($result->paymentStatus == "APPROVED") {
                $this->_log->info("Authorization approved, ID: " . $result->id);
                $payment->setTransactionId($result->id);
                $payment->setCcLast4($result->card->last4);
                $payment->setIsTransactionClosed(1);
            } else {
                $this->_log->warning("Authorization not approved: " . $result->paymentStatus);
                throw new \Magento\Framework\Validator\Exception(__("Authorization not approved: " . $result->paymentStatus . $result->declineReason));
            }
        }
        else {            
            $this->_log->error("Authorization not processed");
            throw new \Magento\Framework\Validator\Exception(__("Authorization not processed"));
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
        $this->_log->info("Capturing payment ...");

        $result = null;
        try {
            $request = new SC_Request($payment, $amount);
            if ($request) {
                $result = SC_Payment::createPayment($request->getPaymentRequest());
            }
        }
        catch (Exception $e) {
            $this->_log->error("Payment failed:\n" . $e->getMessage());
            $result = null;
            throw new \Magento\Framework\Validator\Exception(__("Payment error: " . implode(", ", $result->errors)));
        }

        if ($result) {
            if ($result->paymentStatus == "APPROVED") {
                $this->_log->info("Payment approved, ID: " . $result->id);
                $payment->setTransactionId($result->id);
                $payment->setCcLast4($result->card->last4);
                $payment->setIsTransactionClosed(1);
            } else {
                $this->_log->warning("Payment not approved: " . $result->paymentStatus);
                throw new \Magento\Framework\Validator\Exception(__("Payment not approved: " . $result->paymentStatus . $result->declineReason));
            }
        }
        else {            
            $this->_log->error("Payment not processed");
            throw new \Magento\Framework\Validator\Exception(__("Payment not processed"));
        }

        return $this;
    }


    /** 
     * Void the payment 
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->_log->info("Voiding payment ...");
        return $this;
    }

    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->void($payment);
        return $this;
    }


    /** 
     * Refund the payment 
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $this->_log->info("Refunding payment ...");
        $transactionId = $payment->getParentTransactionId();
        $order = $payment->getOrder();

        // ... call Simplify API

        if (true) {
          $payment
              ->setTransactionId($result->response->id)
              ->setParentTransactionId($transactionId)
              ->setIsTransactionClosed(1)
              ->setShouldCloseParentTransaction(1);
        } 
        else {
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error - ' . implode(', ', $result->errors)));
        }

        return $this;
    }


    /** 
     * Determine method availability based on quote amount and config data 
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
        // Simplify Commerce API keys required
        if (!$this->_publicKey) {
            $this->_log->info("Payment method not available: public API key not specified");
            return false;
        }
        if (!$this->_privateKey) {
            $this->_log->info("Payment method not available: private API key not specified");
            return false;
        }

        // Order total must be within the specified limits
        if ($quote) {
            $total = $quote->getBaseGrandTotal();
            if ($total < $this->_minAmount || ($this->_maxAmount && $total > $this->_maxAmount)) {
                $this->_log->info("Payment method not available: order value out of range");
                return false;
            }
        } 

        // Some credit card types must be selected
        $ccTypes = explode(',', $this->getConfigData("cctypes"));
        if (!($ccTypes || $ccTypes.length == 0)) {
            $this->_log->info("No credit cards enabled in store configuration");
            return false;
        }

        // Some currencies must be selected
        $currencies = explode(',', $this->getConfigData("currencies"));
        if (!($currencies || $currencies.length == 0)) {
            $this->_log->info("No currencies enabled in store configuration");
            return false;
        }

        if ($quote) {
            $currencyCode = $quote->getBaseCurrencyCode();
            if (!$this->isCurrencySupported($currencyCode)) {
                $this->_log->info("Unsupported currency " . $currencyCode);
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
     * Assign additional card data to payment instance 
     */
    public function assignData(\Magento\Framework\DataObject $data) {
        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }
        $additionalData = $data->getData("additional_data");
        $data->setCcType($additionalData["cc-type"]);
        $data->setCcExpMonth($additionalData["cc-expiration-month"]);
        $data->setCcExpYear($additionalData["cc-expiration-year"]);
        if (isset($additionalData["cc-number"])) {
          $data->setCcNumber($additionalData["cc-number"]);
          $data->setCcLast4(substr($additionalData["cc-number"], -4));
        }
        if (isset($additionalData["cc-cid"])) {
            $data->setCcCid($additionalData["cc-cid"]);
        }
        $info = $this->getInfoInstance();
        $info
            ->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear());

        if (isset($additionalData["cc-token"])) {
            $info->setAdditionalInformation("cc-token", $additionalData["cc-token"]);
        }
        if (isset($additionalData["cc-save"])) {
            $info->setAdditionalInformation("cc-save", $additionalData["cc-save"]);
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
        $this->_log->info("Validating payment ...");
        if (isset($token)) {
            $result = $this;
        } else {
            $result = parent::validate();
        }
        $this->_log->info("Validation completed");
        return $result;
    }
}
