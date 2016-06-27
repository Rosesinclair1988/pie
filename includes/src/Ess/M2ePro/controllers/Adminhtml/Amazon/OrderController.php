<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_Amazon_OrderController extends Ess_M2ePro_Controller_Adminhtml_MainController
{
    //#############################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('m2epro/sales')
             ->_title(Mage::helper('M2ePro')->__('M2E Pro'))
             ->_title(Mage::helper('M2ePro')->__('Sales'))
             ->_title(Mage::helper('M2ePro')->__('Amazon Orders'));

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
        $block->disableEbayTab();

        $this->getResponse()->setBody($block->getAmazonTabHtml());
    }

    public function gridAction()
    {
        $response = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_amazon_order_grid')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //#############################################

    public function viewAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Amazon')->getObject('Order', (int)$id);

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $order);

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_amazon_order_view'))
             ->renderLayout();
    }

    //#############################################

    public function orderItemGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Amazon')->getObject('Order', (int)$id);

        if (!$id || !$order->getId()) {
            return;
        }

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $order);

        $response = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_amazon_order_view_item')->toHtml();
        $this->getResponse()->setBody($response);
    }

    public function orderLogGridAction()
    {
        $id = $this->getRequest()->getParam('id');
        $order = Mage::helper('M2ePro/Component_Amazon')->getObject('Order', (int)$id);

        if (!$order->getId() && $id) {
            return;
        }

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $order);

        $response = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_amazon_order_view_log')->toHtml();
        $this->getResponse()->setBody($response);
    }

    //#############################################

    private function canCreateMagentoOrder(Ess_M2ePro_Model_Order $order)
    {
        if (!is_null($order->getData('magento_order_id'))) {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Magento Order is already created for this Amazon Order.')
            );
            return false;
        }

        $amazonAccount = $order->getChildObject()->getAmazonAccount();

        if (!$amazonAccount->isMagentoOrdersListingsModeEnabled() &&
            !$amazonAccount->isMagentoOrdersListingsOtherModeEnabled()) {
            $error  = Mage::helper('M2ePro')->__('Magento Order was not created. ');
            $error .= Mage::helper('M2ePro')->__('Reason: Magento Order creation is disabled in Account settings.');
            $this->_getSession()->addError($error);
            return false;
        }

        if ($order->hasListingItems() && !$amazonAccount->isMagentoOrdersListingsModeEnabled()) {
            $error  = Mage::helper('M2ePro')->__('Magento Order was not created. ');
            $error .= Mage::helper('M2ePro')->__('Reason: Magento Order creation for items listed by ');
            $error .= Mage::helper('M2ePro')->__('M2E Pro is disabled in Account settings.');
            $this->_getSession()->addError($error);
            return false;
        }

        if ($order->hasOtherListingItems() && !$amazonAccount->isMagentoOrdersListingsOtherModeEnabled()) {
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
        $order = Mage::helper('M2ePro/Component_Amazon')->getModel('Order')->load((int)$id);

        if (!$order->getId()) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Amazon Order does not exist.'));
            return $this->_redirect('*/adminhtml_order/index');
        }

        if ($this->canCreateMagentoOrder($order)) {
            // Create magento order
            // -------------
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
            // -------------

            // Create invoice
            // -------------
            $result = $order->createInvoice();
            $result && $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Invoice was created.'));
            // -------------

            // Create shipment
            // -------------
            $result = $order->createShipment();
            $result && $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Shipment was created.'));
            // -------------
        }

        $this->_redirect('*/*/view', array('id' => $id));
    }

    //#############################################

    public function updateShippingStatusAction()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var $order Ess_M2ePro_Model_Order */
        $order = Mage::helper('M2ePro/Component_Amazon')->getModel('Order')->load((int)$id);

        if (!$order->getId()) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Amazon Order does not exist.'));
            return $this->_redirect('*/adminhtml_order/index');
        }

        if (!$order->getChildObject()->canUpdateShippingStatus()) {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Amazon Order status cannot be updated to Shipped.')
            );
            return $this->_redirect('*/*/view', array('id' => $id));
        }

        if ($order->getChildObject()->updateShippingStatus()) {
            $this->_getSession()->addSuccess(
                Mage::helper('M2ePro')->__('Updating Amazon Order Status to Shipped in Progress...')
            );
        } else {
            $this->_getSession()->addError(
                Mage::helper('M2ePro')->__('Updating Amazon Order Status Failed.')
            );
        }

        return $this->_redirect('*/*/view', array('id' => $id));
    }

    //#############################################
}
