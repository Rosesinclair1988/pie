<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Bootstrap_Version
{
    public function toOptionArray()
    {
        return array(
            array('value' => '2.3.2', 'label' => Mage::helper('venedor')->__('2.3.2')),
            array('value' => '3.2.0', 'label' => Mage::helper('venedor')->__('3.2.0'))
        );
    }
}