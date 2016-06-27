<?php

class Eternal_Venedor_Model_System_Config_Source_Setting_Category_Banner_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'special_products', 'label' => Mage::helper('venedor')->__('Show Special Products')),
            array('value' => 'simple_banner', 'label' => Mage::helper('venedor')->__('Show with Category Thumbnail Image')),
            array('value' => 'banner', 'label' => Mage::helper('venedor')->__('Show with Category Image')),
        );
    }
}