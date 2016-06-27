<?php
class Eternal_Venedor_Block_Product_List_Featured extends Mage_Catalog_Block_Product_List
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
                $this->getIsLatest(),
                $this->getIsNew(),
                $this->getIsSpecial(),
                $this->getIsRandom(),
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
            $categoryID = $this->getCategoryId();
            
            if (!$categoryID)
            {
                $_category = Mage::registry('current_category');
                if ($_category)
                    $categoryID = $_category->getId();
            }
            
            if($categoryID)
            {
                /*$collection = Mage::getModel('catalog/product')
                             ->getCollection()
                             ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left')
                             ->addAttributeToSelect('*')
                             ->addAttributeToFilter('category_id', array('in' => array($categoryID)));*/
                $catagory_model = Mage::getModel('catalog/category')->load($categoryID); //where $category_id is the id of the category
                $collection = Mage::getResourceModel('catalog/product_collection');
                $collection->addCategoryFilter($catagory_model); //category filter
            }
            else
            {
                $collection = Mage::getResourceModel('catalog/product_collection');
            }
            
            Mage::getModel('catalog/layer')->prepareProductCollection($collection);
            
            if ($this->getIsLatest())
            {
                $collection->addAttributeToSort('created_at', 'desc');
            }

            if (!$this->getIsRandom())
            {
                $collection->addAttributeToSort('position');
            }
            
            $todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
            
            if ($this->getIsNew())
            {
                $collection->addAttributeToFilter('news_from_date', array('date' => true, 'to' => $todayDate))
                            ->addAttributeToFilter('news_to_date', array('or'=> array(
                                0 => array('date' => true, 'from' => $todayDate),
                                1 => array('is' => new Zend_Db_Expr('null')))
                            ), 'left')
                            ->addAttributeToSort('news_from_date', 'desc');
            }
            
            if ($this->getIsSpecial())
            {
                $collection->addAttributeToFilter('special_from_date', array('date' => true, 'to' => $todayDate))
                            ->addAttributeToFilter('special_to_date', array('or'=> array(
                                0 => array('date' => true, 'from' => $todayDate),
                                1 => array('is' => new Zend_Db_Expr('null')))
                            ), 'left');
            }
            
            if ($this->getIsRandom())
            {
                $collection->getSelect()->order('rand()');
            }
            $collection->addStoreFilter();
            $productCount = $this->getProductCount() ? $this->getProductCount() : 6;
            $collection->setPage(1, $productCount)
                ->load();
            
            $this->_productCollection = $collection;
        }
        return $this->_productCollection;
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
