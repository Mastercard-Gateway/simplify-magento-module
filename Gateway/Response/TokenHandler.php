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

namespace MasterCard\SimplifyCommerce\Gateway\Response;

use DateInterval;
use DateTime;
use DateTimeZone;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Zend_Json_Decoder;
use Zend_Json_Encoder;

class TokenHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenInterfaceFactory
     */
    private $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * TokenHandler constructor.
     * @param PaymentTokenInterfaceFactory $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     */
    public function __construct(
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws \Zend_Json_Exception
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $vaultEnabled = $payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE);

        if ($order->getCustomerId() && $vaultEnabled) {
            $token = $this->getPaymentToken($payment);
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($token);
        }
    }

    /**
     * Get payment extension attributes
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    /**
     * @param InfoInterface $payment
     * @return mixed
     * @throws \Zend_Json_Exception
     */
    private function getPaymentToken(InfoInterface $payment)
    {
        $customer = Zend_Json_Decoder::decode($payment->getAdditionalInformation('customer'));

        $token = $this->paymentTokenFactory->create();
        $token->setGatewayToken($customer['id']);
        $token->setTokenDetails(Zend_Json_Encoder::encode([
            'type' => $this->getCcTypeFromBrand($customer['type']),
            'last4' => $customer['last4'],
            'expMonth' => $customer['expMonth'],
            'expYear' => $customer['expYear']
        ]));
        $token->setExpiresAt($this->getExpirationDate(
            $customer['expMonth'],
            $customer['expYear']
        ));

        return $token;
    }

    /**
     * @param string $brand
     * @return string
     */
    private static function getCcTypeFromBrand($brand)
    {
        $brands = [
            'MASTERCARD' => 'MC',
            'VISA' => 'VI',
            'AMERICAN_EXPRESS' => 'AE',
            'DINERS' => 'DN',
            'DISCOVER' => 'DI',
            'JCB' => 'JCB',
            'MAESTRO' => 'SM',
        ];
        return isset($brands[$brand]) ? $brands[$brand] : $brand;
    }

    /**
     * @param string $exprMonth
     * @param string $exprYear
     * @return string
     * @throws \Exception
     */
    private function getExpirationDate($exprMonth, $exprYear)
    {
        $expDate = new DateTime(
            $exprYear
            . '-'
            . $exprMonth
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new DateTimeZone('UTC')
        );
        $expDate->add(new DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }
}
