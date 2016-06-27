<?php

class Eternal_Brands_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getConfig($optionString)
    {
        return Mage::getStoreConfig('eternal_brands/' . $optionString);
    }
}
