<?php
/**
 * Copyright (c) 2019 Mastercard. Licensed under Open Software License ("OSL") v. 3.0.
 * See file LICENSE.txt or go to https://opensource.org/licenses/OSL-3.0 for full license details.
 */

namespace MasterCard\SimplifyCommerce\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class FormType implements ArrayInterface
{
    const MODAL = 0;
    const EMBED = 1;

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::MODAL,
                'label' => __('Modal Payment Form')
            ],
            [
                'value' => self::EMBED,
                'label' => __('Embed Payment Form')

            ]
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            self::MODAL => __('Modal Payment Form'),
            self::EMBED => __('Embed Payment Form')
        ];
    }
}
