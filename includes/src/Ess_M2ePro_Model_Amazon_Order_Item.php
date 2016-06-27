<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Order_Item extends Ess_M2ePro_Model_Component_Child_Amazon_Abstract
{
    // Parser hack -> Mage::helper('M2ePro')->__('Product import is disabled in Amazon Account settings.');
    // Parser hack -> Mage::helper('M2ePro')->__('Product for Amazon Item "%id%" was created in Magento catalog.');
    const LOG_IMPORT_PRODUCT_DISABLED = 'Product import is disabled in Amazon Account settings.';
    const LOG_IMPORT_PRODUCT_SUCCEEDED = 'Product for Amazon Item "%title%" was created in Magento catalog.';

    // ########################################

    /** @var $amazonItem Ess_M2ePro_Model_Amazon_Item */
    private $amazonItem = NULL;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Amazon_Order_Item');
    }

    /**
     * @return Ess_M2ePro_Model_Order_Item
     */
    public function getParentObject()
    {
        return parent::getParentObject();
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Amazon_Order
     */
    public function getAmazonOrder()
    {
        return $this->getParentObject()->getOrder()->getChildObject();
    }

    // ########################################

    public function getAmazonItem()
    {
        if (is_null($this->amazonItem)) {
            $this->amazonItem = Mage::getModel('M2ePro/Amazon_Item')->getCollection()
                ->addFieldToFilter('account_id', $this->getParentObject()->getOrder()->getAccountId())
                ->addFieldToFilter('marketplace_id', $this->getParentObject()->getOrder()->getMarketplaceId())
                ->addFieldToFilter('sku', $this->getSku())
                ->setOrder('create_date', Varien_Data_Collection::SORT_ORDER_DESC)
                ->getFirstItem();
        }

        return !is_null($this->amazonItem->getId()) ? $this->amazonItem : NULL;
    }

    // ########################################

    public function getItemId()
    {
        return $this->getData('item_id');
    }

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getSku()
    {
        return $this->getData('sku');
    }

    public function getGeneralId()
    {
        return $this->getData('general_id');
    }

    public function getIsIsbnGeneralId()
    {
        return (int)$this->getData('is_isbn_general_id');
    }

    public function getPrice()
    {
        return (float)$this->getData('price');
    }

    public function getTaxAmount()
    {
        return (float)$this->getData('tax_amount');
    }

    public function getDiscountAmount()
    {
        return (float)$this->getData('discount_amount');
    }

    public function getCurrency()
    {
        return $this->getData('currency');
    }

    public function getQtyPurchased()
    {
        return (int)$this->getData('qty_purchased');
    }

    // ########################################

    public function getAssociatedStoreId()
    {
        /** @var $amazonAccount Ess_M2ePro_Model_Amazon_Account */
        $amazonAccount = $this->getAmazonOrder()->getAmazonAccount();

        // Item was listed by M2E
        // ----------------
        if (!is_null($this->getAmazonItem())) {
            return $amazonAccount->isMagentoOrdersListingsStoreCustom()
                ? $amazonAccount->getMagentoOrdersListingsStoreId()
                : $this->getAmazonItem()->getStoreId();
        }
        // ----------------

        return $amazonAccount->getMagentoOrdersListingsOtherStoreId();
    }

    // ########################################

    public function getAssociatedProductId()
    {
        $order = $this->getParentObject()->getOrder();

        // Item was listed by M2E
        // ----------------
        if (!is_null($this->getAmazonItem())) {
            return $this->getAmazonItem()->getProductId();
        }
        // ----------------

        // 3rd party Item
        // ----------------
        $sku = $this->getSku();
        if ($sku != '' && strlen($sku) <= 64) {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

            if ($product && $product->getId()) {
                return $product->getId();
            }
        }
        // ----------------

        if (!$this->getAmazonOrder()->getAmazonAccount()->isMagentoOrdersListingsOtherProductImportEnabled()) {
            $log = $order->makeLog(Ess_M2ePro_Model_Order::LOG_IMPORT_ORDER_FAILED, array(
                'msg' => self::LOG_IMPORT_PRODUCT_DISABLED
            ));
            $order->addErrorLog($log);

            return NULL;
        }

        $product = $this->createProduct();

        return $product->getId();
    }

    private function createProduct()
    {
        $storeId = $this->getAmazonOrder()->getAmazonAccount()->getMagentoOrdersListingsOtherStoreId();
        if ($storeId == 0) {
            $storeId = Mage::helper('M2ePro/Magento')->getDefaultStoreId();
        }

        $sku = $this->getSku();
        if (strlen($sku) > 64) {
            $sku = substr($sku, strlen($sku) - 64, 64);
        }

        $productData = array(
            'title'             => $this->getTitle(),
            'sku'               => $sku,
            'description'       => '',
            'short_description' => '',
            'qty'               => $this->getQtyForNewProduct(),
            'price'             => $this->getPrice(),
            'store_id'          => $storeId
        );

        // Create product in magento
        // ----------------
        /** @var $productBuilder Ess_M2ePro_Model_Magento_Product_Builder */
        $productBuilder = Mage::getModel('M2ePro/Magento_Product_Builder')->setData($productData);
        $productBuilder->buildProduct();
        // ----------------

        $order = $this->getParentObject()->getOrder();
        $log = $order->makeLog(self::LOG_IMPORT_PRODUCT_SUCCEEDED, array('!title' => $this->getTitle()));
        $order->addSuccessLog($log);

        return $productBuilder->getProduct();
    }

    private function getQtyForNewProduct()
    {
        $otherListing = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Other')
            ->addFieldToFilter('account_id', $this->getParentObject()->getOrder()->getAccountId())
            ->addFieldToFilter('marketplace_id', $this->getParentObject()->getOrder()->getMarketplaceId())
            ->addFieldToFilter('sku', $this->getSku())
            ->getFirstItem();

        if ((int)$otherListing->getOnlineQty() > $this->getQtyPurchased()) {
            return $otherListing->getOnlineQty();
        }

        return $this->getQtyPurchased();
    }

    // ########################################
}
