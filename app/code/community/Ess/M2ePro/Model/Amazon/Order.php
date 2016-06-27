<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Order extends Ess_M2ePro_Model_Component_Child_Amazon_Abstract
{
    // Parser hack -> Mage::helper('M2ePro')->__('Order Status cannot be Updated. Reason: %msg%');
    const LOG_STATUS_UPDATE_FAILED = 'Order Status cannot be Updated. Reason: %msg%';

    const STATUS_PENDING             = 0;
    const STATUS_UNSHIPPED           = 1;
    const STATUS_SHIPPED_PARTIALLY   = 2;
    const STATUS_SHIPPED             = 3;
    const STATUS_UNFULFILLABLE       = 4;
    const STATUS_CANCELED            = 5;
    const STATUS_INVOICE_UNCONFIRMED = 6;

    // ########################################

    private $relatedAmazonItems = NULL;

    private $subTotalPrice = NULL;

    private $grandTotalPrice = NULL;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Amazon_Order');
    }

    /**
     * @return Ess_M2ePro_Model_Order
     */
    public function getParentObject()
    {
        return parent::getParentObject();
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Amazon_Account
     */
    public function getAmazonAccount()
    {
        return $this->getParentObject()->getAccount()->getChildObject();
    }

    // ########################################

    /**
     * @return array
     */
    public function getRelatedChannelItems()
    {
        if (is_null($this->relatedAmazonItems)) {
            $this->relatedAmazonItems = array();

            foreach ($this->getParentObject()->getItemsCollection()->getItems() as $item) {
                if (!is_null($item->getChildObject()->getAmazonItem())) {
                    $this->relatedAmazonItems[] = $item->getChildObject()->getAmazonItem();
                }
            }
        }

        return $this->relatedAmazonItems;
    }

    // ########################################

    public function getAmazonOrderId()
    {
        return $this->getData('amazon_order_id');
    }

    public function getBuyerName()
    {
        return $this->getData('buyer_name');
    }

    public function getBuyerEmail()
    {
        $email = $this->getData('buyer_email');

        if ($email == '') {
            $email = str_replace(' ', '-', strtolower($this->getBuyerName()));
            $email .= Ess_M2ePro_Model_Magento_Customer::FAKE_EMAIL_POSTFIX;
        }

        return $email;
    }

    public function getStatus()
    {
        return (int)$this->getData('status');
    }

    public function getCurrency()
    {
        return $this->getData('currency');
    }

    public function getShippingService()
    {
        return $this->getData('shipping_service');
    }

    public function getShippingPrice()
    {
        return (float)$this->getData('shipping_price');
    }

    /**
     * @return array
     */
    public function getShippingAddress()
    {
        return json_decode($this->getData('shipping_address'), true);
    }

    public function getPaidAmount()
    {
        return (float)$this->getData('paid_amount');
    }

    public function getTaxAmount()
    {
        return (float)$this->getData('tax_amount');
    }

    // ########################################

    public function isPending()
    {
        return $this->getStatus() == self::STATUS_PENDING;
    }

    public function isUnshipped()
    {
        return $this->getStatus() == self::STATUS_UNSHIPPED;
    }

    public function isPartiallyShipped()
    {
        return $this->getStatus() == self::STATUS_SHIPPED_PARTIALLY;
    }

    public function isShipped()
    {
        return $this->getStatus() == self::STATUS_SHIPPED;
    }

    public function isUnfulfillable()
    {
        return $this->getStatus() == self::STATUS_UNFULFILLABLE;
    }

    public function isCanceled()
    {
        return $this->getStatus() == self::STATUS_CANCELED;
    }

    public function isInvoiceUnconfirmed()
    {
        return $this->getStatus() == self::STATUS_INVOICE_UNCONFIRMED;
    }

    // ########################################

    public function getSubtotalPrice()
    {
        if (is_null($this->subTotalPrice)) {
            $this->subTotalPrice = $this->getResource()->getItemsTotal($this->getId());
        }

        return $this->subTotalPrice;
    }

    public function getGrandTotalPrice()
    {
        if (is_null($this->grandTotalPrice)) {
            $this->grandTotalPrice = $this->getSubtotalPrice();
            $this->grandTotalPrice += round((float)$this->getData('shipping_price'), 2);
        }

        return $this->grandTotalPrice;
    }

    // ########################################

    public function getRawAddressData()
    {
        $shippingAddress = $this->getShippingAddress();

        return array(
            'buyer_name'     => $this->getBuyerName(),
            'email'          => $this->getBuyerEmail(),
            'recipient_name' => $shippingAddress['recipient_name'],
            'country_id'     => $shippingAddress['country_code'],
            'region'         => $shippingAddress['state'] ? $shippingAddress['state'] : $shippingAddress['county'],
            'city'           => $shippingAddress['city'],
            'postcode'       => $shippingAddress['postal_code'],
            'telephone'      => $shippingAddress['phone'],
            'street'         => array_filter($shippingAddress['street'])
        );
    }

    // ########################################

    public function getStatusForMagentoOrder()
    {
        $status = '';
        $this->isPending()          && $status = $this->getAmazonAccount()->getMagentoOrdersStatusNew();
        $this->isUnshipped()        && $status = $this->getAmazonAccount()->getMagentoOrdersStatusProcessing();
        $this->isPartiallyShipped() && $status = $this->getAmazonAccount()->getMagentoOrdersStatusProcessing();
        $this->isShipped()          && $status = $this->getAmazonAccount()->getMagentoOrdersStatusShipped();

        return $status;
    }

    // ########################################

    public function getAssociatedStoreId()
    {
        $storeId = NULL;

        $relatedChannelItems = $this->getRelatedChannelItems();

        if (count($relatedChannelItems) == 0) {
            // 3rd party order
            // ---------------
            $storeId = $this->getAmazonAccount()->getMagentoOrdersListingsOtherStoreId();
            // ---------------
        } else {
            // M2E order
            // ---------------
            if ($this->getAmazonAccount()->isMagentoOrdersListingsStoreCustom()) {
                $storeId = $this->getAmazonAccount()->getMagentoOrdersListingsStoreId();
            } else {
                $firstChannelItem = reset($relatedChannelItems);
                $storeId = $firstChannelItem->getStoreId();
            }
            // ---------------
        }

        if ($storeId == 0) {
            $storeId = Mage::helper('M2ePro/Magento')->getDefaultStoreId();
        }

        return $storeId;
    }

    // ########################################

    /**
     * Check possibility for magento order creation
     *
     * @return bool
     */
    public function canCreateMagentoOrder()
    {
        if (!is_null($this->getParentObject()->getMagentoOrderId())) {
            return false;
        }

        $amazonAccount = $this->getAmazonAccount();

        if (!$amazonAccount->isMagentoOrdersListingsModeEnabled() &&
            !$amazonAccount->isMagentoOrdersListingsOtherModeEnabled()) {
            return false;
        }

        if ($this->isPending() || $this->isCanceled()) {
            return false;
        }

        // M2E order
        // ---------------
        if ($this->getParentObject()->hasListingItems() &&
            !$amazonAccount->isMagentoOrdersListingsModeEnabled()) {
            return false;
        }
        // ---------------

        // 3rd party order
        // ---------------
        if ($this->getParentObject()->hasOtherListingItems() &&
            !$amazonAccount->isMagentoOrdersListingsOtherModeEnabled()) {
            return false;
        }
        // ---------------

        return true;
    }

    public function beforeCreateMagentoOrder()
    {

    }

    public function afterCreateMagentoOrder()
    {
        if ($this->getAmazonAccount()->isMagentoOrdersCustomerNewNotifyWhenOrderCreated()) {
            $this->getParentObject()->getMagentoOrder()->sendNewOrderEmail();
        }
    }

    // ########################################

    public function canCancelMagentoOrder()
    {
        return $this->isCanceled();
    }

    // ########################################

    public function createPaymentTransactions()
    {
        return NULL;
    }

    // ########################################

    public function canCreateInvoice()
    {
        if (!$this->getAmazonAccount()->isMagentoOrdersInvoiceEnabled()) {
            return false;
        }

        if ($this->isPending() || $this->isCanceled() || $this->isUnfulfillable() || $this->isInvoiceUnconfirmed()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if (is_null($magentoOrder)) {
            return false;
        }

        if ($magentoOrder->hasInvoices() || !$magentoOrder->canInvoice()) {
            return false;
        }

        return true;
    }

    // ----------------------------------------

    public function createInvoice()
    {
        if (!$this->canCreateInvoice()) {
            return NULL;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();

        // Create invoice
        // -------------
        /** @var $invoiceBuilder Ess_M2ePro_Model_Magento_Order_Invoice */
        $invoiceBuilder = Mage::getModel('M2ePro/Magento_Order_Invoice');
        $invoiceBuilder->setMagentoOrder($magentoOrder);
        $invoiceBuilder->buildInvoice();
        // -------------

        $invoice = $invoiceBuilder->getInvoice();

        if ($this->getAmazonAccount()->isMagentoOrdersCustomerNewNotifyWhenInvoiceCreated()) {
            $invoice->sendEmail();
        }

        return $invoice;
    }

    // ########################################

    public function canCreateShipment()
    {
        if (!$this->getAmazonAccount()->isMagentoOrdersShipmentEnabled()) {
            return false;
        }

        if (!$this->isShipped()) {
            return false;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();
        if (is_null($magentoOrder)) {
            return false;
        }

        if ($magentoOrder->hasShipments() || !$magentoOrder->canShip()) {
            return false;
        }

        return true;
    }

    // ----------------------------------------

    public function createShipment()
    {
        if (!$this->canCreateShipment()) {
            return NULL;
        }

        $magentoOrder = $this->getParentObject()->getMagentoOrder();

        // Create shipment
        // -------------
        /** @var $shipmentBuilder Ess_M2ePro_Model_Magento_Order_Shipment */
        $shipmentBuilder = Mage::getModel('M2ePro/Magento_Order_Shipment');
        $shipmentBuilder->setMagentoOrder($magentoOrder);
        $shipmentBuilder->buildShipment();
        // -------------

        return $shipmentBuilder->getShipment();
    }

    // ########################################

    public function createTracks()
    {
        return NULL;
    }

    // ########################################

    private function processConnector(array $params)
    {
        $dispatcherObject = Mage::getModel('M2ePro/Amazon_Connector')->getDispatcher();
        $dispatcherObject->processConnector('orders', 'update', 'items', $params,
            $this->getParentObject()->getMarketplace(),
            $this->getParentObject()->getAccount()
        );
    }

    // ########################################

    public function canUpdateShippingStatus(array $trackingDetails = array())
    {
        if ($this->getParentObject()->isLockedObject('update_shipping_status')) {
            return false;
        }

        if ($this->isShipped() && empty($trackingDetails)) {
            return false;
        }

        if ($this->isPending() || $this->isCanceled()) {
            return false;
        }

        return true;
    }

    public function updateShippingStatus(array $trackingDetails = array())
    {
        if (!$this->canUpdateShippingStatus($trackingDetails)) {
            return false;
        }

        $params = array(
            'order' => $this->getParentObject(),
            'amazon_order_id' => $this->getAmazonOrderId(),
        );

        if (!empty($trackingDetails['tracking_number'])) {

            $supportedCarriers = Mage::helper('M2ePro/Component_Amazon')->getCarriers();

            if (isset($supportedCarriers[$trackingDetails['carrier_code']])) {
                $carrierName = $supportedCarriers[$trackingDetails['carrier_code']];
            } else if (!empty($trackingDetails['carrier_title'])) {
                $carrierName = $trackingDetails['carrier_title'];
            } else {
                $carrierName = $trackingDetails['carrier_code'];
            }

            $params['tracking_details'] = array(
                'tracking_number' => $trackingDetails['tracking_number'],
                'carrier_name'    => $carrierName
            );
        }

        try {
            $this->processConnector($params);
        } catch (Exception $e) {
            $log = $this->getParentObject()->makeLog(self::LOG_STATUS_UPDATE_FAILED, array('msg' => $e->getMessage()));
            $this->getParentObject()->addErrorLog($log);

            return false;
        }

        return true;
    }

    // ########################################

    public function deleteInstance()
    {
        return $this->delete();
    }

    // ########################################
}
