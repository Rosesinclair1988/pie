<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Ebay_Order_Builder extends Mage_Core_Model_Abstract
{
    const STATUS_NOT_MODIFIED = 0;
    const STATUS_NEW          = 1;
    const STATUS_UPDATED      = 2;

    const EBAY_CHECKOUT_STATUS_SINGLE_COMPLETE   = 'CheckoutComplete';
    const EBAY_CHECKOUT_STATUS_COMBINED_COMPLETE = 'Complete';

    const EBAY_PAYMENT_METHOD_NONE = 'None';
    const EBAY_PAYMENT_STATUS_SUCCEEDED = 'NoPaymentFailure';

    // Parser hack -> Mage::helper('M2ePro')->__('Payment status was updated to Paid on eBay.');
    // Parser hack -> Mage::helper('M2ePro')->__('Shipping status was updated to Shipped on eBay.');
    // Parser hack -> Mage::helper('M2ePro')->__('Buyer has changed shipping address on eBay.');
    // Parser hack -> Mage::helper('M2ePro')->__('Duplicated eBay orders with ID "%id%".');
    const LOG_PAYMENT_UPDATED   = 'Payment status was updated to Paid on eBay.';
    const LOG_SHIPPING_UPDATED  = 'Shipping status was updated to Shipped on eBay.';
    const LOG_ADDRESS_UPDATED   = 'Buyer has changed shipping address on eBay.';
    const LOG_DUPLICATED_ORDERS = 'Duplicated eBay orders with ID "%id%".';

    // ########################################

    /** @var $order Ess_M2ePro_Model_Account */
    private $account = NULL;

    /** @var $order Ess_M2ePro_Model_Order */
    private $order = NULL;

    private $status = self::STATUS_NOT_MODIFIED;

    private $shippingAddressUpdated = false;

    private $paymentDataUpdated = false;

    private $checkoutMessageUpdated = false;

    private $items = array();

    private $externalTransactions = array();

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

    protected function initializeData(array $data = array())
    {
        // Init general data
        // ------------------
        $this->setData('account_id', $this->account->getId());

        $this->setData('ebay_order_id', $data['ebay_order_id']);
        $this->setData('selling_manager_record_number', $data['selling_manager_record_number']);

        $this->setData('checkout_status', $this->getCheckoutStatus($data['checkout_status']));
        $this->setData('checkout_buyer_message', trim($data['checkout_buyer_message']));

        $this->setData('purchase_update_date', $data['purchase_update_date']);
        $this->setData('purchase_create_date', $data['purchase_create_date']);
        // ------------------

        // Init sale data
        // ------------------
        //$this->setData('price', (float)$data['price']); // do we need this?
        $this->setData('paid_amount', (float)$data['paid_amount']);
        $this->setData('saved_amount', (float)$data['saved_amount']);
        $this->setData('currency', $data['currency']);
        $this->setData('best_offer', (int)$data['best_offer']);
        $this->setData('final_fee', (float)$data['final_fee']);
        // ------------------

        // Init tax data
        // ------------------
        $this->setData('tax_rate', (float)$data['tax_rate']);
        $this->setData('tax_state', $data['tax_state']);
        $this->setData('tax_amount', (float)$data['tax_amount']);
        $taxIncludesShipping = $data['tax_includes_shipping']
            ? Ess_M2ePro_Model_Ebay_Order::TAX_SHIPPING_INCLUDED
            : Ess_M2ePro_Model_Ebay_Order::TAX_SHIPPING_EXCLUDED;
        $this->setData('tax_includes_shipping', $taxIncludesShipping);
        // ------------------

        // Init customer data
        // ------------------
        $this->setData('buyer_user_id', trim($data['buyer_user_id']));
        $this->setData('buyer_name', trim($data['buyer_name']));
        $this->setData('buyer_email', trim($data['buyer_email']));
        // ------------------

        // Init payment data
        // ------------------
        $this->setData('payment_method', $data['payment_method']);
        $this->setData('payment_status_ebay', $data['payment_status_ebay']);
        $this->setData('payment_status_hold', $data['payment_status_hold']);
        $this->setData('payment_date', $data['payment_date']);
        $this->setData('payment_status', $this->getPaymentStatus());
        // ------------------

        // Init shipping data
        // ------------------
        $this->setData('get_it_fast', (int)$data['get_it_fast']);
        $this->setData('shipping_method', $data['shipping_method']);
        $this->setData('shipping_method_selected', (int)$data['shipping_method_selected']);
        $this->setData('shipping_address', $data['shipping_address']);
        $this->setData('shipping_tracking_details', $data['shipping_tracking_details']);
        $this->setData('shipping_price', (float)$data['shipping_price']);
        $this->setData('shipping_type', $data['shipping_type']);
        $this->setData('shipping_date', $data['shipping_date']);
        $this->setData('shipping_status', $this->getShippingStatus());
        // ------------------

        $this->items = $data['items'];
        $this->externalTransactions = $data['external_transactions'];
    }

    private function getCheckoutStatus($ebayCheckoutStatus)
    {
        if ($ebayCheckoutStatus == self::EBAY_CHECKOUT_STATUS_SINGLE_COMPLETE ||
            $ebayCheckoutStatus == self::EBAY_CHECKOUT_STATUS_COMBINED_COMPLETE) {
            return Ess_M2ePro_Model_Ebay_Order::CHECKOUT_STATUS_COMPLETED;
        }

        return Ess_M2ePro_Model_Ebay_Order::CHECKOUT_STATUS_INCOMPLETE;
    }

    private function getPaymentStatus()
    {
        if ($this->_data['payment_method'] == self::EBAY_PAYMENT_METHOD_NONE) {

            if ($this->_data['payment_date']) {
                return Ess_M2ePro_Model_Ebay_Order::PAYMENT_STATUS_COMPLETED;
            }

            if ($this->_data['payment_status_ebay'] == self::EBAY_PAYMENT_STATUS_SUCCEEDED) {
                return Ess_M2ePro_Model_Ebay_Order::PAYMENT_STATUS_NOT_SELECTED;
            }

        } else {

            if ($this->_data['payment_status_ebay'] == self::EBAY_PAYMENT_STATUS_SUCCEEDED) {
                return $this->_data['payment_date']
                    ? Ess_M2ePro_Model_Ebay_Order::PAYMENT_STATUS_COMPLETED
                    : Ess_M2ePro_Model_Ebay_Order::PAYMENT_STATUS_PROCESS;
            }

        }

        return Ess_M2ePro_Model_Ebay_Order::PAYMENT_STATUS_ERROR;
    }

    private function getShippingStatus()
    {
        if (!$this->_data['shipping_date']) {
            return $this->_data['shipping_method_selected']
                ? Ess_M2ePro_Model_Ebay_Order::SHIPPING_STATUS_PROCESSING
                : Ess_M2ePro_Model_Ebay_Order::SHIPPING_STATUS_NOT_SELECTED;
        }

        return Ess_M2ePro_Model_Ebay_Order::SHIPPING_STATUS_COMPLETED;
    }

    // ########################################

    private function initializeOrder()
    {
        $this->status = self::STATUS_NOT_MODIFIED;

        $existOrders = Mage::helper('M2ePro/Component_Ebay')
            ->getCollection('Order')
            ->addFieldToFilter('account_id', $this->account->getId())
            ->addFieldToFilter('ebay_order_id', $this->getData('ebay_order_id'))
            ->getItems();
        $existOrdersNumber = count($existOrders);

        if ($existOrdersNumber > 1) {
            $message = Mage::getModel('M2ePro/Log_Abstract')->encodeDescription(
                self::LOG_DUPLICATED_ORDERS, array('!id' => $this->getData('ebay_order_id'))
            );
            throw new Exception($message);
        }

        // New order
        // --------------------
        if ($existOrdersNumber == 0) {
            $this->status = self::STATUS_NEW;
            $this->order = Mage::helper('M2ePro/Component_Ebay')->getModel('Order');

            return;
        }
        // --------------------

        // Already exist order
        // --------------------
        $this->order = reset($existOrders);

        $purchaseUpdateDateCompareTo = strtotime($this->order->getData('purchase_update_date'));
        $purchaseUpdateDateCompareWith = strtotime($this->getData('purchase_update_date'));

        if ($purchaseUpdateDateCompareTo != $purchaseUpdateDateCompareWith ||
            $this->order->getData('checkout_status') != $this->getData('checkout_status') ||
            $this->order->getData('payment_status')  != $this->getData('payment_status') ||
            $this->order->getData('shipping_status') != $this->getData('shipping_status')) {
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

        $this->initializeMarketplace();

        $this->checkUpdates();

        $this->createOrder();
        $this->processItems();
        $this->processExternalTransactions();

        $this->processUpdates();

        return $this->order;
    }

    // ########################################

    private function initializeMarketplace()
    {
        // Get first order item
        // ------------------
        $item = reset($this->items);
        // ------------------

        if (empty($item['item_site'])) {
            return;
        }

        $marketplace = Mage::helper('M2ePro/Component_Ebay')->getMarketplace($item['item_site'],'code');

        $this->setData('marketplace_id', $marketplace->getId());

        if (!is_null($marketplace->getId())) {
            $this->initializePaymentService($marketplace);
            $this->initializeShippingService($marketplace);
        }
    }

    private function initializeShippingService(Ess_M2ePro_Model_Marketplace $marketplace)
    {
        /** @var $connRead Varien_Db_Adapter_Pdo_Mysql */
        $connRead = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableDictShipping = Mage::getSingleton('core/resource')->getTableName('m2epro_ebay_dictionary_shipping');

        $dbSelect = $connRead->select()
            ->from($tableDictShipping, 'title')
            ->where('`marketplace_id` = ?', (int)$marketplace->getId())
            ->where('`ebay_id` = ?', $this->getData('shipping_method'));
        $shipping = $connRead->fetchRow($dbSelect);

        if (!empty($shipping['title'])) {
            $this->setData('shipping_method', $shipping['title']);
        }
    }

    private function initializePaymentService(Ess_M2ePro_Model_Marketplace $marketplace)
    {
        /** @var $connRead Varien_Db_Adapter_Pdo_Mysql */
        $connRead = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableDictMarketplace = Mage::getSingleton('core/resource')->getTableName('m2epro_ebay_dictionary_marketplace');

        $dbSelect = $connRead->select()
            ->from($tableDictMarketplace, 'payments')
            ->where('`marketplace_id` = ?', (int)$marketplace->getId());
        $marketplace = $connRead->fetchRow($dbSelect);

        if (!$marketplace) {
            return;
        }

        $payments = (array)json_decode($marketplace['payments'], true);

        foreach ($payments as $payment) {
            if ($payment['ebay_id'] == $this->getData('payment_method')) {
                $this->setData('payment_method', $payment['title']);
                break;
            }
        }
    }

    // ########################################

    private function processItems()
    {
        $itemsCollection = $this->order->getItemsCollection();
        $itemsCollection->load();

        foreach ($this->items as $itemData) {
            $itemData['order_id'] = $this->order->getId();

            /** @var $itemBuilder Ess_M2ePro_Model_Ebay_Order_Item_Builder */
            $itemBuilder = Mage::getModel('M2ePro/Ebay_Order_Item_Builder');
            $itemBuilder->initialize($itemData);

            $item = $itemBuilder->process($this->order, $this->isNew(), $this->isCombined());
            $item->setOrder($this->order);

            $itemsCollection->removeItemByKey($item->getId());
            $itemsCollection->addItem($item);
        }
    }

    // ########################################

    private function processExternalTransactions()
    {
        $externalTransactionsCollection = $this->order->getChildObject()->getExternalTransactionsCollection();
        $externalTransactionsCollection->load();

        foreach ($this->externalTransactions as $transactionData) {
            $transactionData['order_id'] = $this->order->getId();

            /** @var $transactionBuilder Ess_M2ePro_Model_Ebay_Order_ExternalTransaction_Builder */
            $transactionBuilder = Mage::getModel('M2ePro/Ebay_Order_ExternalTransaction_Builder');
            $transactionBuilder->initialize($transactionData);

            $transaction = $transactionBuilder->process();
            $transaction->setOrder($this->order);

            $externalTransactionsCollection->removeItemByKey($transaction->getId());
            $externalTransactionsCollection->addItem($transaction);
        }
    }

    // ########################################

    /**
     * @return bool
     */
    public function isRefund()
    {
        return $this->getData('is_refund');
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isSingle()
    {
        return count($this->items) == 1;
    }

    /**
     * @return bool
     */
    public function isCombined()
    {
        return count($this->items) > 1;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isNew()
    {
        return $this->status == self::STATUS_NEW;
    }

    /**
     * @return bool
     */
    public function isUpdated()
    {
        return $this->status == self::STATUS_UPDATED;
    }

    // ########################################

    /**
     * @return bool
     */
    private function canCreateOrder()
    {
        if ($this->isRefund()) {
            return false;
        }

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
        $this->setData('shipping_tracking_details', json_encode($this->getData('shipping_tracking_details')));

        $this->order->addData($this->getData());
        $this->order->save();

        $this->order->setAccount($this->account);

        return $this->order;
    }

    // ########################################

    private function checkUpdates()
    {
        if (!$this->isUpdated()) {
            return;
        }

        $this->isCheckoutMessageUpdated() && $this->checkoutMessageUpdated = true;

        // Check payment status
        // ------------------
        if ($this->isPaymentStatusUpdated()) {
            $this->order->addSuccessLog(self::LOG_PAYMENT_UPDATED);
        }
        // ------------------

        // Check shipping status
        // ------------------
        if ($this->isShippingStatusUpdated()) {
            $this->order->addSuccessLog(self::LOG_SHIPPING_UPDATED);
        }
        // ------------------

        // Check shipping address
        // ------------------
        if ($this->isShippingAddressUpdated()) {
            $this->order->addWarningLog(self::LOG_ADDRESS_UPDATED);
            $this->shippingAddressUpdated = true;
        }
        // ------------------

        // Check payment data
        // ------------------
        if ($this->isPaymentDataUpdated()) {
            $this->paymentDataUpdated = true;
        }
        // ------------------
    }

    // ----------------------------------------

    private function isCheckoutMessageUpdated()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        if ($this->getData('checkout_buyer_message') == '') {
            return false;
        }

        return $this->getData('checkout_buyer_message') == $this->order->getChildObject()->getCheckoutBuyerMessage();
    }

    // ----------------------------------------

    private function isPaymentStatusUpdated()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        /** @var $ebayOrder Ess_M2ePro_Model_Ebay_Order */
        $ebayOrder = $this->order->getChildObject();

        return !$ebayOrder->isPaymentCompleted() &&
            $this->getData('payment_status') == Ess_M2ePro_Model_Ebay_Order::PAYMENT_STATUS_COMPLETED;
    }

    // ----------------------------------------

    private function isShippingStatusUpdated()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        /** @var $ebayOrder Ess_M2ePro_Model_Ebay_Order */
        $ebayOrder = $this->order->getChildObject();

        return !$ebayOrder->isShippingCompleted() &&
            $this->getData('shipping_status') == Ess_M2ePro_Model_Ebay_Order::SHIPPING_STATUS_COMPLETED;
    }

    // ----------------------------------------

    private function isShippingAddressUpdated()
    {
        if (!$this->isUpdated() || is_null($this->order->getMagentoOrderId())) {
            return false;
        }

        /** @var $ebayOrder Ess_M2ePro_Model_Ebay_Order */
        $ebayOrder = $this->order->getChildObject();

        if ($ebayOrder->getBuyerEmail() != $this->getData('buyer_email')) {
            return true;
        }

        $existShippingAddress = $ebayOrder->getShippingAddress();
        $newShippingAddress = $this->getData('shipping_address');

        ksort($existShippingAddress);
        ksort($newShippingAddress);

        return count($existShippingAddress) == count($newShippingAddress) &&
            count(array_diff($existShippingAddress, $newShippingAddress)) == 0;
    }

    // ----------------------------------------

    private function isPaymentDataUpdated()
    {
        if (!$this->isUpdated()) {
            return false;
        }

        /** @var $ebayOrder Ess_M2ePro_Model_Ebay_Order */
        $ebayOrder = $this->order->getChildObject();

        if ($ebayOrder->getData('payment_method') != $this->getData('payment_method')) {
            return true;
        }

        if (!$ebayOrder->hasExternalTransactions() && $this->hasExternalTransactions()) {
            return true;
        }

        return false;
    }

    private function hasExternalTransactions()
    {
        return count($this->externalTransactions) > 0;
    }

    // ########################################

    private function processUpdates()
    {
        if (!$this->isUpdated()) {
            return;
        }

        $magentoOrder = NULL;
        if ($this->shippingAddressUpdated || $this->paymentDataUpdated || $this->checkoutMessageUpdated) {
            $magentoOrder = $this->order->getMagentoOrder();
        }

        if (is_null($magentoOrder)) {
            return;
        }

        /** @var $magentoOrderUpdater Ess_M2ePro_Model_Magento_Order_Updater */
        $magentoOrderUpdater = Mage::getModel('M2ePro/Magento_Order_Updater');
        $magentoOrderUpdater->setMagentoOrder($magentoOrder);

        if ($this->shippingAddressUpdated) {
            $magentoOrderUpdater->updateShippingAddress($this->order->getProxy()->getAddressData());
            $magentoOrderUpdater->updateCustomer($this->order->getProxy()->getAddressData());
        }

        if ($this->paymentDataUpdated) {
            $magentoOrderUpdater->updatePaymentData($this->order->getProxy()->getPaymentData());
        }

        if ($this->checkoutMessageUpdated) {
            $magentoOrderUpdater->updateComments($this->order->getChildObject()->getCheckoutBuyerMessage());
        }

        $magentoOrderUpdater->finishUpdate();
    }

    // ########################################
}
