<?php

class Eternal_Venedor_Model_Import_Settings extends Mage_Core_Model_Abstract
{
    private $_importPath;
    
    public function __construct()
    {
        parent::__construct();
        $this->_importPath = Mage::getBaseDir() . '/app/code/local/Eternal/Venedor/data/import/';
    }
    
    public function exportSettings($website, $store)
    {
        try
        {
            if ($store) {
                $store_id = Mage::getModel('core/store')->load($store)->getId();
                $scope = 'stores';
                $scope_id = $store_id;
            } elseif ($website) {
                $website_id = Mage::getModel('core/website')->load($website)->getId();
                $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
                $scope = 'websites';
                $scope_id = $website_id;
            } else {
                $store_id = NULL;
                $scope = 'default';
                $scope_id = 0;
            }
            
            $venedor = Mage::helper('venedor');
            $theme = $venedor->getConfigData('theme_import/import_theme', $store_id);
            
            $xmlPath = $this->_importPath . $theme . '.xml';
            if (!is_readable($xmlPath))
            {
                throw new Exception(
                    Mage::helper('venedor')->__("Can't read data file: %s", $xmlPath)
                );
            }
            
            $xmlObj = new Varien_Simplexml_Config($xmlPath);
                        
            $xml   = simplexml_load_string($xmlObj->getXmlString());
            $array = $this->xml_to_array($xml);
            $array = array($xml->getName() => $array);
            
            $config = $array['default'];
            foreach ($config as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    foreach ($value1 as $key2 => $value2) {
                        Mage::getConfig()->saveConfig($key . '/' . $key1 . '/' . $key2,$value2, $scope, $scope_id);
                    }
                }
            }
            
            Mage::getSingleton('venedor/config_generator')->generateCss($website, $store);
            
            //Final info
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('venedor')->__('Successfully imported.')
            );
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::logException($e);
        }
    } 
    
    private function xml_to_array(SimpleXMLElement $parent)
    {
        $array = array();

        foreach ($parent as $name => $element) {
            ($node = & $array[$name])
                && (1 === count($node) ? $node = array($node) : 1)
                && $node = & $node[];

            $node = count($element) ? $this->xml_to_array($element) : trim($element);
        }

        return $array;
    }
}