<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Facebook_Color_Scheme
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'dark', 'label' => Mage::helper('venedor')->__('Dark')),
            array('value' => 'light', 'label' => Mage::helper('venedor')->__('Light'))
        );
    }
}