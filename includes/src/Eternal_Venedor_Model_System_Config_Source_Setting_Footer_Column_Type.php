<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Footer_Column_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'default', 'label' => Mage::helper('venedor')->__('Default Links')),
            array('value' => 'custom', 'label' => Mage::helper('venedor')->__('Static Block')),
            array('value' => 'twitter', 'label' => Mage::helper('venedor')->__('Twitter Tweets')),
            array('value' => 'facebook', 'label' => Mage::helper('venedor')->__('Facebook Like Box'))
        );
    }
}