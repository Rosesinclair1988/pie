<?php

class Eternal_Venedor_Helper_Config extends Mage_Core_Helper_Abstract
{
    // Path and directory of the automatically generated CSS
    protected $_generatedCssFolder;
    protected $_generatedCssPath;
    protected $_generatedCssDir;
    
    public function __construct()
    {
        //Create paths
        $this->_generatedCssFolder = '_config/';
        $this->_generatedCssPath = 'frontend/venedor/default/' . $this->_generatedCssFolder;
        $this->_generatedCssDir = Mage::getBaseDir('skin') . '/' . $this->_generatedCssPath;
    }
    
    // Get automatically generated CSS directory
    public function getGeneratedCssDir()
    {
        return $this->_generatedCssDir;
    }

    // Get configuration css file path
    public function getCssFile()
    {
        return $this->_generatedCssFolder . 'store_' . Mage::app()->getStore()->getCode() . '.css';
    }
    
    // Get responsive css file path
    public  function getCssResponsiveFile() 
    {
        if (Mage::getStoreConfig('venedor_setting/general/active_responsive'))
            return 'css/styles-venedor-responsive.css';
        return 'css/empty.css';
    }  
    
    // Get skin responsive css file path
    public  function getCssSkinResponsiveFile() 
    {
        if (Mage::getStoreConfig('venedor_setting/general/active_responsive'))
            return 'css/skin-responsive.css';
        return 'css/empty.css';
    } 
}
