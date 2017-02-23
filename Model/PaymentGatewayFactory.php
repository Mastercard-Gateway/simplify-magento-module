<?php

namespace MasterCard\SimplifyCommerce\Model;

class PaymentGatewayFactory {
    protected $_objectManager = null;
    protected $_instanceName = null;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = "\\MasterCard\\SimplifyCommerce\\Model\\SimplifyCommerceGateway") {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;        
    }

    public function create(array $data = array()) {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}

