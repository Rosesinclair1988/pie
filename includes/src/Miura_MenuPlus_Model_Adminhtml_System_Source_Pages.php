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
class Miura_MenuPlus_Model_Adminhtml_System_Source_Pages extends Mage_Eav_Model_Entity_Attribute_Source_Abstract {

    public function toOptionArray() {

        $collection = Mage::getModel('cms/page')->getCollection();
        $codePages = array();

        foreach ($collection as $page) {
            $identifier = $page->getIdentifier();
            if (!in_array($identifier, $codePages)) {
                $codePages[] = $identifier;
            }
        }

        sort($codePages);

        $options = array();

        array_unshift($options, array(
            'value' => '',
            'label' => Mage::helper('core')->__('-- Please Select --'))
        );

        foreach ($codePages as $codePage) {
            $options[] = array(
                'label' => $codePage,
                'value' => $codePage,
            );
        }

        return $options;
    }

    public function getAllOptions() {
        if (!$this->_options) {
            $this->_options = $this->toOptionArray();
        }
        return $this->_options;
    }

}

?>
