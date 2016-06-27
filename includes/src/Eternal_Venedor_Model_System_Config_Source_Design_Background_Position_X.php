<?php

class Eternal_Venedor_Model_System_Config_Source_Design_Background_Position_X
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'left',    'label' => Mage::helper('venedor')->__('left')),
            array('value' => 'center',  'label' => Mage::helper('venedor')->__('center')),
            array('value' => 'right',   'label' => Mage::helper('venedor')->__('right'))
        );
    }
}
