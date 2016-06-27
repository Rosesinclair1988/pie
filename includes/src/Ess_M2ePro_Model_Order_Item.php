<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Order_Item extends Ess_M2ePro_Model_Component_Parent_Abstract
{
    // ->__('Product does not exist. Probably it was deleted.');
    // ->__('Product is disabled.');
    // ->__('Order Import does not support product type: %type%.');
    const LOG_PRODUCT_MISSING            = 'Product does not exist. Probably it was deleted.';
    const LOG_PRODUCT_DISABLED           = 'Product is disabled.';
    const LOG_PRODUCT_TYPE_NOT_SUPPORTED = 'Order Import does not support product type: %type%.';

    // ########################################

    /** @var $order Ess_M2ePro_Model_Order */
    private $order = NULL;

    /** @var $product Mage_Catalog_Model_Product */
    private $product = NULL;

    private $proxy = NULL;

    private $supportedProductTypes = array(
        Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
        Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
        Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
        Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
        Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE
    );

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Order_Item');
    }

    /**
     * @return Ess_M2ePro_Model_Ebay_Order_Item|Ess_M2ePro_Model_Amazon_Order_Item
     */
    public function getChildObject()
    {
        return parent::getChildObject();
    }

    // ########################################

    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return $this->getChildObject()->isLocked();
    }

    public function deleteInstance()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->order = NULL;

        $this->deleteChildInstance();
        $this->delete();

        return true;
    }

    // ########################################

    public function getOrderId()
    {
        return $this->getData('order_id');
    }

    public function getProductId()
    {
        return $this->getData('product_id');
    }

    // ########################################

    public function setOrder(Ess_M2ePro_Model_Order $order)
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder()
    {
        if (is_null($this->order)) {
            $this->order = Mage::helper('M2ePro/Component')
                ->getComponentObject($this->getComponentMode(), 'Order', $this->getOrderId());
        }

        return $this->order;
    }

    // ########################################

    public function setProduct(Mage_Catalog_Model_Product $product)
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct()
    {
        if (is_null($this->getProductId())) {
            return NULL;
        }

        if (is_null($this->product)) {
            $storeId = $this->getChildObject()->getAssociatedStoreId();

            if ($storeId == 0) {
                $storeId = Mage::helper('M2ePro/Magento')->getDefaultStoreId();
            }

            $this->product = Mage::getModel('catalog/product')
                ->setStoreId($storeId)
                ->load($this->getProductId());
        }

        return $this->product;
    }

    // ########################################

    public function getProxy()
    {
        if (is_null($this->proxy)) {
            $this->isComponentModeEbay()   && $this->proxy = Mage::getModel('M2ePro/Ebay_Order_Item_Proxy');
            $this->isComponentModeAmazon() && $this->proxy = Mage::getModel('M2ePro/Amazon_Order_Item_Proxy');

            $this->proxy->setItem($this->getChildObject());
        }

        return $this->proxy;
    }

    // ########################################

    public function associateWithProduct()
    {
        $productId = !is_null($this->getProductId())
            ? $this->getProductId()
            : $this->getChildObject()->getAssociatedProductId();

        if (is_null($productId)) {
            return false;
        }

        if (is_null($this->getProductId())) {
            $this->setData('product_id', $productId)->save();
        }

        if (is_null($this->getProduct()->getId())) {
            $log = $this->getOrder()->makeLog(Ess_M2ePro_Model_Order::LOG_IMPORT_ORDER_FAILED, array(
                'msg' => self::LOG_PRODUCT_MISSING
            ));
            $this->getOrder()->addErrorLog($log);
            $this->setData('product_id', NULL)->save();

            return false;
        }

        if ($this->getProduct()->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
            $log = $this->getOrder()->makeLog(Ess_M2ePro_Model_Order::LOG_IMPORT_ORDER_FAILED, array(
                'msg' => self::LOG_PRODUCT_DISABLED
            ));
            $this->getOrder()->addErrorLog($log);

            return false;
        }

        if (!in_array($this->getProduct()->getTypeId(), $this->supportedProductTypes)) {
            $nestedLog = $this->getOrder()->makeLog(self::LOG_PRODUCT_TYPE_NOT_SUPPORTED, array(
                'type' => $this->getProduct()->getTypeId()
            ));
            $log = $this->getOrder()->makeLog(Ess_M2ePro_Model_Order::LOG_IMPORT_ORDER_FAILED, array(
                'msg' => $nestedLog
            ));
            $this->getOrder()->addErrorLog($log);

            return false;
        }

        return true;
    }

    // ########################################
}
