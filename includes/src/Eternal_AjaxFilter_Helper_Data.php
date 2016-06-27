<?php

class Eternal_AjaxFilter_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getConfig($optionString)
    {
        return Mage::getStoreConfig('eternal_ajaxfilter/' . $optionString);
    }
}
