<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MasterCard\SimplifyCommerce\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

class Pendingpayment extends Status
{
    protected $_stateStatuses = [Order::STATE_PENDING_PAYMENT, Order::STATE_PROCESSING];
}
