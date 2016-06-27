<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Product_Label_Wrapper
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'rect', 'label' => Mage::helper('venedor')->__('Rectangle')),
            array('value' => 'circle', 'label' => Mage::helper('venedor')->__('Circle'))
        );
    }
}