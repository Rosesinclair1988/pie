<?php

class Miura_ProductAttribute_Block_ProductAttribute extends Mage_Catalog_Block_Product_Abstract {

    public function _prepareLayout() {
        return parent::_prepareLayout();
    }

    public function __construct() {
        parent::__construct();
        $this->setTemplate('Miura/ProductAttribute/view10.phtml');

        $this->addData(array(
            'cache_lifetime' => 86400,
            'cache_tags' => array('MIURA_PRODUCTATTRIBUTE'),
            'cache_key' => $this->getCacheKey(),
        ));
    }

    public function getCacheKey() {
        return 'MIURA_PRODUCTATTRIBUTE_' . Mage::app()->getStore()->getId()
                . '_' . (int) Mage::app()->getStore()->isCurrentlySecure()
                . '_' . Mage::getDesign()->getPackageName()
                . '_' . Mage::getDesign()->getTheme('template')
                . '_' . $this->getData('name');
    }

    public function getCollection() {
        if (($this->getData('attribute_name') && $this->getData('attribute_value'))
                || $this->getData('product_id')) {
            $collection = Mage::getResourceModel('catalog/product_collection');
            $collection->addAttributeToSelect('name')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('small_image')
                    ->addMinimalPrice()
                    ->addFinalPrice()
                    ->addTaxPercents()
                    ->addStoreFilter();

            if ($this->getData('attribute_name') && $this->getData('attribute_value'))
                $collection->addAttributeToFilter($this->getData('attribute_name'), $this->getData('attribute_value'));

            if ($this->getData('product_id'))
                $collection->addAttributeToFilter('entity_id', $this->getData('product_id'));

            if (!$this->getData('product_id') && $this->getData('column_count') > 1)
                $collection->getSelect()->order('rand()')->limit($this->getData('column_count'));

            return $collection;
        }
        return null;
    }

}
