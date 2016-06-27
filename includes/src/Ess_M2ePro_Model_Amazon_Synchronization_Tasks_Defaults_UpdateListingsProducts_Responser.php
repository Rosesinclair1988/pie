<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Synchronization_Tasks_Defaults_UpdateListingsProducts_Responser
{
    protected $params = array();
    protected $synchronizationLog = NULL;

    /**
     * @var Ess_M2ePro_Model_Marketplace|null
     */
    protected $marketplace = NULL;

    /**
     * @var Ess_M2ePro_Model_Account|null
     */
    protected $account = NULL;

    // ########################################

    public function initialize(array $params = array(),
                               Ess_M2ePro_Model_Marketplace $marketplace = NULL,
                               Ess_M2ePro_Model_Account $account = NULL)
    {
        $this->params = $params;
        $this->marketplace = $marketplace;
        $this->account = $account;
    }

    // ########################################

    public function unsetLocks($hash, $fail = false, $message = NULL)
    {
        /** @var $lockItem Ess_M2ePro_Model_LockItem */
        $lockItem = Mage::getModel('M2ePro/LockItem');

        $tempNick = Ess_M2ePro_Model_Amazon_Synchronization_Tasks_Defaults_UpdateListingsProducts::LOCK_ITEM_PREFIX;
        $tempNick .= '_'.$this->params['account_id'].'_'.$this->params['marketplace_id'];

        $lockItem->setNick($tempNick);
        $lockItem->remove();

        $this->getAccount()->deleteObjectLocks(NULL,$hash);
        $this->getAccount()->deleteObjectLocks('synchronization',$hash);
        $this->getAccount()->deleteObjectLocks('synchronization_amazon',$hash);
        $this->getAccount()->deleteObjectLocks(
            Ess_M2ePro_Model_Amazon_Synchronization_Tasks_Defaults_UpdateListingsProducts::LOCK_ITEM_PREFIX,$hash
        );

        $this->getMarketplace()->deleteObjectLocks(NULL,$hash);
        $this->getMarketplace()->deleteObjectLocks('synchronization',$hash);
        $this->getMarketplace()->deleteObjectLocks('synchronization_amazon',$hash);
        $this->getMarketplace()->deleteObjectLocks(
            Ess_M2ePro_Model_Amazon_Synchronization_Tasks_Defaults_UpdateListingsProducts::LOCK_ITEM_PREFIX,$hash
        );

        $fail && $this->getSynchLogModel()->addMessage(Mage::helper('M2ePro')->__($message),
                                                       Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                                                       Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH);
    }

    public function processSucceededResponseData($receivedItems)
    {
        Mage::helper('M2ePro/Exception')->setFatalErrorHandler();

        // Prepare received items
        //----------------------
        $tempItems = array();
        $tempCount = count($receivedItems);
        for ($i=0;$i<$tempCount;$i++) {
            if (empty($receivedItems[$i]['identifiers']['sku'])) {
                continue;
            }
            $tempItems[$receivedItems[$i]['identifiers']['sku']] = $receivedItems[$i];
        }
        $receivedItems = $tempItems;
        //----------------------

        $this->processExistanceCheck($receivedItems);
        $this->processListingsProducts($receivedItems);
    }

    protected function processExistanceCheck($receivedItems)
    {
        // Prepare existing items
        //----------------------
        $listingTable = Mage::getResourceModel('M2ePro/Listing')->getMainTable();
        $generalTemplateTable = Mage::getResourceModel('M2ePro/Template_General')->getMainTable();

        /** @var $collection Varien_Data_Collection_Db */
        $collection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Product');
        $collection->getSelect()->join(array('l' => $listingTable), 'main_table.listing_id = l.id', array());
        $collection->getSelect()->join(array('gt' => $generalTemplateTable), 'l.template_general_id = gt.id', array());
        $collection->getSelect()->where('gt.marketplace_id = ?',(int)$this->getMarketplace()->getId());
        $collection->getSelect()->where('gt.account_id = ?',(int)$this->getAccount()->getId());
        $collection->getSelect()->where('status = ?',(int)Ess_M2ePro_Model_Listing_Product::STATUS_NOT_LISTED);
        $collection->getSelect()->where('existance_check_status = ?',
            (int)Ess_M2ePro_Model_Amazon_Listing_Product::EXISTANCE_CHECK_STATUS_NONE);

        $tempItems = $collection->toArray();

        if ($tempItems['totalRecords']) {
            try {

                $this->updateExistanceCheckListingsProducts($receivedItems,$tempItems['items']);

            } catch (Exception $exception) {

                Mage::helper('M2ePro/Exception')->process($exception,true);

                $this->getSynchLogModel()->addMessage(Mage::helper('M2ePro')->__($exception->getMessage()),
                    Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH);
            }
        }
    }

    protected function processListingsProducts($receivedItems)
    {
        // Prepare existing items
        //----------------------
        $listingTable = Mage::getResourceModel('M2ePro/Listing')->getMainTable();
        $generalTemplateTable = Mage::getResourceModel('M2ePro/Template_General')->getMainTable();

        /** @var $collection Varien_Data_Collection_Db */
        $collection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Product');
        $collection->getSelect()->join(array('l' => $listingTable), 'main_table.listing_id = l.id', array());
        $collection->getSelect()->join(array('gt' => $generalTemplateTable), 'l.template_general_id = gt.id', array());
        $collection->getSelect()->where('gt.marketplace_id = ?',(int)$this->getMarketplace()->getId());
        $collection->getSelect()->where('gt.account_id = ?',(int)$this->getAccount()->getId());
        $collection->getSelect()->where("`sku` is not null and `sku` <> ''");

        $tempItems = $collection->toArray();

        if (!$tempItems['totalRecords']) {
            return;
        }

        $existingListings = array();
        for ($i=0; $i<$tempItems['totalRecords']; $i++) {
            $existingListings[$tempItems['items'][$i]['sku']] = $tempItems['items'][$i];
        }
        //----------------------

        try {

            $this->updateReceivedListingsProducts($receivedItems,$existingListings);
            $this->updateNotReceivedListingsProducts($receivedItems,$existingListings);

        } catch (Exception $exception) {

            Mage::helper('M2ePro/Exception')->process($exception,true);

            $this->getSynchLogModel()->addMessage(Mage::helper('M2ePro')->__($exception->getMessage()),
                Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH);
        }
    }

    // ########################################

    protected function updateExistanceCheckListingsProducts($receivedItems, $existingItems)
    {
        foreach ($existingItems as $existingItem) {

            $listingProductObj = Mage::helper('M2ePro/Component_Amazon')->getObject(
                'Listing_Product',(int)$existingItem['listing_product_id']
            );

            $sku = $listingProductObj->getChildObject()->getAddingSku();

            if (!isset($receivedItems[$sku])) {
                $newData = array(
                    'existance_check_status'
                            => Ess_M2ePro_Model_Amazon_Listing_Product::EXISTANCE_CHECK_STATUS_NOT_FOUND,
                );

                $listingProductObj->addData($newData)->save();
            } else {
                $newData = array(
                    'existance_check_status' => Ess_M2ePro_Model_Amazon_Listing_Product::EXISTANCE_CHECK_STATUS_FOUND,
                    'sku' => $sku
                );
                $listingProductObj->addData($newData)->save();

                // save to listing log
                $logModel = Mage::getModel('M2ePro/Listing_Log');
                $logModel->setComponentMode(Ess_M2ePro_Helper_Component_Amazon::NICK);

                $logModel->addProductMessage($existingItem['listing_id'] ,
                    $existingItem['product_id'],
                    Ess_M2ePro_Model_Log_Abstract::INITIATOR_EXTENSION,
                    NULL,
                    Ess_M2ePro_Model_Listing_Log::ACTION_UNKNOWN,
                    Mage::helper('M2ePro')->__('The product was found in your Amazon inventory and linked by SKU.'),
                    Ess_M2ePro_Model_Log_Abstract::TYPE_SUCCESS,
                    Ess_M2ePro_Model_Log_Abstract::PRIORITY_MEDIUM);
            }
        }
    }

    protected function updateReceivedListingsProducts(&$receivedItems, &$existingItems)
    {
        foreach ($existingItems as &$existingItem) {

            if (!isset($receivedItems[$existingItem['sku']])) {
                continue;
            }

            $receivedItem = $receivedItems[$existingItem['sku']];

            $newData = array(
                'item_id' => (string)$receivedItem['identifiers']['item_id'],
                'general_id' => (string)$receivedItem['identifiers']['product_id'],
                'sku' => (string)$receivedItem['identifiers']['sku'],
                'online_price' => (float)$receivedItem['price'],
                'online_qty' => (int)$receivedItem['qty'],
                'start_date' => (string)$receivedItem['start_date'],
                'is_afn_channel' => (bool)$receivedItem['channel']['is_afn'],
                'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn']
            );

            $existingData = array(
                'item_id' => (string)$existingItem['item_id'],
                'general_id' => (string)$existingItem['general_id'],
                'sku' => (string)$existingItem['sku'],
                'online_price' => (float)$existingItem['online_price'],
                'online_qty' => (int)$existingItem['online_qty'],
                'start_date' => (string)$existingItem['start_date'],
                'is_afn_channel' => (bool)$existingItem['is_afn_channel'],
                'is_isbn_general_id' => (bool)$existingItem['is_isbn_general_id']
            );

            if ($newData == $existingData) {
                continue;
            }

            $newData['status_changer'] = Ess_M2ePro_Model_Listing_Product::STATUS_CHANGER_COMPONENT;

            if ((bool)$newData['is_afn_channel']) {
                $newData['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_UNKNOWN;
            } else {
                if ((int)$newData['online_qty'] > 0) {
                    $newData['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_LISTED;
                    $newData['end_date'] = NULL;
                } else {
                    $newData['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED;
                    $newData['end_date'] = Mage::helper('M2ePro')->getCurrentGmtDate();
                }
            }

            if (isset($newData['status']) && $newData['status'] != $existingItem['status']) {

                Mage::getModel('M2ePro/ProductChange')
                    ->updateAttribute( $existingItem['product_id'],
                    'amazon_listing_product_status',
                    'listing_product_'.$existingItem['listing_product_id'].'_status_'
                        .$existingItem['status'],
                    'listing_product_'.$existingItem['listing_product_id'].'_status_'
                        .$newData['status'] ,
                    Ess_M2ePro_Model_ProductChange::CREATOR_TYPE_SYNCHRONIZATION );
            }

            $listingProductId = (int)$existingItem['listing_product_id'];
            $listingProductObj = Mage::helper('M2ePro/Component_Amazon')->getObject(
                'Listing_Product',$listingProductId
            );
            $listingProductObj->addData($newData)->save();
        }
    }

    protected function updateNotReceivedListingsProducts(&$receivedItems, &$existingItems)
    {
        foreach ($existingItems as &$existingItem) {

            if (isset($receivedItems[$existingItem['sku']])) {
                continue;
            }

            if ($existingItem['status'] == Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED) {
                continue;
            }

            Mage::getModel('M2ePro/ProductChange')
                ->updateAttribute( $existingItem['product_id'],
                'amazon_listing_product_status',
                'listing_product_'.$existingItem['listing_product_id'].'_status_'
                    .$existingItem['status'],
                'listing_product_'.$existingItem['listing_product_id'].'_status_'
                    .Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED ,
                Ess_M2ePro_Model_ProductChange::CREATOR_TYPE_SYNCHRONIZATION );

            $listingProductId = (int)$existingItem['listing_product_id'];
            $listingProductObj = Mage::helper('M2ePro/Component_Amazon')->getObject(
                'Listing_Product',$listingProductId
            );
            $listingProductObj->addData(array('status'=>Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED))->save();
        }
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Account
     */
    protected function getAccount()
    {
        return $this->account;
    }

    /**
     * @return Ess_M2ePro_Model_Marketplace
     */
    protected function getMarketplace()
    {
        return $this->marketplace;
    }

    //-----------------------------------------

    protected function getSynchLogModel()
    {
        if (!is_null($this->synchronizationLog)) {
            return $this->synchronizationLog;
        }

        /** @var $runs Ess_M2ePro_Model_Synchronization_Run */
        $runs = Mage::getModel('M2ePro/Synchronization_Run');
        $runs->start(Ess_M2ePro_Model_Synchronization_Run::INITIATOR_UNKNOWN);
        $runsId = $runs->getLastId();
        $runs->stop();

        /** @var $logs Ess_M2ePro_Model_Synchronization_Log */
        $logs = Mage::getModel('M2ePro/Synchronization_Log');
        $logs->setSynchronizationRun($runsId);
        $logs->setComponentMode(Ess_M2ePro_Helper_Component_Amazon::NICK);
        $logs->setInitiator(Ess_M2ePro_Model_Synchronization_Run::INITIATOR_UNKNOWN);
        $logs->setSynchronizationTask(Ess_M2ePro_Model_Synchronization_Log::SYNCH_TASK_DEFAULTS);

        $this->synchronizationLog = $logs;

        return $this->synchronizationLog;
    }

    // ########################################
}
