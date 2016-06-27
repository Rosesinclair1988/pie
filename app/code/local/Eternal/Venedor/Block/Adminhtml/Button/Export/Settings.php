<?php

class Eternal_Venedor_Block_Adminhtml_Button_Export_Settings extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $elementOriginalData = $element->getOriginalData();
        if (isset($elementOriginalData['process']))
        {
            $name = $elementOriginalData['process'];
        }
        else
        {
            return '<div>Action was not specified</div>';
        }
        
        $buttonSuffix = '';
        if (isset($elementOriginalData['label']))
            $buttonSuffix = ' ' . $elementOriginalData['label'];
            
        $website = Mage::app()->getRequest()->getParam('website');
        $store   = Mage::app()->getRequest()->getParam('store');
        
        $url = 'venedor/adminhtml_export/' . $name;
        if ($website)
            $url .= '/website/' . $website;
        if ($store)
            $url .= '/store/' . $store;
        
        $url = $this->getUrl($url);
        
        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('export-settings')
            ->setLabel('Export' . $buttonSuffix)
            ->setOnClick("setLocation('$url')")
            ->toHtml();
            
        return $html;
    }
}
