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
        Simplify::$userAgent = 'Magento-3.0.1';
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
