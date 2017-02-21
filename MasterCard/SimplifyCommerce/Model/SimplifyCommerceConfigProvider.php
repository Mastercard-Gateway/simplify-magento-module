<?php

namespace MasterCard\SimplifyCommerce\Model;

use \Magento\Checkout\Model\ConfigProviderInterface;

/** Provider for configuration values available client-side through 
  * window.checkoutConfig.payment.simplifycommerce object 
  */
class SimplifyCommerceConfigProvider implements ConfigProviderInterface
{
    protected $gateway = null;


    public function __construct(
        \MasterCard\SimplifyCommerce\Model\PaymentGatewayFactory $gatewayFactory
    ) {
        $this->gateway = $gatewayFactory->create();
    }


    public function getConfig()
    {
        $configuration = $this->gateway->getConfiguration();
        // get rid of some of fields in saved credit cards 
        /*
        if ($configuration["customer"]["cards"]) {
                //"year"
                //"month"
                //"id"
        }
        */
        return [
            "payment" => [
                "simplifycommerce" => $configuration
            ]
        ];
    }


}
