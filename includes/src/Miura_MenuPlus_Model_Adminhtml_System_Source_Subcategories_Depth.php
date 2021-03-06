<?php

/**
 * Miura Menu+ Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Miura
 * @package    Miura_MenuPlus
 * @author     Clement Larduinat
 * @copyright  Copyright (c) 2011 Miura Conseil (http://www.miura-conseil.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Miura_MenuPlus_Model_Adminhtml_System_Source_Subcategories_Depth extends Mage_Eav_Model_Entity_Attribute_Source_Abstract {

    public function toOptionArray() {
        return array(
            array(
                'label' => "1 " . Mage::helper('menuplus')->__("level"),
                'value' => 1),
            array(
                'label' => "2 " . Mage::helper('menuplus')->__("levels"),
                'value' => 2),
            array(
                'label' => "3 " . Mage::helper('menuplus')->__("levels"),
                'value' => 3),
            array(
                'label' => "4 " . Mage::helper('menuplus')->__("levels"),
                'value' => 4)
        );
    }
    
    public function getAllOptions(){
        if (!$this->_options) {
            $this->_options = $this->toOptionArray();
        }
        return $this->_options;
    }

}

?>
