<?php
class Eternal_AjaxCart_Helper_data extends Mage_Core_Helper_Abstract {
    
    public function getConfig($optionString)
    {
        return Mage::getStoreConfig('eternal_ajaxcart/' . $optionString);
    }
}