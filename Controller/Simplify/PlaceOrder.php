<?php
/**
 * Copyright (c) 2013-2020 Mastercard
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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
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
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Redirect constructor.
     * @param Context $context
     * @param ConfigInterface $config
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     * @param CartManagementInterface $cartManagement
     * @param CustomerSession $customerSession
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        CartManagementInterface $cartManagement,
        CustomerSession $customerSession,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->cartManagement = $cartManagement;
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->config->getValue('active')) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, '1');

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('noRoute');
            //@phpstan-ignore-next-line
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
        /** @var CartInterface|false $quote */
        if (!$quote || !$quote->getItemsCount()) {
            throw new InvalidArgumentException(__('We can\'t initialize checkout.'));
        }
    }

    /**
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    protected function setInvalidationCookie()
    {
        try {
            $store = $this->storeManager->getStore();
            $cookieMeta = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setHttpOnly(false)
                ->setDuration(86400) //24h
                ->setPath($store->getStorePath());

            $this->cookieManager->setPublicCookie('simplify_section_data_clean', '1', $cookieMeta);
        } catch (\Exception $e) {
            $this->logger->error('Simplify Commerce could not set Invalidation Cookie:' . $e->getMessage());
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

            $this->setInvalidationCookie();

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
