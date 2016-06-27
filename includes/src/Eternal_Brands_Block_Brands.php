<?php

class Eternal_Brands_Block_Brands extends Mage_Core_Block_Template
{    
    public function getAttributeId()
    {
        return Mage::helper('eternal_brands')->getConfig('general/attr_id');
    }
    
    public function getBrandTitle()
    {
        return Mage::helper('eternal_brands')->getConfig('general/brand_title');
    }
    
    public function getBrands()
    {
        $attributeModel = Mage::getSingleton('eav/config')
            ->getAttribute('catalog_product', $this->getAttributeId());
            
        $options = array();
        foreach ($attributeModel->getSource()->getAllOptions(false, true) as $option)
        {
            $options[] = $option['label'];
        }
        
        return $options;
    }
    
    public function getBrandImageBaseUrl()
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'wysiwyg/eternal/brands/';
    }
    
    public function getBrandImageBaseDir() 
    {
        return Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . '/wysiwyg/eternal/brands/';
    }
    
    public function getBrandImageUrl($brand)
    {
        $brandImageExt = trim(Mage::helper('eternal_brands')->getConfig('general/image_extension'));
        $brandImage = $this->getBrandImageBaseDir() . str_replace(" ", "_", strtolower($brand)) . '.' . $brandImageExt;
        if (file_exists($brandImage))
            return $this->getBrandImageBaseUrl() . str_replace(" ", "_", strtolower($brand)) . '.' . $brandImageExt;
        return $this->getBrandImageBaseUrl() . 'default.png';
    }
    
    public function getBrandUrl($brand)
    {
        $brandPageUrl = '';
        $helper = Mage::helper('eternal_brands');
        
        // if brand logo is a link to Magento's Quick Search results
        $brandLinkToSearch = $helper->getConfig('general/link_search_enabled');
        
        if ($brandLinkToSearch)
        {
            $brandPageUrl = Mage::getUrl() . 'catalogsearch/result/?q=' . str_replace(" ", "+", $brand);
        }
        
        return $brandPageUrl;
    }
}
