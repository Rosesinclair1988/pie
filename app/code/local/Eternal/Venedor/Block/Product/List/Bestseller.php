<?php
class Eternal_Venedor_Block_Product_List_Bestseller extends Mage_Catalog_Block_Product_List
{
    protected $_collectionCount = NULL;
    protected $_productCollectionId = NULL;
    protected $_cacheKeyArray = NULL;
    
    /**
     * Initialize block's cache
     */
    protected function _construct()
    {
        parent::_construct();

        $this->addData(array(
            'cache_lifetime'    => 99999999,
            'cache_tags'        => array(Mage_Catalog_Model_Product::CACHE_TAG)
        ));
    }
    
    /**
     * Get Key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        if (NULL === $this->_cacheKeyArray)
        {
            $this->_cacheKeyArray = array(
                'VENEDOR_PRODUCT_FEATURED',
                Mage::app()->getStore()->getCurrentCurrency()->getCode(),
                //Mage::app()->getStore()->getCurrentCurrencyCode(),
                
                Mage::app()->getStore()->getId(),
                Mage::getDesign()->getPackageName(),
                Mage::getDesign()->getTheme('template'),
                Mage::getSingleton('customer/session')->getCustomerGroupId(),
                'template' => $this->getTemplate(),
                
                $this->getBlockName(),
                $this->getBlockDescription(),
                $this->getCategoryId(),
                $this->getIsWide(),
                $this->getIsGrid(),
                
                (int)Mage::app()->getStore()->isCurrentlySecure(),
                $this->getUniqueCollectionId(),
            );
        }
        return $this->_cacheKeyArray;
    }
    
    /**
     * Get collection id
     *
     * @return string
     */
    public function getUniqueCollectionId()
    {
        if (NULL === $this->_productCollectionId)
        {
            $this->_prepareCollectionAndCache();
        }
        return $this->_productCollectionId;
    }
    
    /**
     * Prepare collection id, count collection
     */
    protected function _prepareCollectionAndCache()
    {
        $ids = array();
        $i = 0;
        foreach ($this->_getProductCollection() as $product)
        {
            $ids[] = $product->getId();
            $i++;
        }
        
        $this->_productCollectionId = implode("+", $ids);
        $this->_collectionCount = $i;
    }
    
    /**
     * Retrieve loaded category collection.
     * Need Following Variables : category_id, product_count, is_random
     */
    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection))
        {
            $visibility = array(
                                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
            );
            
            $collection = Mage::getResourceModel('reports/product_collection')
                        ->addAttributeToSelect('*')
                        ->addStoreFilter()
                        ->addOrderedQty();
            
            Mage::getModel('catalog/layer')->prepareProductCollection($collection);
            
            $categoryID = 7;
            if($categoryID)
            {
                $category = new Mage_Catalog_Model_Category();
                $category->load($categoryID);
                $collection->addCategoryFilter($category);
            }
            
            $collection->addAttributeToFilter('visibility', $visibility)
                        ->setOrder('ordered_qty', 'desc');
            $productCount = $this->getProductCount() ? $this->getProductCount() : 6;
            $collection->setPage(1, $productCount)
                ->load();
            
            Mage::getSingleton('catalog/product_status')
                        ->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')
                        ->addVisibleInCatalogFilterToCollection($collection);
            
            $this->_productCollection = $collection;
        }
        return $this->_productCollection;
    }

    protected function _getProductCollectionByCategory($catId)
    {
        if (is_null($this->_productCollectionByArrCategory))
        {
            if(!$catId)
                return false;

            $visibility = array(
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
            );

            $collection = Mage::getResourceModel('reports/product_collection')
                ->addAttributeToSelect('*')
                ->addStoreFilter()
                ->addOrderedQty();

            Mage::getModel('catalog/layer')->prepareProductCollection($collection);

            $category = new Mage_Catalog_Model_Category();
            $category->load($catId);
            $collection->addCategoryFilter($category);

            $collection->addAttributeToFilter('visibility', $visibility)
                ->setOrder('ordered_qty', 'desc');
            $productCount = $this->getProductCount() ? $this->getProductCount() : 6;
            $collection->setPage(1, $productCount)
                ->load();

            Mage::getSingleton('catalog/product_status')
                ->addVisibleFilterToCollection($collection);
            Mage::getSingleton('catalog/product_visibility')
                ->addVisibleInCatalogFilterToCollection($collection);

            $this->_productCollectionArrByCategory = $collection;
        }
        return $this->_productCollectionArrByCategory;
    }
    /**
     * Get number of products in the collection
     *
     * @return int
     */
    public function getCollectionCount()
    {
        if (NULL === $this->_collectionCount)
        {
            $this->_prepareCollectionAndCache();
        }
        return $this->_collectionCount;
    }
    
    /**
     * Create unique block id for frontend
     *
     * @return string
     */
    public function getFrontHash()
    {
        return md5( implode("+", $this->getCacheKeyInfo()) );
    }
    
    /**
     * Get add to cart Url
     *
     * @return string
     */
    public function getAddToCartUrl($product, $additional = array())
    {
        $AddToCartUrl = parent::getAddToCartUrl($product, $additional);
        switch ($product['type_id']){
            case 'simple':{
                return $AddToCartUrl;
            }
            break;
            default:
                return $AddToCartUrl.'?options=cart';
                break;
        }
        return $AddToCartUrl;
    }
}
