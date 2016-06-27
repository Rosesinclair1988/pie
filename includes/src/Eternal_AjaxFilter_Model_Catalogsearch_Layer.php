<?php

class Eternal_AjaxFilter_Model_Catalogsearch_Layer extends Mage_CatalogSearch_Model_Layer 
{
    /**
     * Prepare product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection $collection
     * @return Mage_Catalog_Model_Layer
     */
    public function prepareProductCollection($collection)
    {
        $collection
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addSearchFilter(Mage::helper('catalogsearch')->getQuery()->getQueryText())
            ->setStore(Mage::app()->getStore())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addStoreFilter()
            ->addUrlRewrite();

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);
        
        $this->currentRate = $collection->getCurrencyRate();
        $max=$this->getMaxPriceFilter();
        $min=$this->getMinPriceFilter();
        
        //print_r($collection->getData());
        
        if($min && $max){
            //$collection= $collection->addAttributeToFilter('price',array('from'=>$min, 'to'=>$max)); 
            $collection->getSelect()->where(' final_price >= "'.$min.'" AND final_price <= "'.$max.'" ');
            
            //echo $collection->getSelect();exit;
        }
        
        return $this;
    }
    
    /*
    * convert Price as per currency
    *
    * @return currency
    */
    public function getMaxPriceFilter(){
        if (isset($_GET['max']))
            return round($_GET['max']/$this->currentRate);
    }
    
    
    /*
    * Convert Min Price to current currency
    *
    * @return currency
    */
    public function getMinPriceFilter(){
        if (isset($_GET['min']))
            return round($_GET['min']/$this->currentRate);
    }
    
}