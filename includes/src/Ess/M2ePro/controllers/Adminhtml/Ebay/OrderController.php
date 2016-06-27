<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_Ebay_OrderController extends Ess_M2ePro_Controller_Adminhtml_MainController
{
    //#############################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('m2epro/sales')
             ->_title(Mage::helper('M2ePro')->__('M2E Pro'))
             ->_title(Mage::helper('M2ePro')->__('Sales'))
             ->_title(Mage::helper('M2ePro')->__('eBay Orders'));

        $this->getLayout()->getBlock('head')
             ->addJs('M2ePro/OrderHandler.js');

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro/sales/order');
    }

    //#############################################

    public function indexAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $this->_redirect('*/adminhtml_order/index');
        }

        /** @var $block Ess_M2ePro_Block_Adminhtml_Order */
        $block = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_order');
        $block->disableAmazonTab();

        $this->getResponse()->setBody($block->getEbayTabHtml());
    }

    public function gridAction()
    {
        $response = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_ebay_order_grid')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //#############################################

    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Ebay')->getObject('Order', (int)$id);

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $order);

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_ebay_order_view'))
             ->renderLayout();
    }

    //#############################################

    public function orderItemGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Ebay')->getObject('Order', (int)$id);

        if (!$id || !$order->getId()) {
            return;
        }

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $order);

        $response = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_ebay_order_view_item')->toHtml();
        $this->getResponse()->setBody($response);
    }

    public function orderLogGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Ebay')->getObject('Order', (int)$id);

        if (!$id || !$order->getId()) {
            return;
        }

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $order);

        $response = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_ebay_order_view_log')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //#############################################

    private function processConnector($action, array $params = array())
    {
        $id = $this->getRequest()->getParam('id');
        $ids = $this->getRequest()->getParam('ids');

        if (is_null($id) && is_null($ids)) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Please select order(s).'));
            return $this->_redirect('*/adminhtml_order/index');
        }

        $ordersIds = array();
        !is_null($id) && $ordersIds[] = $id;
        !is_null($ids) && $ordersIds = array_merge($ordersIds,(array)$ids);

        return Mage::getModel('M2ePro/Connector_Server_Ebay_Order_Dispatcher')->process($action, $ordersIds, $params);
    }

    //--------------------

    public function updatePaymentStatusAction()
    {
        if ($this->processConnector(Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher::ACTION_PAY)) {
            $this->_getSession()->addSuccess(
                Mage::helper('M2ePro')->__('Payment status for selected eBay Order(s) was updated to Paid.')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Payment status for selected eBay Order(s) was not updated.')
            );
        }

        $id = $this->getRequest()->getParam('id');

        return is_null($id)
            ? $this->_redirect('*/adminhtml_order/index') : $this->_redirect('*/*/view', array('id' => $id));
    }

    public function updateShippingStatusAction()
    {
        if ($this->processConnector(Ess_M2ePro_Model_Connector_Server_Ebay_Order_Dispatcher::ACTION_SHIP)) {
            $this->_getSession()->addSuccess(
                Mage::helper('M2ePro')->__('Shipping status for selected eBay Order(s) was updated to Shipped.')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Shipping status for selected eBay Order(s) was not updated.')
            );
        }

        $id = $this->getRequest()->getParam('id');

        return is_null($id)
            ? $this->_redirect('*/adminhtml_order/index') : $this->_redirect('*/*/view', array('id' => $id));
    }

    //#############################################

    private function canCreateMagentoOrder(Ess_M2ePro_Model_Order $order)
    {
        if (!is_null($order->getData('magento_order_id'))) {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Magento Order is already created for this eBay Order.')
            );
            return false;
        }

        $ebayAccount = $order->getChildObject()->getEbayAccount();

        if (!$ebayAccount->isMagentoOrdersListingsModeEnabled() &&
            !$ebayAccount->isMagentoOrdersListingsOtherModeEnabled()) {
            $error  = Mage::helper('M2ePro')->__('Magento Order was not created. ');
            $error .= Mage::helper('M2ePro')->__('Reason: Magento Order creation is disabled in Account settings.');
            $this->_getSession()->addError($error);
            return false;
        }

        if ($order->hasListingItems() && !$ebayAccount->isMagentoOrdersListingsModeEnabled()) {
            $error  = Mage::helper('M2ePro')->__('Magento Order was not created. ');
            $error .= Mage::helper('M2ePro')->__('Reason: Magento Order creation for items listed by ');
            $error .= Mage::helper('M2ePro')->__('M2E Pro is disabled in Account settings.');
            $this->_getSession()->addError($error);
            return false;
        }

        if ($order->hasOtherListingItems() && !$ebayAccount->isMagentoOrdersListingsOtherModeEnabled()) {
            $error  = Mage::helper('M2ePro')->__('Magento Order was not created. Reason: Magento Order creation for ');
            $error .= Mage::helper('M2ePro')->__('items listed by 3rd party software is disabled in Account settings.');
            $this->_getSession()->addError($error);
            return false;
        }

        return true;
    }

    public function createMagentoOrderAction()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var $order Ess_M2ePro_Model_Order */
        $order = Mage::helper('M2ePro/Component_Ebay')->getModel('Order')->load((int)$id);

        if (!$order->getId()) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('eBay Order does not exist.'));
            return $this->_redirect('*/adminhtml_order/index');
        }

        if ($this->canCreateMagentoOrder($order)) {

            $result = $order->createMagentoOrder();
            $order->updateMagentoOrderStatus();

            if ($result) {
                $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Magento Order was created.'));
            } else {
                $lastOrderLog = Mage::getModel('M2ePro/Order_Log')->getLastByOrderId($order->getId());
                $lastOrderLogMessage = Mage::getSingleton('M2ePro/Log_Abstract')->decodeDescription(
                    $lastOrderLog->getMessage()
                );

                $this->_getSession()->addError($lastOrderLogMessage);
            }

            $order->createPaymentTransactions();

            $result = $order->createInvoice();
            $result && $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Invoice was created.'));

            $result = $order->createShipment();
            $result && $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Shipment was created.'));

            $order->createTracks();
        }

        $this->_redirect('*/*/view', array('id' => $id));
    }

    //#############################################

    public function goToPaypalAction()
    {
        $transactionId = $this->getRequest()->getParam('transaction_id');

        if (!$transactionId) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Transaction ID should be defined.'));
            return $this->_redirect('*/adminhtml_order/index');
        }

        /** @var $transaction Ess_M2ePro_Model_Ebay_Order_ExternalTransaction */
        $transaction = Mage::getModel('M2ePro/Ebay_Order_ExternalTransaction')->load($transactionId, 'transaction_id');

        if (is_null($transaction->getId())) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('eBay order transaction does not exist.'));
            return $this->_redirect('*/adminhtml_order/index');
        }

        if (!$transaction->isPaypal()) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('This is not a PayPal transaction.'));
            return $this->_redirect('*/adminhtml_order/index');
        }

        return $this->_redirectUrl($transaction->getPaypalUrl());
    }

    //#############################################
}
