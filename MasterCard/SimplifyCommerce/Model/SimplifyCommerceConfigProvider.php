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
        // get rid of confidential fields 
        $configuration = unserialize(serialize($this->gateway->getConfiguration()));
        unset($configuration["privateKey"]);
        foreach ($configuration["customer"]["cards"] as $last4 => $card) {
            unset($card["year"]);
            unset($card["month"]);
            unset($card["cid"]);
            $configuration["customer"]["cards"][$last4] = $card;
        }

        return [
            "payment" => [
                "simplifycommerce" => $configuration
            ]
        ];
    }


}
