<?php
class Eternal_Venedor_Model_System_Config_Source_Design_Font_Family_Group
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'custom',
                  'label' => Mage::helper('venedor')->__('Custom...')),
            array('value' => 'google',
                  'label' => Mage::helper('venedor')->__('Google Font')),
            
            array('value' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
                  'label' => Mage::helper('venedor')->__('"Helvetica Neue", Helvetica, Arial, sans-serif')),
            array('value' => 'Verdana, Geneva, Arial, Helvetica, sans-serif',
                  'label' => Mage::helper('venedor')->__('Verdana, Geneva, Arial, Helvetica, sans-serif')),
            array('value' => 'Georgia, serif',
                  'label' => Mage::helper('venedor')->__('Georgia, serif')),
            array('value' => '"Lucida Sans Unicode", ""Lucida Sans", "Lucida Grande", "Lucida Sans Unicode", Verdana, Geneva, sans-serif',
                  'label' => Mage::helper('venedor')->__('"Lucida Sans", "Lucida Grande", "Lucida Sans Unicode", Verdana, Geneva, sans-serif')),
            array('value' => 'Baskerville, Georgia, Palatino, "Palatino Linotype", "Book Antiqua", "URW Palladio L", serif',
                  'label' => Mage::helper('venedor')->__('Baskerville, Georgia, Palatino, "Palatino Linotype", "Book Antiqua", "URW Palladio L", serif')),
            array('value' => 'Tahoma, Geneva, sans-serif',
                  'label' => Mage::helper('venedor')->__('Tahoma, Geneva, sans-serif')),
            array('value' => '"Trebuchet MS", Helvetica, sans-serif',
                  'label' => Mage::helper('venedor')->__('"Trebuchet MS", Helvetica, sans-serif'))
        );
    }
}