<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Bootstrap_Layout
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'fixed', 'label' => Mage::helper('venedor')->__('Fixed')),
            array('value' => 'fluid', 'label' => Mage::helper('venedor')->__('Fluid'))
        );
    }
}