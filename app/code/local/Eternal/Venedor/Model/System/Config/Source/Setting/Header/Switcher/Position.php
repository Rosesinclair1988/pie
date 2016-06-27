<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Header_Switcher_Position
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'top', 'label' => Mage::helper('venedor')->__('Top')),
            array('value' => 'middle', 'label' => Mage::helper('venedor')->__('Middle'))
        );
    }
}