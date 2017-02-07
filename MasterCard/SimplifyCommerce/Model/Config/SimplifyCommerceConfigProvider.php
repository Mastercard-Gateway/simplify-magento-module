<?php

namespace MasterCard\SimplifyCommerce\Model\Config;

use Magento\Checkout\Model\ConfigProviderInterface;
use \Magento\Customer\Helper\Session\CurrentCustomer;

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
                    "customerName" => $this->getCustomerName($customer),
                    "customerEmail" => $this->getCustomerEmail($customer),
                    "customerSavedCreditCard" => $this->customerSavedCreditCard($customer)
                ]
            ]
        ];
        return $config;
    }

    /** Retrieves a value from payment method configuration */
    private function getConfigValue($key) {
      return $this->method->getConfigData($key);
    }

    /** Returns true if Simplify Hosted Payments enabled */
    private function getHostedPaymentsEnabled() {
      return (bool)$this->getConfigValue("simplify_hostedpayments");
    }

    /** Returns Simplify Commerce Public API Key */
    private function getPublicAPIKey() {
        return $this->getConfigValue("public_key");
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
        $customer_id = $this->currentCustomer->getCustomerId();
        if (isset($customer_id)) { 
            $customer = $this->currentCustomer->getCustomer();
            return $customer;
        }
    }

    /** Returns Simplify Customer ID for the currently logged in customer.
      * This can be used to execute payments using a previously stored credit card */
    private function customerSavedCreditCard($customer) {
        if (is_null($customer)) {
            return false;
        } 
        else {
            $attrs = $customer->getCustomAttributes();
            return isset($attrs["simplifycommerce_customer_id"]);
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
