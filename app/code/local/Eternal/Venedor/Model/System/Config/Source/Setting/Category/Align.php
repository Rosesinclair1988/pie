<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Category_Align
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'left', 'label' => Mage::helper('venedor')->__('Left')),
            array('value' => 'center', 'label' => Mage::helper('venedor')->__('Center'))
        );
    }
}