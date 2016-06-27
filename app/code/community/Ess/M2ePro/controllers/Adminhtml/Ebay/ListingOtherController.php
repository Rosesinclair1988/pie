<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_Ebay_ListingOtherController extends Ess_M2ePro_Controller_Adminhtml_MainController
{
    //#############################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('m2epro/listings')
             ->_title(Mage::helper('M2ePro')->__('M2E Pro'))
             ->_title(Mage::helper('M2ePro')->__('Manage Listings'))
             ->_title(Mage::helper('M2ePro')->__('3rd Party Listings'));

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro/listings/listing_other');
    }

    //#############################################

    public function indexAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->_redirect('*/adminhtml_listingOther/index');
        }

        /** @var $block Ess_M2ePro_Block_Adminhtml_Listing_Other */
        $block = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_listing_other');
        $block->disableAmazonTab();

        $this->getResponse()->setBody($block->getEbayTabHtml());
    }

    public function gridAction()
    {
        $block = $this->loadLayout()->getLayout()->createBlock('M2ePro/adminhtml_ebay_listing_other_grid');
        $this->getResponse()->setBody($block->toHtml());
    }

    //#############################################

    protected function processConnector($action, array $params = array())
    {
        if (!$ebayProductsIds = $this->getRequest()->getParam('selected_products')) {
            exit('You should select products');
        }

        $ebayProductsIds = explode(',', $ebayProductsIds);

        $dispatcherObject = Mage::getModel('M2ePro/Connector_Server_Ebay_OtherItem_Dispatcher');
        $result = (int)$dispatcherObject->process($action, $ebayProductsIds, $params);
        $actionId = (int)$dispatcherObject->getLogsActionId();

        if ($result == Ess_M2ePro_Model_Connector_Server_Ebay_Item_Abstract::STATUS_ERROR) {
            exit(json_encode(array('result'=>'error','action_id'=>$actionId)));
        }

        if ($result == Ess_M2ePro_Model_Connector_Server_Ebay_Item_Abstract::STATUS_WARNING) {
            exit(json_encode(array('result'=>'warning','action_id'=>$actionId)));
        }

        if ($result == Ess_M2ePro_Model_Connector_Server_Ebay_Item_Abstract::STATUS_SUCCESS) {
            exit(json_encode(array('result'=>'success','action_id'=>$actionId)));
        }

        exit(json_encode(array('result'=>'error','action_id'=>$actionId)));
    }

    //-------------------------------------------

    public function runRelistProductsAction()
    {
        $this->processConnector(Ess_M2ePro_Model_Connector_Server_Ebay_Item_Dispatcher::ACTION_RELIST,array());
    }

    public function runStopProductsAction()
    {
        $this->processConnector(Ess_M2ePro_Model_Connector_Server_Ebay_Item_Dispatcher::ACTION_STOP,array());
    }

    //#############################################
}
