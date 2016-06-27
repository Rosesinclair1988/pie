<?php

class Eternal_HomeSlider_Model_System_Config_Source_Slider_Type
{
    public function toOptionArray()
    {
        return array(
			array('value' => 'block',	'label' => Mage::helper('eternal_homeslider')->__('Block Slider')),
			array('value' => 'product',	'label' => Mage::helper('eternal_homeslider')->__('Product Slider'))
        );
    }
}
