<?php
/**
 * @copyright   Copyright (c) 2010 Amasty (http://www.amasty.com)
 */  
class Amasty_Shopby_Model_Catalog_Layer_Filter_Price extends Amasty_Shopby_Model_Catalog_Layer_Filter_Price_Adapter
{
	public function _construct()
	{	
		parent::_construct();
	}
	
    public function _srt($a, $b)
    {
        $res = ($a['pos'] < $b['pos']) ? -1 : 1;
        return $res;
    } 
    
	protected function _getCustomRanges()
	{
        $ranges = array();
        $collection = Mage::getModel('amshopby/range')->getCollection()
            ->setOrder('price_frm','asc')
            ->load();
            
        $rate = Mage::app()->getStore()->getCurrentCurrencyRate(); 
        foreach ($collection as $range){
            $from = $range->getPriceFrm()*$rate;
            $to = $range->getPriceTo()*$rate;
            
            $ranges[$range->getId()] = array($from, $to);
        }
        
        if (!$ranges){
            echo "Please set up Custom Ranges in the Admin > Catalog > Improved Navigation > Ranges";
            exit;
        }
        
        return $ranges;
	}  
	
	public function calculateRanges()
    {
        return (Mage::getStoreConfig('amshopby/general/price_type') == 0 || Mage::getStoreConfig('amshopby/general/price_type') == 1);     
    } 
    
    public function hideAfterSelection()
    {
        if (Mage::getStoreConfig('amshopby/general/price_from_to')){
            return false;
        }
        if (Mage::getStoreConfig('amshopby/general/price_type') == 3){
            return false;
        }
        return true;
    }
}
