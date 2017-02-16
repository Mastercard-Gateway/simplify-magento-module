<?php

namespace MasterCard\SimplifyCommerce\Model;

require_once("Simplify.php");

use Magento\Checkout\Model\ConfigProviderInterface;
use \Magento\Customer\Helper\Session\CurrentCustomer;
use Simplify as SC;
use Simplify_Customer as SC_Customer;

/** Provider for configuration values available client-side through 
  * window.checkoutConfig.payment.simplifycommerce object 
  */
class SimplifyCommerceConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = \MasterCard\SimplifyCommerce\Model\Payment::CODE;
    protected $method;
    protected $currentCustomer;
    protected $storeManager;

    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        CurrentCustomer $currentCustomer,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->currentCustomer = $currentCustomer;
        $this->storeManager = $storeManager;

        SC::$publicKey = $this->getPublicAPIKey();
        SC::$privateKey = $this->getPrivateAPIKey();
        SC::$userAgent = "Magento-2.1.0";
    }

    /** Returns custom configuration values */
    public function getConfig()
    {
        $customer = $this->getCustomer();
        $config = [
            "payment" => [
                "simplifycommerce" => [
                    "hostedPaymentsEnabled" => $this->getHostedPaymentsEnabled(),
                    "canSaveCard" => $this->canSaveCard(),
                    "publicAPIKey" => $this->getPublicAPIKey(),
                    "isDeveloperMode" => $this->isDeveloperMode(),
                    "storeName" => $this->storeManager->getStore()->getName(),
                    "customer" => [
                        "name" => $this->getCustomerName($customer),
                        "email" => $this->getCustomerEmail($customer),
                        "cards" => $this->getSavedCreditCards($customer),
                    ]
                ]
            ]
        ];
        return $config;
    }


    /** Retrieves stored credit cards associated with the current customer */
    public function getSavedCreditCards($customer) {        
        $savedCreditCards = [];
        if ($this->getConfigValue("customer_save_credit_card") && $customer && $customer->getEmail()) {
            $customers = SC_Customer::listCustomer([
                "filter" => [
                    "text"=> $customer->getEmail()
                ]
            ]);
            if ($customers) {
                foreach ($customers->list as $item) { 
                    if ($item->card) {
                        $card = [
                            "type" => $item->card->type,
                            "last4" => $item->card->last4,
                            "selected" => false
                        ];
                        if (!isset($savedCreditCards[$card["last4"]]))
                            $savedCreditCards[$card["last4"]] = $card;
                    }
                }
            }
        }
        return $savedCreditCards;
    }


    /** Retrieves a value from payment method configuration */
    private function getConfigValue($key) {
      return $this->method->getConfigData($key);
    }

    /** Returns true if Simplify Hosted Payments enabled */
    private function getHostedPaymentsEnabled() {
      return (bool)$this->getConfigValue("simplify_hostedpayments");
    }

    /** Returns Simplify Commerce API Key */
    private function getPublicAPIKey() {
        return $this->getConfigValue("public_key");
    }
    private function getPrivateAPIKey() {
        return $this->getConfigValue("private_key");
    }

    /** Returns true if saving customer cards is enabled */
    private function canSaveCard() {
        $customer = $this->currentCustomer->getCustomerId();
        return !is_null($customer) && $this->getConfigValue("customer_save_credit_card");
    }

    /** Returns Simplify Customer ID for the currently logged in customer.
      * This can be used to execute payments using a previously stored credit card */
    private function getCustomer() {
        $customer = null;
        $customer_id = $this->currentCustomer ? $this->currentCustomer->getCustomerId() : null;
        if (isset($customer_id)) { 
            $customer = $this->currentCustomer->getCustomer();
            return $customer;
        }
    }

    /** Returns customer name */
    private function getCustomerName($customer) {
        return is_null($customer) ? null : ($customer->getFirstName() . " " . $customer->getLastName());
    }

    /** Returns customer name */
    private function getCustomerEmail($customer) {
        return is_null($customer) ? null : $customer->getEmail();
    }

	/** Detects whether Magento runs in developer mode */
	private function isDeveloperMode() {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $state = $om->get("Magento\Framework\App\State");
        return $state->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER;
	}	


}
