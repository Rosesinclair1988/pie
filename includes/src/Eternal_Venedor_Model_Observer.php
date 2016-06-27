<?php
/**
 * Call actions after configuration is saved
 */
class Eternal_Venedor_Model_Observer
{
    // After any system config is saved
    public function venedor_controllerActionPostdispatchAdminhtmlSystemConfigSave()
    {
        $section = Mage::app()->getRequest()->getParam('section');
        
        $websiteCode = Mage::app()->getRequest()->getParam('website');
        $storeCode = Mage::app()->getRequest()->getParam('store');
        
        Mage::getSingleton('venedor/config_generator')->generateCss($websiteCode, $storeCode);
    }
    
    // After store view is saved
    public function venedor_storeEdit(Varien_Event_Observer $observer)
    {
        $store = $observer->getEvent()->getStore();
        $storeCode = $store->getCode();
        $websiteCode = $store->getWebsite()->getCode();
        
        Mage::getSingleton('venedor/config_generator')->generateCss($websiteCode, $storeCode);
    }
}
