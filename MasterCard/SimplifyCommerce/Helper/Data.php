<?php
namespace MasterCard\SimplifyCommerce\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue($config_path,  \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
