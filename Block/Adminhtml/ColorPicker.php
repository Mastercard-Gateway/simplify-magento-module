<?php
/**
 * Copyright (c) 2022 Mastercard
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

namespace MasterCard\SimplifyCommerce\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ColorPicker extends Field
{
    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html      = $element->getElementHtml();
        $value     = $element->getData('value');
        $elementId = $element->getHtmlId();
        $html      .= '<script type="text/javascript">
            require(["jquery", "tinycolor"], function($) {
                $(document).ready(function(e) {
                    $("#' . $elementId . '").css("color", "#ffffff");
                    $("#' . $elementId . '").css("background-color", "' . $value . '");
                    $("#' . $elementId . '").colpick({
                        layout: "hex",
                        submit: 0,
                        color: "' . $value . '",
                        onChange: function(hsb, hex, rgb, el, bySetColor) {
                            $(el).css("background-color", "#" + hex);
                            if (!bySetColor) {
                                $(el).val("#" + hex);
                            }
                        }
                    }).keyup(function() {
                        $(this).colpickSetColor(this.value);
                    });
                });
            });
            </script>';

        return $html;
    }
}
