<?php
class Eternal_Venedor_Adminhtml_ExportController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
        $website = Mage::app()->getRequest()->getParam('website');
        $store   = Mage::app()->getRequest()->getParam('store');
        
        $url = 'adminhtml/system_config/edit/section/venedor_data';
        if ($website)
            $url .= '/website/' . $website;
        if ($store)
            $url .= '/store/' . $store;
        
        $url = $this->getUrl($url);
        
		$this->getResponse()->setRedirect($url);
	}
    
    public function blocksAction()
    {
        $website = Mage::app()->getRequest()->getParam('website');
        $store   = Mage::app()->getRequest()->getParam('store');
        
        $url = 'adminhtml/system_config/edit/section/venedor_data';
        if ($website)
            $url .= '/website/' . $website;
        if ($store)
            $url .= '/store/' . $store;
        
        $url = $this->getUrl($url);
        
        Mage::getSingleton('venedor/export_blocks')->exportBlockItems('cms/block', 'cms_block', 'blocks');
        $this->getResponse()->setRedirect($url); 
    }
    
    public function pagesAction() 
    {
        $website = Mage::app()->getRequest()->getParam('website');
        $store   = Mage::app()->getRequest()->getParam('store');
        
        $url = 'adminhtml/system_config/edit/section/venedor_data';
        if ($website)
            $url .= '/website/' . $website;
        if ($store)
            $url .= '/store/' . $store;
        
        $url = $this->getUrl($url);
        
        Mage::getSingleton('venedor/export_pages')->exportPageItems('cms/page', 'cms_page', 'pages');
        $this->getResponse()->setRedirect($url);
    }
    
    public function settingsAction() 
    {
        $website = Mage::app()->getRequest()->getParam('website');
        $store   = Mage::app()->getRequest()->getParam('store');
        
        $url = 'adminhtml/system_config/edit/section/venedor_data';
        if ($website)
            $url .= '/website/' . $website;
        if ($store)
            $url .= '/store/' . $store;
        
        $url = $this->getUrl($url);

        Mage::getSingleton('venedor/export_settings')->exportSettings($website, $store);
        $this->getResponse()->setRedirect($url);
    }
}