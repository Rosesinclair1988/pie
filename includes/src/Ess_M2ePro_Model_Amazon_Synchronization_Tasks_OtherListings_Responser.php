<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Synchronization_Tasks_OtherListings_Responser
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

        $tempNick = Ess_M2ePro_Model_Amazon_Synchronization_Tasks_OtherListings::LOCK_ITEM_PREFIX;
        $tempNick .= '_'.$this->params['account_id'].'_'.$this->params['marketplace_id'];

        $lockItem->setNick($tempNick);
        $lockItem->remove();

        $this->getAccount()->deleteObjectLocks(NULL,$hash);
        $this->getAccount()->deleteObjectLocks('synchronization',$hash);
        $this->getAccount()->deleteObjectLocks('synchronization_amazon',$hash);
        $this->getAccount()->deleteObjectLocks(
            Ess_M2ePro_Model_Amazon_Synchronization_Tasks_OtherListings::LOCK_ITEM_PREFIX,
            $hash
        );

        $this->getMarketplace()->deleteObjectLocks(NULL,$hash);
        $this->getMarketplace()->deleteObjectLocks('synchronization',$hash);
        $this->getMarketplace()->deleteObjectLocks('synchronization_amazon',$hash);
        $this->getMarketplace()->deleteObjectLocks(
            Ess_M2ePro_Model_Amazon_Synchronization_Tasks_OtherListings::LOCK_ITEM_PREFIX,
            $hash
        );

        $fail && $this->getSynchLogModel()->addMessage(Mage::helper('M2ePro')->__($message),
                                                       Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                                                       Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH);
    }

    public function processSucceededResponseData($receivedItems)
    {
        Mage::helper('M2ePro/Exception')->setFatalErrorHandler();

        // Prepare extension items
        //----------------------
        /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
        $collection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Product');
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns(array('second_table.sku'));

        $listingTable = Mage::getResourceModel('M2ePro/Listing')->getMainTable();
        $generalTemplateTable = Mage::getResourceModel('M2ePro/Template_General')->getMainTable();

        $collection->getSelect()->join(array('l' => $listingTable), 'main_table.listing_id = l.id', array());
        $collection->getSelect()->join(array('gt' => $generalTemplateTable), 'l.template_general_id = gt.id', array());
        $collection->getSelect()->where('gt.marketplace_id = ?',(int)$this->getMarketplace()->getId());
        $collection->getSelect()->where('gt.account_id = ?',(int)$this->getAccount()->getId());

        $tempItems = $collection->toArray();

        $extensionListingsProducts = array();
        for ($i=0;$i<$tempItems['totalRecords'];$i++) {
            if (empty($tempItems['items'][$i]['sku'])) {
                continue;
            }
            $extensionListingsProducts[] = $tempItems['items'][$i]['sku'];
        }
        //----------------------

        // Prepare received items
        //----------------------
        $tempItems = array();
        $tempCount = count($receivedItems);
        for ($i=0;$i<$tempCount;$i++) {
            if (empty($receivedItems[$i]['identifiers']['sku'])) {
                continue;
            }
            if (in_array($receivedItems[$i]['identifiers']['sku'],$extensionListingsProducts)) {
                continue;
            }
            $tempItems[$receivedItems[$i]['identifiers']['sku']] = $receivedItems[$i];
        }
        $receivedItems = $tempItems;
        //----------------------

        // Prepare existing items
        //----------------------
        /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
        $collection = Mage::helper('M2ePro/Component_Amazon')->getCollection('Listing_Other');
        $collection->addFieldToFilter('account_id',(int)$this->params['account_id']);
        $collection->addFieldToFilter('marketplace_id',(int)$this->params['marketplace_id']);
        //$tempColumns = array('item_id','online_qty','online_price','sku');
        //$collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns($tempColumns);
        $tempItems = $collection->toArray();

        $existingListings = array();
        for ($i=0;$i<$tempItems['totalRecords'];$i++) {
            if (empty($tempItems['items'][$i]['sku'])) {
                continue;
            }
            $existingListings[$tempItems['items'][$i]['sku']] = $tempItems['items'][$i];
        }
        //----------------------

        try {

            $this->updateReceivedOtherListings($receivedItems,$existingListings);
            $this->updateNotReceivedOtherListings($receivedItems,$existingListings);
            $this->createNotExistOtherListings($receivedItems,$existingListings);

        } catch (Exception $exception) {

            Mage::helper('M2ePro/Exception')->process($exception,true);

            $this->getSynchLogModel()->addMessage(Mage::helper('M2ePro')->__($exception->getMessage()),
                                                  Ess_M2ePro_Model_Log_Abstract::TYPE_ERROR,
                                                  Ess_M2ePro_Model_Log_Abstract::PRIORITY_HIGH);
        }
    }

    // ########################################

    protected function updateReceivedOtherListings(&$receivedItems, &$existingItems)
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
                'title' => (string)$receivedItem['title'],
                'description' => (string)$receivedItem['description'],
                'notice' => (string)$receivedItem['notice'],
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
                'title' => (string)$existingItem['title'],
                'description' => (string)$existingItem['description'],
                'notice' => (string)$existingItem['notice'],
                'online_price' => (float)$existingItem['online_price'],
                'online_qty' => (int)$existingItem['online_qty'],
                'start_date' => (string)$existingItem['start_date'],
                'is_afn_channel' => (bool)$existingItem['is_afn_channel'],
                'is_isbn_general_id' => (bool)$existingItem['is_isbn_general_id']
            );

            if ($newData == $existingData) {
                continue;
            }

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

            $listingOtherId = (int)$existingItem['listing_other_id'];
            $listingOtherObj = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Other',$listingOtherId);
            $listingOtherObj->addData($newData)->save();
        }
    }

    protected function updateNotReceivedOtherListings(&$receivedItems, &$existingItems)
    {
        foreach ($existingItems as &$existingItem) {

            if (isset($receivedItems[$existingItem['sku']])) {
                continue;
            }

            if ($existingItem['status'] == Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED) {
                continue;
            }

            $listingOtherId = (int)$existingItem['listing_other_id'];
            $listingOtherObj = Mage::helper('M2ePro/Component_Amazon')->getObject('Listing_Other',$listingOtherId);
            $listingOtherObj->addData(array('status'=>Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED))->save();
        }
    }

    protected function createNotExistOtherListings(&$receivedItems, &$existingItems)
    {
        foreach ($receivedItems as &$receivedItem) {

            if (isset($existingItems[$receivedItem['identifiers']['sku']])) {
                continue;
            }

            $newData = array(
                'account_id' => (int)$this->params['account_id'],
                'marketplace_id' => (int)$this->params['marketplace_id'],
                'product_id' => NULL,

                'item_id' => (string)$receivedItem['identifiers']['item_id'],
                'general_id' => (string)$receivedItem['identifiers']['product_id'],
                'sku' => (string)$receivedItem['identifiers']['sku'],
                'title' => (string)$receivedItem['title'],
                'description' => (string)$receivedItem['description'],
                'notice' => (string)$receivedItem['notice'],
                'online_price' => (float)$receivedItem['price'],
                'online_qty' => (int)$receivedItem['qty'],
                'start_date' => (string)$receivedItem['start_date'],
                'end_date' => NULL,
                'is_afn_channel' => (bool)$receivedItem['channel']['is_afn'],
                'is_isbn_general_id' => (bool)$receivedItem['identifiers']['is_isbn']
            );

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

            $listingOtherModel = Mage::helper('M2ePro/Component_Amazon')->getModel('Listing_Other');
            $listingOtherModel->setData($newData)->save();

            $logModel = Mage::getModel('M2ePro/Listing_Other_Log');
            $logModel->setComponentMode(Ess_M2ePro_Helper_Component_Amazon::NICK);
            $logModel->addProductMessage($listingOtherModel->getId(),
                                         Ess_M2ePro_Model_Log_Abstract::INITIATOR_EXTENSION,
                                         NULL,
                                         Ess_M2ePro_Model_Listing_Other_Log::ACTION_ADD_LISTING,
                                         // Parser hack -> Mage::helper('M2ePro')->__('Item was successfully added');
                                         'Item was successfully added',
                                         Ess_M2ePro_Model_Log_Abstract::TYPE_NOTICE,
                                         Ess_M2ePro_Model_Log_Abstract::PRIORITY_LOW);

            /** @var $mappingModel Ess_M2ePro_Model_Amazon_Listing_Other_Mapping */
            $mappingModel = Mage::getModel('M2ePro/Amazon_Listing_Other_Mapping');
            $mappingModel->initialize($this->getMarketplace(),$this->getAccount());
            $mappingResult = $mappingModel->autoMapOtherListingProduct($listingOtherModel);

            if ($mappingResult) {

                /** @var $movingModel Ess_M2ePro_Model_Amazon_Listing_Other_Moving */
                $movingModel = Mage::getModel('M2ePro/Amazon_Listing_Other_Moving');
                $movingModel->initialize($this->getMarketplace(),$this->getAccount());
                $movingModel->autoMoveOtherListingProduct($listingOtherModel);
            }
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
        $logs->setSynchronizationTask(Ess_M2ePro_Model_Synchronization_Log::SYNCH_TASK_OTHER_LISTINGS);

        $this->synchronizationLog = $logs;

        return $this->synchronizationLog;
    }

    // ########################################
}
