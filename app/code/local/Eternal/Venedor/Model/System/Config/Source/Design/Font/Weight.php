<?php

class Eternal_Venedor_Model_System_Config_Source_Design_Font_Weight
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'lighter',    'label' => Mage::helper('venedor')->__('Lighter')),
            array('value' => 'normal',    'label' => Mage::helper('venedor')->__('Normal')),
            array('value' => 'bold',      'label' => Mage::helper('venedor')->__('Bold')),
            array('value' => 'bolder',    'label' => Mage::helper('venedor')->__('Bolder'))
        );
    }
}
