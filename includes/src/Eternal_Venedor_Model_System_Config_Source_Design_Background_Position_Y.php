<?php

class Eternal_Venedor_Model_System_Config_Source_Design_Background_Position_Y
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'top',     'label' => Mage::helper('venedor')->__('top')),
            array('value' => 'center',  'label' => Mage::helper('venedor')->__('center')),
            array('value' => 'bottom',  'label' => Mage::helper('venedor')->__('bottom'))
        );
    }
}
