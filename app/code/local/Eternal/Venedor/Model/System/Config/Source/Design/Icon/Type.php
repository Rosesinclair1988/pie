<?php

class Eternal_Venedor_Model_System_Config_Source_Design_Icon_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'white/', 'label' => Mage::helper('venedor')->__('White')),
            array('value' => 'grey/', 'label' => Mage::helper('venedor')->__('Grey')),
            array('value' => 'black/', 'label' => Mage::helper('venedor')->__('Black'))
        );
    }
}