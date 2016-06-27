<?php

class Eternal_Venedor_Model_System_Config_Source_Design_Background_Attachment
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'fixed',       'label' => Mage::helper('venedor')->__('fixed')),
            array('value' => 'scroll',      'label' => Mage::helper('venedor')->__('scroll'))
        );
    }
}
