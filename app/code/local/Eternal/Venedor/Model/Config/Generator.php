<?php 
class Eternal_Venedor_Model_Config_Generator extends Mage_Core_Model_Abstract
{ 
    public function __construct()
    {
        parent::__construct(); 
    } 
    
    public function generateCss($websiteCode, $storeCode)
    {
        if ($websiteCode)
        { 
            if ($storeCode) 
            {
                $this->_generateStoreCss($storeCode); 
            } 
            else 
            {
                $this->_generateWebsiteCss($websiteCode); 
            }
        }
        else
        {
            $websites = Mage::app()->getWebsites(false, true);
            foreach ($websites as $website => $value) 
            {
                $this->_generateWebsiteCss($website); 
            }
        } 
    }
    
    protected function _generateWebsiteCss($websiteCode) 
    {
        $store = Mage::app()->getWebsite($websiteCode);
        foreach ($store->getStoreCodes() as $storeCode)
        { 
            if (!$this->_generateStoreCss($storeCode))
                continue;
        }
    } 
    
    protected function _generateStoreCss($storeCode)
    {
        if (!Mage::app()->getStore($storeCode)->getIsActive()) 
            return false;
            
        $fileName = 'store_' . $storeCode . '.css';
        $file = Mage::helper('venedor/config')->getGeneratedCssDir() . $fileName;
        $templateFile = 'eternal/venedor/config.phtml';
        Mage::register('venedor_css_generate_store', $storeCode);
        try
        { 
            $tempalte = Mage::app()->getLayout()->createBlock("core/template")->setData('area', 'frontend')->setTemplate($templateFile)->toHtml();
            if (empty($tempalte)) 
            {
                throw new Exception( Mage::helper('venedor')->__("Template file is empty or doesn't exist: %s", $templateFile) ); 
                return false;
            }
            $io = new Varien_Io_File(); 
            $io->setAllowCreateFolders(true); 
            $io->open(array( 'path' => Mage::helper('venedor/config')->getGeneratedCssDir() )); 
            $io->streamOpen($file, 'w+'); 
            $io->streamLock(true); 
            $io->streamWrite($tempalte); 
            $io->streamUnlock(); 
            $io->streamClose(); 
        }
        catch (Exception $exception)
        { 
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('venedor')->__('Failed generating CSS file: %s in %s', $fileName, Mage::helper('venedor/config')->getGeneratedCssDir()). '<br/>Message: ' . $exception->getMessage()); 
            Mage::logException($exception);
            return false;
        }
        Mage::unregister('venedor_css_generate_store');
        return true; 
    } 
}