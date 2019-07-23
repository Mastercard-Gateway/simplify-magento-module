<?php
/**
 * Copyright (c) On Tap Networks Limited.
 */

namespace MasterCard\SimplifyCommerce\lib;

use \Simplify;
use \Simplify_Authorization;
use \Simplify_Payment;
use \Simplify_Refund;
use \Simplify_CardToken;
use \Simplify_Customer;

class SimplifyAdapter
{
    /**
     * SimplifyAdapter constructor.
     * @param string $publicKey
     * @param string $privateKey
     */
    public function __construct($publicKey, $privateKey)
    {
        Simplify::$publicKey = $publicKey;
        Simplify::$privateKey = $privateKey;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function paymentAuthorize($data)
    {
        return Simplify_Authorization::createAuthorization($data);
    }

    /**
     * @param string $txnId
     * @return mixed
     */
    public function authorizationFind($txnId)
    {
        return Simplify_Authorization::findAuthorization($txnId);
    }

    /**
     * @param string $cardId
     * @return mixed
     */
    public function findCardToken($cardId)
    {
        return Simplify_CardToken::findCardToken($cardId);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function createCustomer($data)
    {
        return Simplify_Customer::createCustomer($data);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function paymentCapture($data)
    {
        return Simplify_Payment::createPayment($data);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function paymentVoid($data)
    {
        $auth = $this->authorizationFind($data['authorization']);
        return $auth->deleteAuthorization();
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function paymentRefund($data)
    {
        return Simplify_Refund::createRefund($data);
    }
}
