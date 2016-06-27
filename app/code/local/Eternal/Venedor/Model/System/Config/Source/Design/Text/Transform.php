<?php

class Eternal_Venedor_Model_System_Config_Source_Design_Text_Transform
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'none', 'label' => Mage::helper('venedor')->__('None')),
            array('value' => 'uppercase', 'label' => Mage::helper('venedor')->__('Uppercase')),
            array('value' => 'lowercase', 'label' => Mage::helper('venedor')->__('Lowercase')),
            array('value' => 'capitalize', 'label' => Mage::helper('venedor')->__('Capitalize'))
        );
    }
}