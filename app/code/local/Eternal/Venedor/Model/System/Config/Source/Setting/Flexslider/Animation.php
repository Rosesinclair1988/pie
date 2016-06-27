<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Flexslider_Animation
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'fade', 'label' => Mage::helper('venedor')->__('Fade')),
            array('value' => 'slide', 'label' => Mage::helper('venedor')->__('Slide'))
        );
    }
}