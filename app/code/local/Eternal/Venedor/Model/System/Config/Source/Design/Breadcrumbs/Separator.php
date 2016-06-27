<?php

class Eternal_Venedor_Model_System_Config_Source_Design_Breadcrumbs_Separator
{
    public function toOptionArray()
    {
		return array(
			array('value' => '&gt;', 'label' => Mage::helper('venedor')->__('>')),
            array('value' => '|', 'label' => Mage::helper('venedor')->__('|')),
            array('value' => '/', 'label' => Mage::helper('venedor')->__('/'))
        );
    }
}