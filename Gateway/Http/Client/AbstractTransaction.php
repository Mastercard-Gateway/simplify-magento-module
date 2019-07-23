<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientException;
use MasterCard\SimplifyCommerce\lib\SimplifyAdapterFactory;
use Magento\Payment\Model\Method\Logger;

abstract class AbstractTransaction implements ClientInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var SimplifyAdapterFactory
     */
    protected $adapterFactory;

    /**
     * AbstractTransaction constructor.
     * @param Logger $logger
     * @param SimplifyAdapterFactory $adapterFactory
     */
    public function __construct(
        Logger $logger,
        SimplifyAdapterFactory $adapterFactory
    ) {
        $this->logger = $logger;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     * @return array
     * @throws ClientException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();
        $log = [
            'request' => $data,
            'client' => static::class
        ];
        $response['object'] = [];

        try {
            $response['object'] = $this->process($data);
        } catch (\Simplify_ApiException $e) {
            $log['field_errors'] = [];
            $log['exception'] = $e->getMessage();
            if ($e instanceof \Simplify_BadRequestException && $e->hasFieldErrors()) {
                foreach ($e->getFieldErrors() as $fieldError) {
                    $log['field_errors'][] = $fieldError->getFieldName()
                        . ": '" . $fieldError->getMessage()
                        . "' (" . $fieldError->getErrorCode()
                        . ")";
                }
            }
        } catch (\Exception $e) {
            $message = __($e->getMessage() ?: 'Sorry, but something went wrong');
            $log['exception'] = $message;
            throw new ClientException($message);
        } finally {
            $log['response'] = (array) $response['object'];
            $this->logger->debug($log);
        }

        return $response;
    }

    /**
     * @param array $data
     * @return mixed
     */
    abstract protected function process(array $data);
}
