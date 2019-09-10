<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

namespace MasterCard\SimplifyCommerce\Gateway\Http\Client;

class CustomerCreate extends AbstractTransaction
{
    /**
     * @param array $data
     * @return mixed
     */
    protected function process(array $data)
    {
        return $this->adapterFactory->create()
            ->createCustomer($data);
    }
}
