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
class Miura_MenuPlus_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getLabelCategoryLink($categoyName) {
        $label = Mage::getStoreConfig('menuplus/link/l') ? Mage::getStoreConfig('menuplus/link/l') : $this->__('Show category products');
        $label = str_replace('{{category_name}}', $categoyName, $label);

        return $label;
    }

}
