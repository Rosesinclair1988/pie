<?php
class Eternal_CustomMenu_Helper_Data extends Mage_Core_Helper_Abstract
{
    // get config option
    public function getConfig($optionName) 
    {
        return Mage::getStoreConfig('eternal_custommenu/' . $optionName);
    }
}
