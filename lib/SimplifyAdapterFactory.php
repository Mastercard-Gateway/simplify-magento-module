<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

namespace MasterCard\SimplifyCommerce\lib;

use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\ConfigInterface;

class SimplifyAdapterFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * SimplifyAdapterFactory constructor.
     * @param ObjectManagerInterface $objectManager
     * @param ConfigInterface $config
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ConfigInterface $config
    ) {
        $this->objectManager = $objectManager;
        $this->config = $config;
    }

    /**
     * @return SimplifyAdapter
     */
    public function create()
    {
        return $this->objectManager->create(SimplifyAdapter::class, [
            'publicKey' => $this->config->getValue('public_key'),
            'privateKey' => $this->config->getValue('private_key'),
        ]);
    }
}
