<?php
/**
 * Copyright (c) 2013-2019 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Command;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use MasterCard\SimplifyCommerce\lib\SimplifyAdapterFactory;
use Zend_Json_Decoder;

class CaptureStrategyCommand implements CommandInterface
{
    const TOKEN_SALE = 'token_sale';
    const CUSTOMER_SALE = 'customer_sale';
    const CAPTURE = 'settlement';
    const VAULT_CAPTURE = 'vault_capture';
    const CREATE_CUSTOMER_COMMAND = 'create_customer';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var SimplifyAdapterFactory
     */
    private $simplifyAdapterFactory;

    /**
     * CaptureStrategyCommand constructor.
     * @param CommandPoolInterface $commandPool
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionRepositoryInterface $transactionRepository
     * @param SimplifyAdapterFactory $simplifyAdapterFactory
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionRepositoryInterface $transactionRepository,
        SimplifyAdapterFactory $simplifyAdapterFactory
    ) {
        $this->commandPool = $commandPool;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->simplifyAdapterFactory = $simplifyAdapterFactory;
    }

    /**
     * @param array $commandSubject
     * @return \Magento\Payment\Gateway\Command\ResultInterface|void|null
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);

        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        ContextHelper::assertOrderPayment($payment);

        $vaultEnabled = $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE);

        if ($order->getCustomerId() && !$payment->getAuthorizationTransaction() && $vaultEnabled) {
            $this->commandPool
                ->get(self::CREATE_CUSTOMER_COMMAND)
                ->execute($commandSubject);
        }

        return $this->commandPool
            ->get($this->getCommand($paymentDO))
            ->execute($commandSubject);
    }

    /**
     * @param PaymentDataObjectInterface $paymentDO
     * @return string
     */
    private function getCommand(PaymentDataObjectInterface $paymentDO)
    {
        $payment = $paymentDO->getPayment();

        // if auth transaction does not exist then execute authorize&capture command
        $existsCapture = $this->isExistsCaptureTransaction($payment);
        if (!$payment->getAuthorizationTransaction() && !$existsCapture) {
            $customer = $payment->getAdditionalInformation('customer');
            if ($customer) {
                $customer = Zend_Json_Decoder::decode($customer);
                if (isset($customer['id']) && $customer['id']) {
                    return self::CUSTOMER_SALE;
                }
            }
            return self::TOKEN_SALE;
        }

        // do capture for authorization transaction
        if (!$existsCapture && !$this->isExpiredAuthorization($payment, $paymentDO->getOrder())) {
            return self::CAPTURE;
        }

        // process capture for payment via Vault
        return self::VAULT_CAPTURE;
    }

    /**
     * Checks if authorization transaction does not expired yet.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderAdapterInterface $orderAdapter
     * @return bool
     */
    private function isExpiredAuthorization(OrderPaymentInterface $payment, OrderAdapterInterface $orderAdapter)
    {
        $adapter = $this->simplifyAdapterFactory->create();
        $txn = $adapter->authorizationFind($payment->getLastTransId());

        if (!$txn->expirationDate) {
            return false;
        }

        return $txn->expirationDate < time();
    }

    /**
     * Check if capture transaction already exists
     *
     * @param OrderPaymentInterface $payment
     * @return bool
     */
    private function isExistsCaptureTransaction(OrderPaymentInterface $payment)
    {
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('payment_id')
                    ->setValue($payment->getId())
                    ->create(),
            ]
        );

        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('txn_type')
                    ->setValue(TransactionInterface::TYPE_CAPTURE)
                    ->create(),
            ]
        );

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $count = $this->transactionRepository->getList($searchCriteria)->getTotalCount();
        return (boolean) $count;
    }
}
