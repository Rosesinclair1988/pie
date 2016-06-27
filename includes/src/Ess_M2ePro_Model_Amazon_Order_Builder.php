<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Order_Builder extends Mage_Core_Model_Abstract
{
    const STATUS_NOT_MODIFIED = 0;
    const STATUS_NEW          = 1;
    const STATUS_UPDATED      = 2;

    const AMAZON_STATUS_PENDING             = 'Pending';
    const AMAZON_STATUS_UNSHIPPED           = 'Unshipped';
    const AMAZON_STATUS_SHIPPED_PARTIALLY   = 'PartiallyShipped';
    const AMAZON_STATUS_SHIPPED             = 'Shipped';
    const AMAZON_STATUS_UNFULFILLABLE       = 'Unfulfillable';
    const AMAZON_STATUS_CANCELED            = 'Canceled';
    const AMAZON_STATUS_INVOICE_UNCONFIRMED = 'InvoiceUnconfirmed';

    // Parser hack -> Mage::helper('M2ePro')->__('Duplicated Amazon orders with ID "%id%".');
    const LOG_DUPLICATED_ORDERS = 'Duplicated Amazon orders with ID "%id%".';

    // ########################################

    /** @var $order Ess_M2ePro_Model_Account */
    private $account = NULL;

    /** @var $order Ess_M2ePro_Model_Order */
    private $order = NULL;

    private $status = self::STATUS_NOT_MODIFIED;

    private $shippingAddressUpdated = false;

    private $items = array();

    // ########################################

    public function setAccount(Ess_M2ePro_Model_Account $account)
    {
        $this->account = $account;

        return $this;
    }

    // ########################################

    public function initialize(array $data = array())
    {
        $this->initializeData($data);
        $this->initializeOrder();
    }

    // ########################################

    private function initializeData(array $data = array())
    {
        // Init general data
        // ------------------
        $this->setData('account_id', $this->account->getId());
        $this->setData('amazon_order_id', $data['amazon_order_id']);
        $this->setData('marketplace_id', $this->getMarketplaceId($data['marketplace_id']));

        $this->setData('status', $this->getStatus($data['status']));
        $this->setData('is_afn_channel', $data['is_afn_channel']);

        $this->setData('purchase_update_date', $data['purchase_update_date']);
        $this->setData('purchase_create_date', $data['purchase_create_date']);
        // ------------------

        // Init sale data
        // ------------------
        $this->setData('paid_amount', (float)$data['paid_amount']);
        $this->setData('tax_amount', (float)$data['tax_amount']);
        $this->setData('discount_amount', (float)$data['discount_amount']);
        $this->setData('currency', $data['currency']);
        $this->setData('qty_shipped', $data['qty_shipped']);
        $this->setData('qty_unshipped', $data['qty_unshipped']);
        // ------------------

        // Init customer/shipping data
        // ------------------
        $this->setData('buyer_name', $data['buyer_name']);
        $this->setData('buyer_email', $data['buyer_email']);
        $this->setData('shipping_service', $data['shipping_service']);
        $this->setData('shipping_address', $data['shipping_address']);
        $this->setData('shipping_price', (float)$data['shipping_price']);
        // ------------------

        $this->items = $data['items'];
    }

    private function getMarketplaceId($nativeMarketplaceId = NULL)
    {
        if ((int)$nativeMarketplaceId <= 0) {
            return NULL;
        }

        return Mage::helper('M2ePro/Component_Amazon')->getMarketplace((int)$nativeMarketplaceId, 'native_id')->getId();
    }

    private function getStatus($amazonOrderStatus)
    {
        $status = NULL;

        switch ($amazonOrderStatus) {
            case self::AMAZON_STATUS_PENDING:
                $status = Ess_M2ePro_Model_Amazon_Order::STATUS_PENDING;
                break;
            case self::AMAZON_STATUS_UNSHIPPED:
                $status = Ess_M2ePro_Model_Amazon_Order::STATUS_UNSHIPPED;
                break;
            case self::AMAZON_STATUS_SHIPPED_PARTIALLY:
                $status = Ess_M2ePro_Model_Amazon_Order::STATUS_SHIPPED_PARTIALLY;
                break;
            case self::AMAZON_STATUS_SHIPPED:
                $status = Ess_M2ePro_Model_Amazon_Order::STATUS_SHIPPED;
                break;
            case self::AMAZON_STATUS_UNFULFILLABLE:
                $status = Ess_M2ePro_Model_Amazon_Order::STATUS_UNFULFILLABLE;
                break;
            case self::AMAZON_STATUS_CANCELED:
                $status = Ess_M2ePro_Model_Amazon_Order::STATUS_CANCELED;
                break;
            case self::AMAZON_STATUS_INVOICE_UNCONFIRMED:
                $status = Ess_M2ePro_Model_Amazon_Order::STATUS_INVOICE_UNCONFIRMED;
                break;
        }

        return $status;
    }

    // ########################################

    private function initializeOrder()
    {
        $this->status = self::STATUS_NOT_MODIFIED;

        $existOrders = Mage::helper('M2ePro/Component_Amazon')
            ->getCollection('Order')
            ->addFieldToFilter('account_id', $this->account->getId())
            ->addFieldToFilter('amazon_order_id', $this->getData('amazon_order_id'))
            ->getItems();
        $existOrdersNumber = count($existOrders);

        if ($existOrdersNumber > 1) {
            $message = Mage::getModel('M2ePro/Log_Abstract')->encodeDescription(
                self::LOG_DUPLICATED_ORDERS, array('!id' => $this->getData('amazon_order_id'))
            );
            throw new Exception($message);
        }

        // New order
        // --------------------
        if ($existOrdersNumber == 0) {
            $this->status = self::STATUS_NEW;
            $this->order = Mage::helper('M2ePro/Component_Amazon')->getModel('Order');

            return;
        }
        // --------------------

        // Already exist order
        // --------------------
        $this->order = array_shift($existOrders);

        $purchaseUpdateDateCompareTo = strtotime($this->order->getData('purchase_update_date'));
        $purchaseUpdateDateCompareWith = strtotime($this->getData('purchase_update_date'));

        if ($purchaseUpdateDateCompareTo != $purchaseUpdateDateCompareWith ||
            $this->order->getData('status') != $this->getData('status') ||
            $this->order->getData('buyer_email') != $this->getData('buyer_email') ||
            $this->order->getData('qty_shipped') != $this->getData('qty_shipped')) {
            $this->status = self::STATUS_UPDATED;
        }
        // --------------------
    }

    // ########################################

    public function process()
    {
        if (!$this->canCreateOrder()) {
            return NULL;
        }

        $this->checkUpdates();

        $this->createOrder();
        $this->processItems();

        $this->processUpdates();

        return $this->order;
    }

    // ########################################

    private function processItems()
    {
        $itemsCollection = $this->order->getItemsCollection();
        $itemsCollection->load();

        foreach ($this->items as $itemData) {
            $itemData['order_id'] = $this->order->getId();

            /** @var $itemBuilder Ess_M2ePro_Model_Amazon_Order_Item_Builder */
            $itemBuilder = Mage::getModel('M2ePro/Amazon_Order_Item_Builder');
            $itemBuilder->initialize($itemData);

            $item = $itemBuilder->process();
            $item->setOrder($this->order);

            $itemsCollection->removeItemByKey($item->getId());
            $itemsCollection->addItem($item);
        }
    }

    // ########################################

    /**
     * @return bool
     */
    private function isSingle()
    {
        return count($this->items) == 1;
    }

    /**
     * @return bool
     */
    private function isCombined()
    {
        return count($this->items) > 1;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    private function isNew()
    {
        return $this->status == self::STATUS_NEW;
    }

    /**
     * @return bool
     */
    private function isUpdated()
    {
        return $this->status == self::STATUS_UPDATED;
    }

    // ########################################

    /**
     * @return bool
     */
    private function canCreateOrder()
    {
        if ($this->isNew() || $this->isUpdated()) {
            return true;
        }

        return false;
    }

    /**
     * @return Ess_M2ePro_Model_Order
     */
    private function createOrder()
    {
        $this->setData('shipping_address', json_encode($this->getData('shipping_address')));

        $this->order->addData($this->getData());
        $this->order->save();
    }

    // ########################################

    private function checkUpdates()
    {
        if (!$this->isUpdated()) {
            return;
        }

        if ($this->isShippingAddressUpdated()) {
            $this->shippingAddressUpdated = true;
        }

        // Check status
        // ------------------
        // todo log message when order status has been changed on Amazon
        // ------------------
    }

    // ----------------------------------------

    private function isShippingAddressUpdated()
    {
        if (!$this->isUpdated() || is_null($this->order->getMagentoOrderId())) {
            return false;
        }

        /** @var $amazonOrder Ess_M2ePro_Model_Amazon_Order */
        $amazonOrder = $this->order->getChildObject();

        if ($amazonOrder->getBuyerEmail() != $this->getData('buyer_email')) {
            return true;
        }

        $existShippingAddress = $amazonOrder->getShippingAddress();
        $newShippingAddress = $this->getData('shipping_address');

        ksort($existShippingAddress);
        ksort($newShippingAddress);

        return count($existShippingAddress) == count($newShippingAddress) &&
               count(array_diff($existShippingAddress, $newShippingAddress)) == 0;
    }

    // ########################################

    private function processUpdates()
    {
        if (!$this->isUpdated() || !$this->shippingAddressUpdated) {
            return;
        }

        $magentoOrder = $this->order->getMagentoOrder();

        if (is_null($magentoOrder)) {
            return;
        }

        if ($this->order->getChildObject()->isCanceled()) {
            return;
        }

        /** @var $magentoOrderUpdater Ess_M2ePro_Model_Magento_Order_Updater */
        $magentoOrderUpdater = Mage::getModel('M2ePro/Magento_Order_Updater');
        $magentoOrderUpdater->setMagentoOrder($magentoOrder);
        $magentoOrderUpdater->updateShippingAddress($this->order->getProxy()->getAddressData());
        $magentoOrderUpdater->updateCustomer($this->order->getProxy()->getAddressData());

        $magentoOrderUpdater->finishUpdate();
    }

    // ########################################
}
