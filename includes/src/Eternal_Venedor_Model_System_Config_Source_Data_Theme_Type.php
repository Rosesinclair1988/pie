<?php

class Eternal_Venedor_Model_System_Config_Source_Data_Theme_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'green', 'label' => Mage::helper('venedor')->__('Green')),
            array('value' => 'blue', 'label' => Mage::helper('venedor')->__('Blue')),
            array('value' => 'orange', 'label' => Mage::helper('venedor')->__('Orange')),
            array('value' => 'pink', 'label' => Mage::helper('venedor')->__('Pink'))
        );
    }
}