<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Product_Label_Position
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'top-left', 'label' => Mage::helper('venedor')->__('Top Left')),
            array('value' => 'top-right', 'label' => Mage::helper('venedor')->__('Top Right')),
            array('value' => 'bottom-left', 'label' => Mage::helper('venedor')->__('Bottom Left')),
            array('value' => 'bottom-right', 'label' => Mage::helper('venedor')->__('Bottom Right'))
        );
    }
}