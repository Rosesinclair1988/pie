<?php

class Eternal_Venedor_Model_Export_Settings extends Mage_Core_Model_Abstract
{
    private $_exportPath;
    
    public function __construct()
    {
        parent::__construct();
        $this->_exportPath = Mage::getBaseDir() . '/app/code/local/Eternal/Venedor/data/export/';
    }
    
    public function exportSettings($website, $store)
    {
        try
        {
            // Create xml file
            $io = new Varien_Io_File();
            if ($website && $store)
                $xmlPath = $this->_exportPath . $website . '_' . $store . '.xml';
            elseif ($website)
                $xmlPath = $this->_exportPath . $website . '_default' . '.xml';
            elseif ($store)
                $xmlPath = $this->_exportPath . 'default_' . $store . '.xml';
            else 
                $xmlPath = $this->_exportPath . 'default.xml';
            
            if ($store) {
                $store_id = Mage::getModel('core/store')->load($store)->getId();
            } elseif ($website) {
                $website_id = Mage::getModel('core/website')->load($website)->getId();
                $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
            } else {
                $store_id = NULL;
            }
            
            $io->setAllowCreateFolders(true);
            $io->open(array('path' => $this->_exportPath));
            
            if ($io->fileExists($xmlPath) && !$io->isWriteable($xmlPath))
            {
                Mage::throwException(Mage::helper('venedor')->__('File "%s" cannot be saved. Please, make sure the directory is writeable by web server.', $xmlPath));
            }

            $io->streamOpen($xmlPath, 'w+');
            
            $venedor = Mage::helper('venedor');
            $b = $venedor->getConfigGroup($store_id);
            $c = $venedor->getConfigGroupDesign($store_id);
            
            $config = array('venedor_setting' => $b, 'venedor_design' => $c );
            
            $xml = $this->array_to_xml($config);
            $xml = $this->formatXmlString($xml);
            
            $io->streamWrite($xml);
            
            $io->streamClose();
            
            //Final info
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('venedor')->__('Successfully exported.')
            );
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::logException($e);
        }
    } 
    
    private function array_to_xml($array, $xml = false) {
        if($xml === false){
            $xml = new SimpleXMLElement('<default/>');
        }
        foreach($array as $key => $value){
            if(is_array($value)){
                $this->array_to_xml($value, $xml->addChild($key));
            }else{
                $xml->addChild($key, $value);
            }
        }
        return $xml->asXML();
    } 
    
    private function formatXmlString($xml){
        $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
        $token      = strtok($xml, "\n");
        $result     = '';
        $pad        = 0; 
        $matches    = array();
        while ($token !== false) : 
            if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) : 
                $indent = 0;
            elseif (preg_match('/^<\/\w/', $token, $matches)) :
                $pad--;
                $indent = 0;
            elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
                $indent = 1;
            else :
                $indent = 0; 
            endif;
            $line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
            $result .= $line . "\n";
            $token   = strtok("\n");
            $pad    += $indent;
        endwhile; 
        return $result;
    }  
}