<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class AuthorizeStrategyCommand implements CommandInterface
{
    const AUTHORIZE_TOKEN_COMMAND = 'authorize_token';
    const AUTHORIZE_CUSTOMER_COMMAND = 'authorize_customer';
    const CREATE_CUSTOMER_COMMAND = 'create_customer';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * AuthorizeStrategyCommand constructor.
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(
        CommandPoolInterface $commandPool
    ) {
        $this->commandPool = $commandPool;
    }

    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return null|Command\ResultInterface
     * @throws CommandException
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute(array $commandSubject)
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $vaultEnabled = $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE);

        if ($order->getCustomerId() && $vaultEnabled) {
            $this->commandPool
                ->get(self::CREATE_CUSTOMER_COMMAND)
                ->execute($commandSubject);

            return $this->commandPool
                ->get(self::AUTHORIZE_CUSTOMER_COMMAND)
                ->execute($commandSubject);
        }

        return $this->commandPool
            ->get(self::AUTHORIZE_TOKEN_COMMAND)
            ->execute($commandSubject);
    }
}
