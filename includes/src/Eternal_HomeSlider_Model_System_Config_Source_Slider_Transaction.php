<?php

class Eternal_HomeSlider_Model_System_Config_Source_Slider_Transaction
{
    public function toOptionArray()
    {
        return array(
			array('value' => 'slide',	'label' => Mage::helper('eternal_homeslider')->__('Slide')),
			array('value' => 'fade',	'label' => Mage::helper('eternal_homeslider')->__('Fade'))
        );
    }
}
