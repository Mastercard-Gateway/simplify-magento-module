<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */
namespace MasterCard\SimplifyCommerce\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

class Pendingpayment extends Status
{
    protected $_stateStatuses = [Order::STATE_PENDING_PAYMENT, Order::STATE_PROCESSING];
}
