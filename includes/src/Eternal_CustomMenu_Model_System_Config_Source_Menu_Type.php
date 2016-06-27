<?php

class Eternal_CustomMenu_Model_System_Config_Source_Menu_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'narrow', 'label' => Mage::helper('eternal_custommenu')->__('Narrow')),
            array('value' => 'wide', 'label' => Mage::helper('eternal_custommenu')->__('Wide'))
        );
    }
}