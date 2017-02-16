<?php

namespace MasterCard\SimplifyCommerce\Model;

require_once("Log.php");
require_once("Utilities.php");
require_once("Simplify.php");

use \Exception;
use Simplify as SC;
use Simplify_Payment as SC_Payment;
use Simplify_Authorization as SC_Authorization;
use Simplify_Refund as SC_Refund;
use Simplify_Customer as SC_Customer;
use Simplify_ObjectNotFoundException as SC_ObjectNotFoundException;


/*
 * Request builder, for creating Simplify Commerce API requests 
*/
class SC_RequestBuilder
{
    public $order = null;
    public $orderId = null;
    public $billing = null;
    public $shipping = null;
    public $token = null;
    public $cardNumber = null;
    public $last4 = null;
    public $cardType = null;
    public $cardId = null;
    public $customerId = null;
    public $expirationYear = null;
    public $expirationMonth = null;
    public $cvc = null;
    public $currency = null;
    public $name = null;
    public $email = null;
    public $description = null;
    public $reference = null;
    public $amount = null;
    public $parentTransactionId = null;
    public $transactionId = null;
    public $saveCard = false;
    public $useSavedCard = false;

    public function __construct(\Magento\Payment\Model\InfoInterface $payment, $amount = 0) {
        if ($payment) {
            $this->order = $payment->getOrder();      
            $this->orderId = $this->order->getIncrementId();
            $this->transactionId = $payment->getTransactionId();
            $this->parentTransactionId = $payment->getParentTransactionId();
            $this->billing = $this->order->getBillingAddress();
            $this->shipping = $this->order->getShippingAddress();
            $this->customerId = $this->order->getCustomerId();
            $this->cardNumber = $payment->getCcNumber();
            $this->cardType = $this->toSimplifyCardType($payment->getCcType());
            $this->expirationYear = $this->toSimplifyYear($payment->getCcExpYear());
            $this->expirationMonth = $payment->getCcExpMonth();
            $this->cvc = $payment->getCcCid();
            $this->currency = $this->order->getBaseCurrencyCode();
            $this->name = $this->billing->getName();
            $this->email = $this->order->getCustomerEmail();
            $this->description = $this->email;
            $this->reference = "#" . $this->orderId;
            $this->cardToken = $payment->getAdditionalInformation("cc-token");
            $this->last4 = $payment->getAdditionalInformation("cc-number");
            $this->saveCard = $payment->getAdditionalInformation("cc-save");
            $this->useSavedCard = $payment->getAdditionalInformation("cc-use-card");
        }
        $this->amount = intval(round($amount * 100));
    }


    /* Converts Magento card type to Simplify card type */
    public function toSimplifyCardType($cardType) {
        $result = $cardType;
        try {
            $result = $this->cardTypes[$cardType];
        }
        catch (Exception $e) {
        }
        return $result;
    }

    /* Converts Magento expiration year to Simplify year */
    public function toSimplifyYear($year) {
        if ($year) {
            $year = intval(substr(strval($year), -2));
        }
        return $year;
    }

    /* Returns data for Simplify Commerce CreatePayment and CreateAuthorization requests */
    public function getPaymentCreateRequest() {
        $data = null;
        if ($this->amount && $this->currency) {
            // capture pre-authorized payment
            if ($this->parentTransactionId) {
                $data = array(
                    "amount" => $this->amount,
                    "description" => $this->description,
                    "authorization" => $this->parentTransactionId,
                    "reference" => $this->reference,
                    "currency" => $this->currency
                );
            }
            // authorize/capture payment using Simplify customer identifier
            else if ($this->cardId) {
                $data = array(
                    "amount" => $this->amount,
                    "description" => $this->description,
                    "customer" => $this->cardId,
                    "reference" => $this->reference,
                    "currency" => $this->currency
                );
            }
            // authorize/capture payment using card token
            else if ($this->cardToken) {
                $data = array(
                    "amount" => $this->amount,
                    "description" => $this->description,
                    "token" => $this->cardToken,
                    "reference" => $this->reference,
                    "currency" => $this->currency
                );
            }
            // authorize/capture payment using raw card data
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
                if ($this->billing) {
                    $data["card"]["name"] = $this->billing->getName();
                    $data["card"]["addressCity"] = $this->billing->getCity();
                    $data["card"]["addressLine1"] = $this->billing->getStreetLine(1);
                    $data["card"]["addressLine2"] = $this->billing->getStreetLine(2);
                    $data["card"]["addressZip"] = $this->billing->getPostcode();
                    $data["card"]["addressState"] = $this->billing->getRegion();
                    $data["card"]["addressCountry"] = $this->billing->getCountryId();
                }
            } 
        }
        return $data;
    }


    /** Returns data for Simplify Commerce CreateCustomer request */
    public function getCustomerCreateRequest($customer) {
        $data = null;
        if ($customer) {
            // create customer using token of a just-verified card
            if ($this->cardToken) {
                $data = array(
                    "token" => $this->cardToken,
                    "name" => $customer->getFirstName() . " " . $customer->getLastName(),
                    "email" => $customer->getEmail(),
                    "reference" => $customer->getId()
                );
            }
            // create customer using raw card data
            else if ($this->cardNumber) {
                $data = array(
                    "name" => $customer->getFirstName() . " " . $customer->getLastName(),
                    "email" => $customer->getEmail(),
                    "reference" => $customer->getId(),
                    "card" => array(
                        "expYear" => $this->expirationYear,
                        "expMonth" => $this->expirationMonth, 
                        "cvc" => $this->cvc,
                        "number" => $this->cardNumber
                    )
                );
                if ($this->billing) {
                    $data["name"] = $this->billing->getName();
                    $data["card"]["name"] = $this->billing->getName();
                    $data["card"]["addressCity"] = $this->billing->getCity();
                    $data["card"]["addressLine1"] = $this->billing->getStreetLine(1);
                    $data["card"]["addressLine2"] = $this->billing->getStreetLine(2);
                    $data["card"]["addressZip"] = $this->billing->getPostcode();
                    $data["card"]["addressState"] = $this->billing->getRegion();
                    $data["card"]["addressCountry"] = $this->billing->getCountryId();
                }
            } 
        }
        return $data;
    }


    /* Returns data for Simplify Commerce CreateRefund request */
    public function getRefundCreateRequest($reason) {
        $data = null;        
        if ($this->amount && $this->currency && $this->transactionId) { 
            $data = array(
                "amount" => $this->amount,
                "description" => is_null($reason) ? __("Refund for ") . $this->reference : $reason,
                "payment" => $this->parentTransactionId,
                "reference" => $this->reference
            );
        }
        return $data;
    }


    /** Returns true if customer card can be saved:
        - user requested that during checkout
        - customer is logged in
        - card data or card token is present */
    public function canSaveCard() {
        return $this->order &&
               (!$this->order->getCustomerIsGuest()) &&
               $this->saveCard &&
               ($this->cardToken || $this.cardNumber) &&
               $this->last4;
    }


    /** Card type map */
    private $cardTypes = array(
            "VI" => "VISA",
            "MC" => "MASTERCARD",
            "AE" => "AMEX",
            "JCB" => "JCB",
            "DI" => "DISCOVER",
            "DN" => "DINERS"
    );

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
    protected $_savedCards = null;

    protected $_log = null;
    protected $_developerMode = false;
    protected $_storeManager = null;
    protected $_customer = null;
    protected $_customerExtensionFactory = null;
    protected $_customerRepository = null;
    protected $_configProvider = null;


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
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, 
        \Magento\Customer\Api\Data\CustomerExtensionFactory $customerExtensionFactory,
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
        $this->_customerExtensionFactory = $customerExtensionFactory;
        $this->_customerRepository = $customerRepository;
        if ($currentCustomer->getCustomerId())       
            $this->_customer = $customerRepository->getById($currentCustomer->getCustomerId());

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
        SC::$userAgent = "Magento-2.1.0";

        // Fetch saved customer credit cards   
        if ($this->_customer) {
            $this->_savedCards = $this->getSavedCreditCards();
        }

        $this->_log->debug("Payment module initialized");
        // $this->validateSimplifyCommerceAccount();
    }


    /** Retrieves stored credit cards associated with the current customer */
    public function getSavedCreditCards() {
        $savedCreditCards = [];
        if ($this->_customer && $this->_customer->getEmail()) {
            $customers = SC_Customer::listCustomer([
                "filter" => [
                    "text"=> $this->_customer->getEmail()
                ]
            ]);
            if ($customers) {
                foreach ($customers->list as $customer) {
                    if ($customer->card) {
                        $card = [
                            "id" => $customer->id,
                            "last4" => $customer->card->last4,
                            "year" => $customer->card->expYear,
                            "month" => $customer->card->expMonth,
                        ];
                        if (!isset($savedCreditCards[$card["last4"]]))
                            $savedCreditCards[$card["last4"]] = $card;
                    }
                }
            }
        }
        return $savedCreditCards;
    }


    /** Finds a saved credit card with the specified last four digits */
    public function findCreditCard($last4) {
        $savedCreditCard = null;
        if ($this->_savedCards) {
            foreach ($this->_savedCards as $card) {            
                if ($card["last4"] == $last4) {
                    $savedCreditCard = $card;
                    break;
                }
            }
        }
        return $savedCreditCard;
    }


    /** Saves credit card in Simplify Commerce */
    public function saveCreditCard($payment) {
        $savedCreditCard = null;
        $requestBuilder = new SC_RequestBuilder($payment);

        // Proceed if saving cards enabled in store configuration, customer is logged in 
        // and all data required for saving the card available in the payment
        if ($this->_customer && $requestBuilder->canSaveCard()) {
            // ... but ignore if the specified card has already been saved
            if ($this->_savedCards && isset($this->_savedCards[$requestBuilder->last4])) {
                $savedCreditCard = $this->_savedCards[$requestBuilder->last4];
            }
            else {
                $result = null;
                $request = $requestBuilder->getCustomerCreateRequest($this->_customer);
                if ($request) {
                    $this->_log->debug("Customer request: " . var_export($request, true));
                    $result = SC_Customer::createCustomer($request);
                }
                if ($result && $result->id) {
                    $savedCreditCard = [
                        "id" => $result->id,
                        "last4" => $result->card->last4,
                        "year" => $result->card->expYear,
                        "month" => $result->card->expMonth,
                    ];
                    // store in saved cards cache
                    $this->_savedCards[$savedCreditCard["last4"]] = $savedCreditCard;
                }
            }
        }
        
        return $savedCreditCard;
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
            $this->_log->debug("Merchant account valid, public key: " . $this->_publicKey);
        } else {
            $this->_log->error("Cannot validate merchant account, public key: " . $this->_publicKey);
        }
        return $result;
    }


    /** 
     * Authorize payment 
     * Payment interface: /vendor/magento/module-sales/Model/Order/Payment.php
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_log->debug("Authorizing payment ...");

        $result = null;
        try {
            $requestBuilder = new SC_RequestBuilder($payment, $amount);            

            if ($requestBuilder->canSaveCard()) {
                $savedCard = $this->saveCreditCard($payment);
                if ($savedCard) {
                    // Pass card identifier to request builder, so that the payment is performed with it.
                    // Card token has already been used for saving the card, so payment with it would fail.
                    $requestBuilder->cardId = $savedCard["id"];
                }
            }

            $request = $requestBuilder->getPaymentCreateRequest();
            if ($request) {
                $this->_log->debug("Authorization request: " . var_export($request, true));
                $result = SC_Authorization::createAuthorization($request);
            }
        }
        catch (Exception $e) {
            $status = $e->getMessage();
            $this->_log->error("Authorization failed: " . $status);
            throw new \Magento\Framework\Exception\LocalizedException(__("Authorization failes: " . $status));
        }

        if ($result) {
            if ($result->paymentStatus == "APPROVED") {
                $this->_log->debug("Authorization approved, ID: " . $result->id);
                $payment->setTransactionId($result->id);
                $payment->setParentTransactionId($result->id);
                $payment->setCcLast4($result->card->last4);
                $payment->setIsTransactionClosed(false);
            } else {
                $this->_log->warning("Authorization not approved: " . $result->paymentStatus);
                throw new \Magento\Framework\Exception\LocalizedException(__("Authorization not approved: " . $result->paymentStatus . $result->declineReason));
            }
        }
        else {            
            $this->_log->error("Authorization not processed");
            throw new \Magento\Framework\Exception\LocalizedException(__("Authorization not processed"));
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
        $this->_log->debug("Capturing payment ...");

        $result = null;
        $requestBuilder = null;
        try {
            $requestBuilder = new SC_RequestBuilder($payment, $amount);

            if ($requestBuilder->useSavedCard) {
                $savedCard = $this->findCreditCard($requestBuilder->useSavedCard);
                if ($savedCard) {
                    $requestBuilder->cardId = $savedCard["id"];
                }
            }
            else {
                if ($requestBuilder->canSaveCard()) {
                    $savedCard = $this->saveCreditCard($payment);
                    if ($savedCard) {
                        // Pass card identifier to request builder, so that the payment is performed with it.
                        // Card token has already been used for saving the card, so payment with it would fail.
                        $requestBuilder->cardId = $savedCard["id"];
                    }
                }
            }

            // Create payment            
            $request = $requestBuilder->getPaymentCreateRequest();
            if ($request) {
                $this->_log->debug("Payment request: " . var_export($request, true));
                $result = SC_Payment::createPayment($request);
            }
        }
        catch (Exception $e) {
            $status = $e->getMessage();
            $this->_log->error("Payment failed: " . $status);
            throw new \Magento\Framework\Exception\LocalizedException(__("Payment failed: " . $status));
        }

        if ($result) {
            if ($result->paymentStatus == "APPROVED") {
                $this->_log->debug("Payment approved, ID: " . $result->id);
                $payment->setTransactionId($result->id);
                $payment->setCcLast4($result->card->last4);
                $payment->setIsTransactionClosed(false);
                $payment->setShouldCloseParentTransaction(true);               
            } else {
                $this->_log->warning("Payment not approved: " . $result->paymentStatus);
                throw new \Magento\Framework\Exception\LocalizedException(__("Payment not approved: " . $result->paymentStatus . $result->declineReason));
            }
        }
        else {            
            $this->_log->error("Payment not executed");
            throw new \Magento\Framework\Exception\LocalizedException(__("Payment not executed"));
        }

        return $this;
    }


    /** 
     * Void the payment 
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $this->_log->debug("Voiding payment ...");

        $result = false;
        try {
            $requestBuilder = new SC_RequestBuilder($payment, $amount);
            if ($request) {
                $authorization = SC_Authorization::findAuthorization($requestBuilder->transactionId);
                if ($authorization) {
                    $result = SC_Authorization::deleteAuthorization($authorization);
                }
            }
        }
        catch (Exception $e) { 
            $status = $e->getMessage();
            $this->_log->error("Void failed: " . $status);
            throw new \Magento\Framework\Exception\LocalizedException(__("Void failed: " . $refundStatus));
        }

        if ($result) {
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            $this->_log->debug("Void approved");
        }
        else {            
            $this->_log->error("Void not executed");
            throw new \Magento\Framework\Exception\LocalizedException(__("Void not executed"));
        }

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
        $this->_log->debug("Refunding payment ...");

        $result = null;
        $status = __("Refund not approved");
        try {
            $requestBuilder = new SC_RequestBuilder($payment, $amount);
            $request = $requestBuilder->getRefundCreateRequest(null);
            if ($request) {
                $result = SC_Refund::createRefund($request);
            }
        }
        catch (Exception $e) { 
            $status = $e->getMessage();
            $this->_log->error("Refund failed: " . $refundStatus);
            throw new \Magento\Framework\Exception\LocalizedException(__("Refund failed: " . $status));
        }

        if ($result) {
            $payment->setTransactionId($result->id);
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            $this->_log->debug("Refund approved, ID: " . $result->id);
        }
        else {            
            $this->_log->error("Refund not executed");
            throw new \Magento\Framework\Exception\LocalizedException(__("Refund not executed"));
        }

        return $this;
    }


    /** 
     * Determine method availability based on quote amount and config data 
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
        // Simplify Commerce API keys required
        if (!$this->_publicKey) {
            $this->_log->debug("Payment method not available: public API key not specified");
            return false;
        }
        if (!$this->_privateKey) {
            $this->_log->debug("Payment method not available: private API key not specified");
            return false;
        }

        // Order total must be within the specified limits
        if ($quote) {
            $total = $quote->getBaseGrandTotal();
            if ($total < $this->_minAmount || ($this->_maxAmount && $total > $this->_maxAmount)) {
                $this->_log->debug("Payment method not available: order value out of range");
                return false;
            }
        } 

        // Some credit card types must be selected
        $ccTypes = explode(',', $this->getConfigData("cctypes"));
        if (!($ccTypes || $ccTypes.length == 0)) {
            $this->_log->debug("No credit cards enabled in store configuration");
            return false;
        }

        // Some currencies must be selected
        $currencies = explode(',', $this->getConfigData("currencies"));
        if (!($currencies || $currencies.length == 0)) {
            $this->_log->debug("No currencies enabled in store configuration");
            return false;
        }

        if ($quote) {
            $currencyCode = $quote->getBaseCurrencyCode();
            if (!$this->isCurrencySupported($currencyCode)) {
                $this->_log->debug("Unsupported currency " . $currencyCode);
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
            if (isset($additionalData["cc-number"])) {
                $payment->setAdditionalInformation("cc-number", $additionalData["cc-number"]);
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
