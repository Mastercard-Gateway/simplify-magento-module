<?php

namespace MasterCard\SimplifyCommerce\Model\Source;

/** Available payment actions */
class Action
{
    public function toOptionArray()
    {
        return array(
            // Authorize and capture the payment in one go
            array(
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => 'Authorize and Capture'
            ),
            // Only authorize the payment
            array(
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
                'label' => 'Authorize Only'
            )
        );
    }
}
