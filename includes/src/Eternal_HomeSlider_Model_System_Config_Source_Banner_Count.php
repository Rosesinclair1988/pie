<?php

class Eternal_HomeSlider_Model_System_Config_Source_Banner_Count
{
    public function toOptionArray()
    {
        return array(
            array('value' => '1',    'label' => Mage::helper('eternal_homeslider')->__('1')),
            array('value' => '2',    'label' => Mage::helper('eternal_homeslider')->__('2')),
			array('value' => '3',    'label' => Mage::helper('eternal_homeslider')->__('3')),
            array('value' => '4',    'label' => Mage::helper('eternal_homeslider')->__('4')),
            array('value' => '6',    'label' => Mage::helper('eternal_homeslider')->__('6'))
        );
    }
}
