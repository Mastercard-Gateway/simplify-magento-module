<?php

namespace MasterCard\SimplifyCommerce\Model;

require_once("Simplify.php");
require_once("PaymentGateway.php");

use \Exception;
use Simplify as SC;
use Simplify_Payment as SC_Payment;
use Simplify_Authorization as SC_Authorization;
use Simplify_Refund as SC_Refund;
use Simplify_Customer as SC_Customer;
use Simplify_ObjectNotFoundException as SC_ObjectNotFoundException;


/** Simplify Commerce Payments gateway */
class SimplifyCommerceGateway extends \Magento\Framework\Model\AbstractExtensibleModel implements PaymentGateway {
    const CODE = 'simplifycommerce';
    const NAME = 'MasterCard_SimplifyCommerce';
    protected $version = null;
    protected $logger = null;
    protected $developerMode = false;  
    protected $configuration = null;
    protected $customerExtensionFactory = null;
    protected $customerRepository = null;
    protected $customer = null;
    protected $storeManager = null;
    protected $scopeConfig = null;
    protected $savedCards = null;
    protected $cache = null;

    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, 
        \Magento\Customer\Api\Data\CustomerExtensionFactory $customerExtensionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->logger = $logger;
        $this->cache = $cache;
        $moduleInfo = $moduleList->getOne(self::NAME);
        $this->version = $moduleInfo["setup_version"];
        $this->developerMode = $appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER;
        $this->customerExtensionFactory = $customerExtensionFactory;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        if ($currentCustomer->getCustomerId())       
            $this->customer = $customerRepository->getById($currentCustomer->getCustomerId());
        $this->initialize();
    }


    /** Outputs message and optional data to debug log */
    private function log($message, $data = null) {
        $this->logger->debug([
            "message" => "Simplify Commerce Gateway: " . $message,
            "data" => $data
        ], null, $this->developerMode);
    }


    /** Retrieves a value from payment method configuration */
    private function getConfigValue($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/' . self::CODE . '/' . $field;
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }


    /** Returns identifier of the current store */
    private function getStore()
    {
        return $this->getData('store');
    }


    /** Returns store name */
    private function getStoreName() {
        return $this->storeManager->getStore()->getName();
    }


    /** Returns customer name */
    private function getCustomerName() {
        return is_null($this->customer) ? null : ($this->customer->getFirstName() . " " . $this->customer->getLastName());
    }


    /** Returns customer name */
    private function getCustomerEmail() {
        return is_null($this->customer) ? null : $this->customer->getEmail();
    }


    /** Returns gateway version */
    public function getVersion() {
        return $this->version;        
    }


    /** Validates the gateway, if validation fails, returns error message */
    public function validate() {
        if (!$this->configuration) {
            return "Simplify Commerce payment method configuration missing";
        }
        if (!$this->configuration["publicKey"]) {
            return "Simplify Commerce configuration error: public API key not specified";
        }
        if (!$this->configuration["privateKey"]) {
            return "Simplify Commerce configuration error: private API key not specified";
        }
        if (!$this->configuration["paymentAction"]) {
            return "Simplify Commerce configuration error: payment action not specified";
        }
        return null;
    }


    /** Initializes the gateway */
    private function initialize() {
        // validate configuration
        $this->configuration = $this->getConfiguration();
        $configurationErrors = $this->validate();
        if ($configurationErrors) {
        }
        else {            
            // Configure Simplify Commerce API
            SC::$publicKey = $this->configuration["publicKey"]; 
            SC::$privateKey = $this->configuration["privateKey"];
            SC::$userAgent = "Magento-" + $this->version; 

            // Get customer's credit cards
            if (!$this->savedCards) {
                $this->savedCards = $this->getSavedCreditCards();
                $this->configuration["customer"]["cards"] = $this->savedCards;
            }

            // Optionally validate merchant account        
            if (false) {
                try {
                    $cid = "-invalid-customer-";
                    $c = SC_Customer::findCustomer($cid);
                } 
                catch (SC_ObjectNotFoundException $e) {
                }
                catch (Exception $e) {
                }
                if ($result) {
                    $this->log("Merchant account valid");
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__("Cannot validate Simplify Commerce merchant account"));
                }
            }
        }
    }


    /** Returns request builder for creating Simplify Commerce requests */
    private function getRequestBuilder(\Magento\Payment\Model\InfoInterface $payment, $amount = 0) {
        return new SimplifyCommerceRequestBuilder($payment, $amount);
    }


    /** Returns gateway configuration */
    public function getConfiguration($force = false) {
        if ($this->configuration && !$force) {
            return $this->configuration;
        } 
        else {
            return [
                "publicKey" => $this->getConfigValue("public_key"),
                "privateKey" => $this->getConfigValue("private_key"),
                "paymentAction" => $this->getConfigValue("payment_action"),
                "minOrderTotal" => floatval($this->getConfigValue("min_order_total")),
                "maxOrderTotal" => floatval($this->getConfigValue("max_order_total")),
                "canSaveCard" => (bool)$this->getConfigValue("customer_save_credit_card"),
                "hostedPaymentsEnabled" => (bool)$this->getConfigValue("simplify_hostedpayments"),
                "ccTypes" => explode(',', $this->getConfigValue("cctypes")),
                "currencies" => explode(',', $this->getConfigValue("currencies")),
                "isDeveloperMode" => $this->developerMode,
                "storeName" => $this->getStoreName(),
                "customer" => [
                    "name" => $this->getCustomerName(),
                    "email" => $this->getCustomerEmail(),
                    "cards" => $this->savedCards
                ]
            ];
        }
    }


    /** Returns true if order amount is within the allowed ranges */
    public function isOrderAmountValid($amount) {
        $result = true;
        if ($amount) {
            if ($this->configuration["minOrderTotal"] > 0) {
                $result = $amount >= $this->configuration["minOrderTotal"];
            }
            if ($result && $this->configuration["maxOrderTotal"] > 0) {
                $result = $amount <= $this->configuration["maxOrderTotal"];
            }
        }
        return $result;
    }

    /** Returns true if saving customer cards is available. 
        The setting will only apply when hosted payments are used! */
    public function canSaveCard() {
        return $this->configuration["hostedPaymentsEnabled"] && 
               $this->configuration["canSaveCard"];
    }


    /** Retrieves stored credit cards associated with the current customer */
    public function getSavedCreditCards() {
        $savedCreditCards = [];
        if ($this->customer && $this->customer->getEmail() && $this->canSaveCard()) {
            // try cached dataset
            $cacheid = $this->customer->getEmail() . "-simplify-cc";
            $savedCreditCards = unserialize($this->cache->load($cacheid));
            if ($savedCreditCards) {
            }
            else {
                // try Simplify Commerce API
                $customers = SC_Customer::listCustomer([
                    "filter" => [
                        "text"=> $this->customer->getEmail()
                    ]
                ]);
                if ($customers) {
                    foreach ($customers->list as $customer) {
                        if ($customer->card) {
                            $card = [
                                "id" => $customer->id,
                                "type" => $customer->card->type,
                                "last4" => $customer->card->last4,
                                "year" => $customer->card->expYear,
                                "month" => $customer->card->expMonth,
                            ];
                            if (!isset($savedCreditCards[$card["last4"]]))
                                $savedCreditCards[$card["last4"]] = $card;
                        }
                    }
                    // store in cache
                    $this->cache->save(serialize($savedCreditCards), $cacheid, [], null);
                }
            }
        }
        return $savedCreditCards;
    }


    /** Finds a saved credit card with the specified last four digits */
    public function getSavedCreditCard($last4) {
        $savedCreditCard = null;
        if ($this->savedCards) {
            foreach ($this->savedCards as $card) {            
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
        $requestBuilder = $this->getRequestBuilder($payment);

        // Proceed if saving cards enabled in store configuration, customer is logged in 
        // and all data required for saving the card available in the payment
        if ($this->customer && $requestBuilder->canSaveCard() && $this->canSaveCard()) {
            // ... but ignore if the specified card has already been saved
            if ($this->savedCards && isset($this->savedCards[$requestBuilder->last4])) {
                $savedCreditCard = $this->savedCards[$requestBuilder->last4];
            }
            else {
                $result = null;
                $request = $requestBuilder->getCustomerCreateRequest($this->customer);
                if ($request) {
                    $this->log("Customer create request", $request);
                    $result = SC_Customer::createCustomer($request);
                }
                if ($result && $result->id) {
                    $savedCreditCard = [
                        "id" => $result->id,
                        "last4" => $result->card->last4,
                        "year" => $result->card->expYear,
                        "month" => $result->card->expMonth,
                    ];
                    // store in saved cards
                    $this->savedCards[$savedCreditCard["last4"]] = $savedCreditCard;
                    // invalidate cache
                    $cacheid = $this->customer->getEmail() . "-simplify-cc";
                    $this->cache->remove($cacheid);
                }
            }
        }
        
        return $savedCreditCard;
    }


    /** Authorizes payment */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {       
        $this->log("Authorizing payment ...");

        $result = [            
            "error" => false,
            "status" => null,
            "details" => null,
            "last4" => null,
            "transactionId" => null,
            "parentTransactionId" => null
        ];

        try {
            $requestBuilder = $this->getRequestBuilder($payment, $amount);

            $savedCard = null;
            if ($requestBuilder->useSavedCard) {
                // Use saved card if selected
                $savedCard = $this->getSavedCreditCard($requestBuilder->useSavedCard);
                if ($savedCard) {
                    $requestBuilder->cardId = $savedCard["id"];
                }
            }
            else {
                // Save card if user asked
                if ($requestBuilder->canSaveCard()) {
                    $savedCard = $this->saveCreditCard($payment);
                }
            }
            // Pass card identifier to request builder, so that the payment is performed with it.
            // Card token has already been used for saving the card, so payment with it would fail.
            if ($savedCard) {
                $requestBuilder->cardId = $savedCard["id"];
            }

            // Authorize payment
            $request = $requestBuilder->getPaymentCreateRequest();
            $this->log("Authorization request", $request);
            $response = SC_Authorization::createAuthorization($request);
            if ($response->paymentStatus == "APPROVED") {
                $this->log("Authorization approved", $response);
                $result["status"] = $response->paymentStatus;
                $result["last4"] = $response->card->last4;
                $result["transactionId"] = $response->id;
            } else {
                $this->log("Authorization declined", $response);
                $result["error"] = true;
                $result["status"] = $response->paymentStatus;
                $result["details"] = $response->declineReason;
            }
        }
        catch (Exception $e) {
            $result["error"] = true;
            $result["status"] = "ERROR";
            $result["details"] = $e->getMessage();
        }

        return $result;
    }


    /** Captures payment */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $this->log("Capturing payment ...");

        $result = [            
            "error" => false,
            "status" => null,
            "details" => null,
            "last4" => null,
            "transactionId" => null,
            "parentTransactionId" => null
        ];

        try {
            $requestBuilder = $this->getRequestBuilder($payment, $amount);

            $savedCard = null;
            if ($requestBuilder->useSavedCard) {
                // Use saved card if selected
                $savedCard = $this->getSavedCreditCard($requestBuilder->useSavedCard);
                if ($savedCard) {
                    $requestBuilder->cardId = $savedCard["id"];
                }
            }
            else {
                // Save card if user asked
                if ($requestBuilder->canSaveCard()) {
                    $savedCard = $this->saveCreditCard($payment);
                }
            }
            // Pass card identifier to request builder, so that the payment is performed with it.
            // Card token has already been used for saving the card, so payment with it would fail.
            if ($savedCard) {
                $requestBuilder->cardId = $savedCard["id"];
            }

            // Create payment            
            $request = $requestBuilder->getPaymentCreateRequest();
            $parentTransactionId = $requestBuilder->parentTransactionId;
            $this->log("Payment request", $request);
            $response = SC_Payment::createPayment($request);
            if ($response->paymentStatus == "APPROVED") {
                $this->log("Payment approved", $response);
                $result["status"] = $response->paymentStatus;
                $result["last4"] = $response->card->last4;
                $result["transactionId"] = $response->id;
                $result["parentTransactionId"] = isset($parentTransactionId) ? $parentTransactionId : $response->id;
            } else {
                $this->log("Payment declined", $response);
                $result["error"] = true;
                $result["status"] = $response->paymentStatus;
                $result["details"] = $response->declineReason;
            }
        }
        catch (Exception $e) {
            $result["error"] = true;
            $result["status"] = "ERROR";
            $result["details"] = $e->getMessage();
        }

        return $result;
    }

    /** Void the payment */
    public function void(\Magento\Payment\Model\InfoInterface $payment) {        
        $this->log("Voiding payment ...");

        $result = [            
            "error" => false,
            "status" => null,
            "details" => null
        ];

        try {
            $authorization = null;
            $requestBuilder = $this->getRequestBuilder($payment);
            if ($requestBuilder->transactionId) {
                $authorization = SC_Authorization::findAuthorization($requestBuilder->transactionId);
                if ($authorization) {
                    $response = SC_Authorization::deleteAuthorization($authorization);
                    $this->log("Void approved", $response);
                    $result["status"] = "VOIDED";
                }
            }
            if (!$authorization) {                
                throw new \Magento\Framework\Exception\LocalizedException(__("Authorization transaction not found"));
            }
        }
        catch (Exception $e) { 
            $result["error"] = true;
            $result["status"] = "ERROR";
            $result["details"] = $e->getMessage();
        }

        return $result;
    }

    /** Refund the payment */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $this->log("Refunding payment ...");

        $result = [            
            "error" => false,
            "status" => null,
            "details" => null,
            "last4" => null,
            "transactionId" => null,
            "parentTransactionId" => null
        ];

        try {
            $requestBuilder = $this->getRequestBuilder($payment, $amount);
            $request = $requestBuilder->getRefundCreateRequest();
            $this->log("Refund request", $request);
            $response = SC_Refund::createRefund($request);
            if ($response->id) {
                $this->log("Refund approved", $response);
                $result["status"] = "APPROVED";
                $result["transactionId"] = $response->id;
                $result["parentTransactionId"] = $response->payment->id;
            }
            else {
                throw new \Magento\Framework\Exception\LocalizedException(__("Refund not processed"));
            }
        }
        catch (Exception $e) { 
            $result["error"] = true;
            $result["status"] = "ERROR";
            $result["details"] = $e->getMessage();
        }

        return $result;
    }

}



/*
 * Request builder, for creating Simplify Commerce API requests 
*/
class SimplifyCommerceRequestBuilder
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
            $this->last4 = $payment->getAdditionalInformation("cc-last4");
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
    public function getRefundCreateRequest($reason = null) {
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

