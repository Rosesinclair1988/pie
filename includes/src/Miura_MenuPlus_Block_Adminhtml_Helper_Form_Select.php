<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_GiftMessage
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml additional helper block for product configuration
 *
 * @category   Mage
 * @package    Mage_GiftMessage
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Miura_MenuPlus_Block_Adminhtml_Helper_Form_Select extends Varien_Data_Form_Element_Select {

    /**
     * Retrieve element html
     *
     * @return string
     */
    public function getElementHtml() {
        $disabled = false;
        if ($this->getValue() == '') {
            $this->setValue($this->_getValueFromConfig());
            $disabled = true;
        }

        $html = parent::getElementHtml();
        $htmlId = 'use_config_' . $this->getHtmlId();
        $html .= '<input id="' . $htmlId . '" name="use_config[]" value="' . $this->getId() . '"';
        $html .= ($disabled ? ' checked="checked"' : '');

        if ($this->getReadonly()) {
            $html .= ' disabled="disabled"';
        }

        $html .= ' onclick="toggleValueElements(this, this.parentNode);" class="checkbox" type="checkbox" />';
        $html .= ' <label for="' . $htmlId . '" class="normal">' . Mage::helper('adminhtml')->__('Use Config Settings') . '</label>';
        $html .= '<script type="text/javascript">toggleValueElements($(\'' . $htmlId . '\'), $(\'' . $htmlId . '\').parentNode);</script>';

        return $html;
    }

    /**
     * Get config value data
     *
     * @return mixed
     */
    protected function _getValueFromConfig() {
        $storeconfigName = str_replace(array('mp', '_'), array('menuplus', '/'), $this->getData('name'));
        return Mage::getStoreConfig($storeconfigName);
    }
}
