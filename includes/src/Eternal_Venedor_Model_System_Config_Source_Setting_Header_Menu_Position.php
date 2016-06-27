<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Header_Menu_Position
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'left', 'label' => Mage::helper('venedor')->__('Left')),
            array('value' => 'right', 'label' => Mage::helper('venedor')->__('Right'))
        );
    }
}