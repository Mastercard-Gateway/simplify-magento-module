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
