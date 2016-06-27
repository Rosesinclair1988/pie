<?php

class Eternal_Venedor_Model_System_Config_Source_Design_Font_Size
{
    public function toOptionArray()
    {
        return array(
            array('value' => '11px',    'label' => Mage::helper('venedor')->__('11 px')),
            array('value' => '12px',    'label' => Mage::helper('venedor')->__('12 px')),
            array('value' => '13px',    'label' => Mage::helper('venedor')->__('13 px')),
            array('value' => '14px',    'label' => Mage::helper('venedor')->__('14 px')),
            array('value' => '15px',    'label' => Mage::helper('venedor')->__('15 px')),
            array('value' => '16px',    'label' => Mage::helper('venedor')->__('16 px')),
            array('value' => '17px',    'label' => Mage::helper('venedor')->__('17 px')),
            array('value' => '18px',    'label' => Mage::helper('venedor')->__('18 px'))
        );
    }
}
