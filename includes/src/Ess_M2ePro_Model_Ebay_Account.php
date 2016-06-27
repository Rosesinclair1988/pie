<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Ebay_Account extends Ess_M2ePro_Model_Component_Child_Ebay_Abstract
{
    const MODE_SANDBOX    = 0;
    const MODE_PRODUCTION = 1;

    const FEEDBACKS_RECEIVE_NO  = 0;
    const FEEDBACKS_RECEIVE_YES = 1;

    const FEEDBACKS_AUTO_RESPONSE_NONE   = 0;
    const FEEDBACKS_AUTO_RESPONSE_CYCLED = 1;
    const FEEDBACKS_AUTO_RESPONSE_RANDOM = 2;

    const FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_NO  = 0;
    const FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_YES = 1;

    const MESSAGES_RECEIVE_NO  = 0;
    const MESSAGES_RECEIVE_YES = 1;

    const OTHER_LISTINGS_SYNCHRONIZATION_NO  = 0;
    const OTHER_LISTINGS_SYNCHRONIZATION_YES = 1;

    const ORDERS_MODE_NO  = 0;
    const ORDERS_MODE_YES = 1;

    const MAGENTO_ORDERS_LISTINGS_MODE_NO  = 0;
    const MAGENTO_ORDERS_LISTINGS_MODE_YES = 1;

    const MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT = 0;
    const MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM  = 1;

    const MAGENTO_ORDERS_LISTINGS_OTHER_MODE_NO  = 0;
    const MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES = 1;

    const MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE = 0;
    const MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT = 1;

    const MAGENTO_ORDERS_RULES_CHECKOUT_MODE_IGNORE    = 0;
    const MAGENTO_ORDERS_RULES_CHECKOUT_MODE_COMPLETED = 1;

    const MAGENTO_ORDERS_RULES_PAYMENT_MODE_IGNORE    = 0;
    const MAGENTO_ORDERS_RULES_PAYMENT_MODE_COMPLETED = 1;

    const MAGENTO_ORDERS_RULES_TRANSACTION_MODE_IGNORE = 0;
    const MAGENTO_ORDERS_RULES_TRANSACTION_MODE_CANCEL = 1;

    const MAGENTO_ORDERS_TAX_MODE_NONE     = 0;
    const MAGENTO_ORDERS_TAX_MODE_CHANNEL  = 1;
    const MAGENTO_ORDERS_TAX_MODE_MAGENTO  = 2;
    const MAGENTO_ORDERS_TAX_MODE_MIXED = 3;

    const MAGENTO_ORDERS_CUSTOMER_MODE_GUEST      = 0;
    const MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED = 1;
    const MAGENTO_ORDERS_CUSTOMER_MODE_NEW        = 2;

    const MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_NO  = 0;
    const MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_YES = 1;

    const MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT = 0;
    const MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM  = 1;

    const MAGENTO_ORDERS_STATUS_MAPPING_NEW     = 'pending';
    const MAGENTO_ORDERS_STATUS_MAPPING_PAID    = 'processing';
    const MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED = 'complete';

    const MAGENTO_ORDERS_INVOICE_MODE_NO  = 0;
    const MAGENTO_ORDERS_INVOICE_MODE_YES = 1;

    const MAGENTO_ORDERS_SHIPMENT_MODE_NO  = 0;
    const MAGENTO_ORDERS_SHIPMENT_MODE_YES = 1;

    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Ebay_Account');
    }

    // ########################################

    public function deleteInstance()
    {
        if ($this->isLocked()) {
            return false;
        }

        $storeCategoriesTable = Mage::getSingleton('core/resource')->getTableName('m2epro_ebay_account_store_category');
        Mage::getSingleton('core/resource')->getConnection('core_write')
            ->delete($storeCategoriesTable,array('account_id = ?'=>$this->getId()));

        $messages = $this->getMessages(true);
        foreach ($messages as $message) {
            $message->deleteInstance();
        }

        $feedbacks = $this->getFeedbacks(true);
        foreach ($feedbacks as $feedback) {
            $feedback->deleteInstance();
        }

        $feedbackTemplates = $this->getFeedbackTemplates(true);
        foreach ($feedbackTemplates as $feedbackTemplate) {
            $feedbackTemplate->deleteInstance();
        }

        $this->delete();

        return true;
    }

    // ########################################

    public function getMessages($asObjects = false, array $filters = array())
    {
        return $this->getRelatedSimpleItems('Ebay_Message','account_id',$asObjects,$filters);
    }

    public function getFeedbacks($asObjects = false, array $filters = array())
    {
        return $this->getRelatedSimpleItems('Ebay_Feedback','account_id',$asObjects,$filters);
    }

    public function getFeedbackTemplates($asObjects = false, array $filters = array())
    {
        return $this->getRelatedSimpleItems('Ebay_Feedback_Template','account_id',$asObjects,$filters);
    }

    // ########################################

    public function hasFeedbackTemplate()
    {
        return (bool)Mage::getModel('M2ePro/Ebay_Feedback_Template')->getCollection()
                               ->addFieldToFilter('account_id', $this->getId())
                               ->getSize();
    }

    // ########################################

    public function getMode()
    {
        return (int)$this->getData('mode');
    }

    public function getServerHash()
    {
        return $this->getData('server_hash');
    }

    public function isModeProduction()
    {
        return $this->getMode() == self::MODE_PRODUCTION;
    }

    public function isModeSandbox()
    {
        return $this->getMode() == self::MODE_SANDBOX;
    }

    //-----------------------------------------

    public function getFeedbacksReceive()
    {
        return (int)$this->getData('feedbacks_receive');
    }

    public function isFeedbacksReceive()
    {
        return $this->getFeedbacksReceive() == self::FEEDBACKS_RECEIVE_YES;
    }

    //-----------------------------------------

    public function getFeedbacksAutoResponse()
    {
        return (int)$this->getData('feedbacks_auto_response');
    }

    public function isFeedbacksAutoResponseDisabled()
    {
        return $this->getFeedbacksAutoResponse() == self::FEEDBACKS_AUTO_RESPONSE_NONE;
    }

    public function isFeedbacksAutoResponseCycled()
    {
        return $this->getFeedbacksAutoResponse() == self::FEEDBACKS_AUTO_RESPONSE_CYCLED;
    }

    public function isFeedbacksAutoResponseRandom()
    {
        return $this->getFeedbacksAutoResponse() == self::FEEDBACKS_AUTO_RESPONSE_RANDOM;
    }

    //-----------------------------------------

    public function getFeedbacksAutoResponseOnlyPositive()
    {
        return (int)$this->getData('feedbacks_auto_response_only_positive');
    }

    public function isFeedbacksAutoResponseOnlyPositive()
    {
        return $this->getFeedbacksAutoResponseOnlyPositive() == self::FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_YES;
    }

    //-----------------------------------------

    public function getMessagesReceive()
    {
        return (int)$this->getData('messages_receive');
    }

    public function isMessagesReceive()
    {
        return $this->getMessagesReceive() == self::MESSAGES_RECEIVE_YES;
    }

    //-----------------------------------------

    public function getOtherListingsSynchronization()
    {
        return (int)$this->getData('other_listings_synchronization');
    }

    public function isOtherListingsSynchronizationEnabled()
    {
        return $this->getOtherListingsSynchronization() == self::OTHER_LISTINGS_SYNCHRONIZATION_YES;
    }

    //-----------------------------------------

    public function getOrdersMode()
    {
        return (int)$this->getData('orders_mode');
    }

    public function isOrdersModeEnabled()
    {
        return $this->getOrdersMode() == self::ORDERS_MODE_YES;
    }

    // ########################################

    public function isMagentoOrdersListingsModeEnabled()
    {
        $setting = $this->getSetting('magento_orders_settings', array('listing', 'mode'),
                                     self::MAGENTO_ORDERS_LISTINGS_MODE_YES);

        return $setting === self::MAGENTO_ORDERS_LISTINGS_MODE_YES;
    }

    public function isMagentoOrdersListingsStoreCustom()
    {
        $setting = $this->getSetting('magento_orders_settings', array('listing', 'store_mode'),
                                     self::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT);

        return $setting === self::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM;
    }

    public function getMagentoOrdersListingsStoreId()
    {
        $setting = $this->getSetting('magento_orders_settings', array('listing', 'store_id'), 0);

        return (int)$setting;
    }

    //-----------------------------------------

    public function isMagentoOrdersListingsOtherModeEnabled()
    {
        $setting = $this->getSetting('magento_orders_settings', array('listing_other', 'mode'),
                                     self::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES);

        return $setting === self::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES;
    }

    public function getMagentoOrdersListingsOtherStoreId()
    {
        $setting = $this->getSetting('magento_orders_settings', array('listing_other', 'store_id'), 0);

        return (int)$setting;
    }

    public function isMagentoOrdersListingsOtherProductImportEnabled()
    {
        $setting = $this->getSetting('magento_orders_settings', array('listing_other', 'product_mode'),
                                     self::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT);

        return $setting === self::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT;
    }

    //-----------------------------------------

    public function isMagentoOrdersRulesCheckoutCompleted()
    {
        $setting = $this->getSetting('magento_orders_settings', array('rules', 'checkout_mode'),
                                     self::MAGENTO_ORDERS_RULES_CHECKOUT_MODE_COMPLETED);

        return $setting === self::MAGENTO_ORDERS_RULES_CHECKOUT_MODE_COMPLETED;
    }

    public function isMagentoOrdersRulesPaymentCompleted()
    {
        $setting = $this->getSetting('magento_orders_settings', array('rules', 'payment_mode'),
                                     self::MAGENTO_ORDERS_RULES_PAYMENT_MODE_IGNORE);

        return $setting === self::MAGENTO_ORDERS_RULES_PAYMENT_MODE_COMPLETED;
    }

    public function isMagentoOrdersRulesTransactionCancel()
    {
        $setting = $this->getSetting('magento_orders_settings', array('rules', 'transaction_mode'),
                                     self::MAGENTO_ORDERS_RULES_TRANSACTION_MODE_CANCEL);

        return $setting === self::MAGENTO_ORDERS_RULES_TRANSACTION_MODE_CANCEL;
    }

    //-----------------------------------------

    public function isMagentoOrdersTaxModeNone()
    {
        $setting = $this->getSetting('magento_orders_settings', array('tax', 'mode'),
                                     self::MAGENTO_ORDERS_TAX_MODE_MIXED);

        return $setting === self::MAGENTO_ORDERS_TAX_MODE_NONE;
    }

    public function isMagentoOrdersTaxModeChannel()
    {
        $setting = $this->getSetting('magento_orders_settings', array('tax', 'mode'),
                                     self::MAGENTO_ORDERS_TAX_MODE_MIXED);

        return $setting === self::MAGENTO_ORDERS_TAX_MODE_CHANNEL;
    }

    public function isMagentoOrdersTaxModeMagento()
    {
        $setting = $this->getSetting('magento_orders_settings', array('tax', 'mode'),
                                     self::MAGENTO_ORDERS_TAX_MODE_MIXED);

        return $setting === self::MAGENTO_ORDERS_TAX_MODE_MAGENTO;
    }

    public function isMagentoOrdersTaxModeMixed()
    {
        $setting = $this->getSetting('magento_orders_settings', array('tax', 'mode'),
                                     self::MAGENTO_ORDERS_TAX_MODE_MIXED);

        return $setting === self::MAGENTO_ORDERS_TAX_MODE_MIXED;
    }

    //-----------------------------------------

    public function isMagentoOrdersCustomerGuest()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'mode'),
                                     self::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST);

        return $setting === self::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST;
    }

    public function isMagentoOrdersCustomerPredefined()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'mode'),
                                     self::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST);

        return $setting === self::MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED;
    }

    public function isMagentoOrdersCustomerNew()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'mode'),
                                     self::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST);

        return $setting === self::MAGENTO_ORDERS_CUSTOMER_MODE_NEW;
    }

    public function getMagentoOrdersCustomerId()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'id'));

        return (int)$setting;
    }

    public function isMagentoOrdersCustomerNewSubscribed()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'subscription_mode'),
                                     self::MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_NO);

        return $setting === self::MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_YES;
    }

    public function isMagentoOrdersCustomerNewNotifyWhenCreated()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'notifications', 'customer_created'));

        return (bool)$setting;
    }

    public function isMagentoOrdersCustomerNewNotifyWhenOrderCreated()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'notifications', 'order_created'));

        return (bool)$setting;
    }

    public function isMagentoOrdersCustomerNewNotifyWhenInvoiceCreated()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'notifications', 'invoice_created'));

        return (bool)$setting;
    }

    public function getMagentoOrdersCustomerNewWebsiteId()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'website_id'));

        return (int)$setting;
    }

    public function getMagentoOrdersCustomerNewGroupId()
    {
        $setting = $this->getSetting('magento_orders_settings', array('customer', 'group_id'));

        return (int)$setting;
    }

    //-----------------------------------------

    public function isMagentoOrdersStatusMappingDefault()
    {
        $setting = $this->getSetting('magento_orders_settings', array('status_mapping', 'mode'),
                                     self::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT);

        return $setting === self::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT;
    }

    //-----------------------------------------

    public function getMagentoOrdersStatusNew()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return self::MAGENTO_ORDERS_STATUS_MAPPING_NEW;
        }

        return $this->getSetting('magento_orders_settings', array('status_mapping', 'new'));
    }

    public function getMagentoOrdersStatusPaid()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return self::MAGENTO_ORDERS_STATUS_MAPPING_PAID;
        }

        return $this->getSetting('magento_orders_settings', array('status_mapping', 'paid'));
    }

    public function getMagentoOrdersStatusShipped()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return self::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED;
        }

        return $this->getSetting('magento_orders_settings', array('status_mapping', 'shipped'));
    }

    //-----------------------------------------

    public function isMagentoOrdersInvoiceEnabled()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return true;
        }

        return $this->getSetting('magento_orders_settings', 'invoice_mode') === self::MAGENTO_ORDERS_INVOICE_MODE_YES;
    }

    //-----------------------------------------

    public function isMagentoOrdersShipmentEnabled()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return true;
        }

        return $this->getSetting('magento_orders_settings', 'shipment_mode') === self::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
    }

    // ########################################

    public function getTokenSession()
    {
        return $this->getData('token_session');
    }

    public function getTokenExpiredDate()
    {
        return $this->getData('token_expired_date');
    }

    //-----------------------------------------

    public function getFeedbacksLastUsedId()
    {
        return $this->getData('feedbacks_last_used_id');
    }

    //-----------------------------------------

    public function getEbayStoreTitle()
    {
        return $this->getData('ebay_store_title');
    }

    public function getEbayStoreUrl()
    {
        return $this->getData('ebay_store_url');
    }

    public function getEbayStoreSubscriptionLevel()
    {
        return $this->getData('ebay_store_subscription_level');
    }

    public function getEbayStoreDescription()
    {
        return $this->getData('ebay_store_description');
    }

    public function getEbayStoreCategories()
    {
        $tableAccountStoreCategories = Mage::getSingleton('core/resource')
            ->getTableName('m2epro_ebay_account_store_category');

        /** @var $connRead Varien_Db_Adapter_Pdo_Mysql */
        $connRead = Mage::getSingleton('core/resource')->getConnection('core_read');

        $dbSelect = $connRead->select()
                             ->from($tableAccountStoreCategories,'*')
                             ->where('`account_id` = ?',(int)$this->getId())
                             ->order(array('sorder ASC'));

        return $connRead->fetchAll($dbSelect);
    }

    public function buildEbayStoreCategoriesTreeRec($data, $rootId)
    {
        $children = array();

        foreach ($data as $node) {
            if ($node['parent_id'] == $rootId) {
                $children[] = array(
                    'id' => $node['category_id'],
                    'text' => $node['title'],
                    'allowDrop' => false,
                    'allowDrag' => false,
                    'children' => array()
                );
            }
        }

        foreach ($children as &$child) {
            $child['children'] = $this->buildEbayStoreCategoriesTreeRec($data,$child['id']);
        }

        return $children;
    }

    public function buildEbayStoreCategoriesTree()
    {
        return $this->buildEbayStoreCategoriesTreeRec($this->getEbayStoreCategories(), 0);
    }

    //-----------------------------------------

    public function updateEbayStoreInfo()
    {
        // Get eBay store data
        //----------------------------
        $data = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                        ->processVirtualAbstract('account','get','store',
                                                 array(),NULL,
                                                 NULL,$this->getId(),NULL);
        //----------------------------

        // Save eBay store information
        //----------------------------
        if (isset($data['data'])) {
            $dataForUpdate = array();
            foreach ($data['data'] as $key=>$value) {
                $dataForUpdate['ebay_store_'.$key] = $value;
            }
            $this->addData($dataForUpdate)->save();
        }
        //----------------------------

        // Save eBay store categories
        //----------------------------
        if (isset($data['categories'])) {
            $tableAccountStoreCategories = Mage::getSingleton('core/resource')
                ->getTableName('m2epro_ebay_account_store_category');

            Mage::getSingleton('core/resource')->getConnection('core_write')
                    ->delete($tableAccountStoreCategories,array('account_id = ?'=>$this->getId()));

            /** @var $connWrite Varien_Db_Adapter_Pdo_Mysql */
            $connWrite = Mage::getSingleton('core/resource')->getConnection('core_write');

            foreach ($data['categories'] as &$item) {
                $item['account_id'] = $this->getId();
                $connWrite->insertOnDuplicate($tableAccountStoreCategories, $item);
            }
        }
        //----------------------------
    }

    // ########################################
}
