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

namespace MasterCard\SimplifyCommerce\Controller\Simplify;

use Exception;
use InvalidArgumentException;
use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;
use Zend_Json_Encoder;

class PlaceOrder extends Action
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AgreementsValidatorInterface
     */
    protected $agreementsValidator;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * Redirect constructor.
     * @param Context $context
     * @param ConfigInterface $config
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     * @param CartManagementInterface $cartManagement
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        CartManagementInterface $cartManagement,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->cartManagement = $cartManagement;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->config->getValue('active')) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('noRoute');

            return $resultRedirect;
        }

        return parent::dispatch($request);
    }

    /**
     * @param CartInterface $quote
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateQuote($quote)
    {
        if (!$quote || !$quote->getItemsCount()) {
            throw new InvalidArgumentException(__('We can\'t initialize checkout.'));
        }
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $quote = $this->checkoutSession->getQuote();

        try {
            $checkoutMethod = Onepage::METHOD_GUEST;
            if ($this->customerSession->isLoggedIn()) {
                $checkoutMethod = Onepage::METHOD_REGISTER;
            }
            $quote->setCheckoutMethod($checkoutMethod);

            $this->validateQuote($quote);

            $quote->getPayment()->setAdditionalInformation('response', Zend_Json_Encoder::encode([
                'cardToken' => $this->getRequest()->getParam('cardToken')
            ]));

            $this->cartManagement->placeOrder($quote->getId());

            /** @var Redirect $resultRedirect */
            return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
        } catch (Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addExceptionMessage(
                $e,
                __('The order #%1 cannot be processed.', $quote->getReservedOrderId())
            );
        }

        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }
}
