<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Adminhtml_Ebay_AccountController extends Ess_M2ePro_Controller_Adminhtml_MainController
{
    //#############################################

    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('m2epro/configuration')
             ->_title(Mage::helper('M2ePro')->__('M2E Pro'))
             ->_title(Mage::helper('M2ePro')->__('Configuration'))
             ->_title(Mage::helper('M2ePro')->__('eBay Accounts'));

        $this->getLayout()->getBlock('head')
             ->setCanLoadExtJs(true)
             ->addJs('M2ePro/Ebay/AccountHandler.js');

        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('m2epro/configuration/account');
    }

    //#############################################

    public function indexAction()
    {
        return $this->_redirect('*/adminhtml_account/index');
    }

    //#############################################

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = Mage::helper('M2ePro/Component_Ebay')->getModel('Account')->load($id);

        if (!$model->getId() && $id) {
            $this->_getSession()->addError(Mage::helper('M2ePro')->__('Account does not exist.'));
            return $this->_redirect('*/adminhtml_account/index');
        }

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $model);

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('M2ePro/adminhtml_ebay_account_edit'))
             ->_addLeft($this->getLayout()->createBlock('M2ePro/adminhtml_ebay_account_edit_tabs'))
             ->renderLayout();
    }

    //#############################################

    public function beforeGetTokenAction()
    {
        // Get and save form data
        //-------------------------------
        $accountId = $this->getRequest()->getParam('id', 0);
        $accountTitle = strip_tags($this->getRequest()->getParam('title'));
        $accountMode = (int)$this->getRequest()->getParam('mode', Ess_M2ePro_Model_Ebay_Account::MODE_SANDBOX);
        //-------------------------------

        // Get and save session id
        //-------------------------------
        $mode = $accountMode == Ess_M2ePro_Model_Ebay_Account::MODE_PRODUCTION ?
                                Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::MODE_PRODUCTION :
                                Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::MODE_SANDBOX;

        try {
            $response = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                            ->processVirtualAbstract('account','get','authUrl',
                                                      array('back_url'=>$this->getUrl('*/*/afterGetToken')),NULL,
                                                      NULL,NULL,$mode);
        } catch (Exception $exception) {

            Mage::helper('M2ePro/Exception')->process($exception,true);

            $error  = Mage::helper('M2ePro')->__('The eBay token obtaining is currently unavailable. ');
            $error .= Mage::helper('M2ePro')->__('Please try again later.');
            $this->_getSession()->addError($error);
            return $this->indexAction();
        }

        Mage::helper('M2ePro')->setSessionValue('get_token_account_id', $accountId);
        Mage::helper('M2ePro')->setSessionValue('get_token_account_title', $accountTitle);
        Mage::helper('M2ePro')->setSessionValue('get_token_account_mode', $accountMode);
        Mage::helper('M2ePro')->setSessionValue('get_token_session_id', $response['session_id']);

        $this->_redirectUrl($response['url']);
        //-------------------------------
    }

    public function afterGetTokenAction()
    {
        // Get eBay session id
        //-------------------------------
        $sessionId = Mage::helper('M2ePro')->getSessionValue('get_token_session_id', true);
        is_null($sessionId) && $this->_redirect('*/*/index');
        //-------------------------------

        // Get account form data
        //-------------------------------
        Mage::helper('M2ePro')->setSessionValue('get_token_account_token_session', $sessionId);
        //-------------------------------

        // Goto account add or edit page
        //-------------------------------
        $accountId = (int)Mage::helper('M2ePro')->getSessionValue('get_token_account_id', true);

        if ($accountId == 0) {
            $this->_redirect('*/*/new');
        } else {
            $this->_redirect('*/*/edit', array('id' => $accountId));
        }
        //-------------------------------
    }

    //#############################################

    public function checkCustomerIdAction()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        exit(json_encode(array(
            'ok' => (bool)Mage::getModel('customer/customer')->load($customerId)->getId()
        )));
    }

    //#############################################

    public function saveAction()
    {
        if (!$post = $this->getRequest()->getPost()) {
            return $this->indexAction();
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        //--------------------
        $data = array();
        //--------------------

        // tab: general
        //--------------------
        $keys = array(
            'title',
            'mode',
            'token_session',
            'other_listings_synchronization',
            'messages_receive'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        //--------------------

        // tab: orders
        //--------------------
        $keys = array(
            'orders_mode',
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        $data['magento_orders_settings'] = array();

        // m2e orders settings
        //--------------------
        $tempKey = 'listing';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'store_mode',
            'store_id'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        //--------------------

        // 3rd party orders settings
        //--------------------
        $tempKey = 'listing_other';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'product_mode',
            'store_id'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        //--------------------

        // rules settings
        //--------------------
        $tempKey = 'rules';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'checkout_mode',
            'payment_mode',
            'transaction_mode'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        //--------------------

        // tax settings
        //--------------------
        $tempKey = 'tax';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        //--------------------

        // customer settings
        //--------------------
        $tempKey = 'customer';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'id',
            'website_id',
            'group_id',
            'subscription_mode'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        $notificationsKeys = array(
            'customer_created',
            'order_created',
            'invoice_created'
        );
        $tempSettings = !empty($tempSettings['notifications']) ? $tempSettings['notifications'] : array();
        foreach ($notificationsKeys as $key) {
            if (in_array($key, $tempSettings)) {
                $data['magento_orders_settings'][$tempKey]['notifications'][$key] = true;
            }
        }
        //--------------------

        // status mapping settings
        //--------------------
        $tempKey = 'status_mapping';
        $tempSettings = !empty($post['magento_orders_settings'][$tempKey])
            ? $post['magento_orders_settings'][$tempKey] : array();

        $keys = array(
            'mode',
            'new',
            'paid',
            'shipped'
        );
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }
        //--------------------

        // invoice/shipment settings
        //--------------------
        $temp = Ess_M2ePro_Model_Ebay_Account::MAGENTO_ORDERS_INVOICE_MODE_YES;
        $data['magento_orders_settings']['invoice_mode'] = $temp;
        $temp = Ess_M2ePro_Model_Ebay_Account::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
        $data['magento_orders_settings']['shipment_mode'] = $temp;

        $temp = Ess_M2ePro_Model_Ebay_Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM;
        if (!empty($data['magento_orders_settings']['status_mapping']['mode']) &&
            $data['magento_orders_settings']['status_mapping']['mode'] == $temp) {

            if (!isset($post['magento_orders_settings']['invoice_mode'])) {
                $temp = Ess_M2ePro_Model_Ebay_Account::MAGENTO_ORDERS_INVOICE_MODE_NO;
                $data['magento_orders_settings']['invoice_mode'] = $temp;
            }
            if (!isset($post['magento_orders_settings']['shipment_mode'])) {
                $temp = Ess_M2ePro_Model_Ebay_Account::MAGENTO_ORDERS_SHIPMENT_MODE_NO;
                $data['magento_orders_settings']['shipment_mode'] = $temp;
            }
        }
        //--------------------

        //--------------------
        $data['magento_orders_settings'] = json_encode($data['magento_orders_settings']);
        //--------------------

        // tab: feedbacks
        //--------------------
        $keys = array(
            'feedbacks_receive',
            'feedbacks_auto_response',
            'feedbacks_auto_response_only_positive'
        );
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }
        //--------------------

        // Add or update server
        //--------------------
        $requestMode = $data['mode'] == Ess_M2ePro_Model_Ebay_Account::MODE_PRODUCTION ?
                       Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::MODE_PRODUCTION :
                       Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::MODE_SANDBOX;

        if ((bool)$id) {

            $requestTempParams = array(
                'title' => $data['title'],
                'mode' => $requestMode,
                'token_session' => $data['token_session']
            );
            $response = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                        ->processVirtualAbstract('account','update','entity',
                                                  $requestTempParams,NULL,
                                                  NULL,$id,NULL);
        } else {

            $requestTempParams = array(
                'title' => $data['title'],
                'mode' => $requestMode,
                'token_session' => $data['token_session']
            );
            $response = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                        ->processVirtualAbstract('account','add','entity',
                                                  $requestTempParams,NULL,
                                                  NULL,NULL,$requestMode);
        }

        if (!isset($response['token_expired_date'])) {
            throw new Exception('Account is not added or updated. Try again later.');
        }

        isset($response['hash']) && $data['server_hash'] = $response['hash'];

        $data['ebay_info'] = json_encode($response['info']);
        $data['token_expired_date'] = $response['token_expired_date'];
        //--------------------

        // Change token
        //--------------------
        $isChangeTokenSession = false;
        if ((bool)$id) {
            $oldTokenSession = Mage::getModel('M2ePro/Ebay_Account')->loadInstance($id)->getTokenSession();
            $newTokenSession = $data['token_session'];
            if ($newTokenSession != $oldTokenSession) {
                $isChangeTokenSession = true;
            }
        } else {
            $isChangeTokenSession = true;
        }
        //--------------------

        // Add or update model
        //--------------------
        $model = Mage::helper('M2ePro/Component_Ebay')->getModel('Account');
        is_null($id) && $model->setData($data);
        !is_null($id) && $model->load($id)->addData($data);
        $id = $model->save()->getId();
        //--------------------

        // Update eBay store
        //--------------------
        if ($isChangeTokenSession || (int)$this->getRequest()->getParam('update_ebay_store')) {
            $ebayAccount = $model->getChildObject();
            $ebayAccount->updateEbayStoreInfo();
        }
        //--------------------

        Mage::helper('M2ePro/Component_Ebay')->clearAllCache();
        $this->_getSession()->addSuccess(Mage::helper('M2ePro')->__('Account was successfully saved'));

        //if (Mage::getSingleton('M2ePro/Wizard')->isInstallationActive() &&
        //    Mage::getSingleton('M2ePro/Wizard')->getStatus() == Ess_M2ePro_Model_Wizard::STATUS_ACCOUNTS_EBAY) {
        //    $this->_redirect('*/*/new');
        //    return;
        //}

        $this->_redirectUrl(Mage::helper('M2ePro')->getBackUrl('list',array(),array('edit'=>array('id'=>$id))));
    }

    public function deleteAction()
    {
        $this->_forward('delete','adminhtml_account');
    }

    //#############################################

    public function feedbackTemplateGridAction()
    {
        $id    = $this->getRequest()->getParam('id');
        $model = Mage::helper('M2ePro/Component_Ebay')->getModel('Account')->load($id);

        if (!$model->getId() && $id) {
            return;
        }

        Mage::helper('M2ePro')->setGlobalValue('temp_data', $model);

        // Response for grid
        //----------------------------
        $response = $this->loadLayout()->getLayout()
                         ->createBlock('M2ePro/adminhtml_ebay_account_edit_tabs_feedback_grid')->toHtml();
        $this->getResponse()->setBody($response);
        //----------------------------
    }

    public function feedbackTemplateCheckAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::helper('M2ePro/Component_Ebay')->getModel('Account')->load($id);

        exit(json_encode(array(
            'ok' => (bool)$model->getChildObject()->hasFeedbackTemplate()
        )));
    }

    public function feedbackTemplateEditAction()
    {
        $id = $this->getRequest()->getParam('id');
        $accountId = $this->getRequest()->getParam('account_id');
        $body = $this->getRequest()->getParam('body');

        $data = array('account_id'=>$accountId,'body'=>$body);

        $model = Mage::getModel('M2ePro/Ebay_Feedback_Template');
        is_null($id) && $model->setData($data);
        !is_null($id) && $model->load($id)->addData($data);
        $model->save();

        exit('ok');
    }

    public function feedbackTemplateDeleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        Mage::getModel('M2ePro/Ebay_Feedback_Template')->loadInstance($id)->deleteInstance();
        exit('ok');
    }

    //#############################################
}
