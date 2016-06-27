<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
*/

class Ess_M2ePro_Model_Ebay_Synchronization_Tasks_OtherListings extends Ess_M2ePro_Model_Ebay_Synchronization_Tasks
{
    const PERCENTS_START = 0;
    const PERCENTS_END = 100;
    const PERCENTS_INTERVAL = 100;

    const EBAY_STATUS_ACTIVE = 'Active';
    const EBAY_STATUS_ENDED = 'Ended';
    const EBAY_STATUS_COMPLETED = 'Completed';

    //####################################

    public function process()
    {
        // PREPARE SYNCH
        //---------------------------
        $this->prepareSynch();
        //---------------------------

        // RUN SYNCH
        //---------------------------
        $this->execute();
        //---------------------------

        // CANCEL SYNCH
        //---------------------------
        $this->cancelSynch();
        //---------------------------
    }

    //####################################

    private function prepareSynch()
    {
        $this->_lockItem->activate();
        $this->_logs->setSynchronizationTask(Ess_M2ePro_Model_Synchronization_Log::SYNCH_TASK_OTHER_LISTINGS);

        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) > 1) {
            $componentName = Ess_M2ePro_Helper_Component_Ebay::TITLE.' ';
        } else {
            $componentName = '';
        }

        $this->_profiler->addEol();
        $this->_profiler->addTitle($componentName.'3rd Party Listings Synchronization');
        $this->_profiler->addTitle('--------------------------');
        $this->_profiler->addTimePoint(__CLASS__, 'Total time');
        $this->_profiler->increaseLeftPadding(5);

        $this->_lockItem->setTitle(Mage::helper('M2ePro')->__($componentName.'3rd Party Listings Synchronization'));
        $this->_lockItem->setPercents(self::PERCENTS_START);
        $this->_lockItem->setStatus(
            Mage::helper('M2ePro')->__('Task "3rd Party Listings Synchronization" is started. Please wait...')
        );
    }

    private function cancelSynch()
    {
        $this->_lockItem->setPercents(self::PERCENTS_END);
        $this->_lockItem->setStatus(
            Mage::helper('M2ePro')->__('Task "3rd Party Listings Synchronization" is finished. Please wait...')
        );

        $this->_profiler->decreaseLeftPadding(5);
        $this->_profiler->addEol();
        $this->_profiler->addTitle('--------------------------');
        $this->_profiler->saveTimePoint(__CLASS__);

        $this->_logs->setSynchronizationTask(Ess_M2ePro_Model_Synchronization_Log::SYNCH_TASK_UNKNOWN);
        $this->_lockItem->activate();
    }

    //####################################

    private function execute()
    {
        $this->_profiler->addTimePoint(__METHOD__,'Get and process items from eBay');

        // Get separate all accounts
        //---------------------------
        $accounts = Mage::helper('M2ePro/Component_Ebay')->getModel('Account')->getCollection()
            ->addFieldToFilter(
                'other_listings_synchronization',
                Ess_M2ePro_Model_Ebay_Account::OTHER_LISTINGS_SYNCHRONIZATION_YES
            )
            ->getItems();
        if (count($accounts) <= 0) {
            return;
        }
        //---------------------------

        // Processing each account
        //---------------------------
        $accountIteration = 1;
        $percentsForAccount = self::PERCENTS_INTERVAL / count($accounts);

        foreach ($accounts as $account) {

            $this->processAccount($account, $percentsForAccount);

            $this->_lockItem->setPercents(self::PERCENTS_START + $percentsForAccount*$accountIteration);
            $this->_lockItem->activate();
            $accountIteration++;
        }
        //---------------------------

        $this->_profiler->saveTimePoint(__METHOD__);
    }

    //####################################

    private function processAccount(Ess_M2ePro_Model_Account $account, $percentsForAccount)
    {
        $this->_profiler->addTitle('Starting account "'.$account->getData('title').'"');

        $this->_profiler->addTimePoint(__METHOD__.'get'.$account->getId(),'Get items from eBay');

        // ->__('Task "3rd Party Listings Synchronization" for eBay account: "%acc%" is started. Please wait...')
        $status = 'Task "3rd Party Listings Synchronization" for eBay account: "%acc%" is started. Please wait...';
        $tempString = str_replace('%acc%',$account->getData('title'),Mage::helper('M2ePro')->__($status));
        $this->_lockItem->setStatus($tempString);

        $currentPercent = $this->_lockItem->getPercents();

        $currentPercent = $currentPercent + $percentsForAccount * 0.05;
        $this->_lockItem->setPercents($currentPercent);
        $this->_lockItem->activate();

        // Get since time
        //---------------------------
        $sinceTime = $account->getData('other_listings_last_synchronization');
        //---------------------------

        // Get all items from eBay
        //---------------------------
        if (is_null($sinceTime) || empty($sinceTime)) {

            $tempSinceTime = new DateTime('now', new DateTimeZone('UTC'));
            $tempSinceTime->modify("-118 days");
            $tempSinceTime = strftime("%Y-%m-%d %H:%M:%S", (int)$tempSinceTime->format('U'));

            $responseData = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                                ->processVirtualAbstract('item','get','all',
                                                         array('since_time'=>$tempSinceTime),NULL,
                                                         NULL,$account->getId(),NULL);
        } else {

            $tempSinceTime = $this->prepareSinceTime($sinceTime);

            $responseData = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                                ->processVirtualAbstract('item','get','changes',
                                                         array('since_time'=>$tempSinceTime),NULL,
                                                         NULL,$account->getId(),NULL);

            if (!isset($responseData['items']) || !isset($responseData['to_time'])) {

                $tempSinceTime = new DateTime('now', new DateTimeZone('UTC'));
                $tempSinceTime->modify("-1 day");
                $tempSinceTime = $tempSinceTime->format('Y-m-d H:i:s');

                $responseData = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                                ->processVirtualAbstract('item','get','changes',
                                                         array('since_time'=>$tempSinceTime),NULL,
                                                         NULL,$account->getId(),NULL);

                if (!isset($responseData['items']) || !isset($responseData['to_time'])) {

                    $tempSinceTime = new DateTime('now', new DateTimeZone('UTC'));
                    $tempSinceTime = $tempSinceTime->format('Y-m-d H:i:s');

                    $responseData = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                                ->processVirtualAbstract('item','get','changes',
                                                         array('since_time'=>$tempSinceTime),NULL,
                                                         NULL,$account->getId(),NULL);
                }
            }
        }

        $currentPercent = $currentPercent + $percentsForAccount * 0.15;
        $this->_lockItem->setPercents($currentPercent);
        $this->_lockItem->activate();

        $items = array();
        $tempToTime = Mage::helper('M2ePro')->getCurrentGmtDate();

        if (isset($responseData['items']) && isset($responseData['to_time'])) {
            $items = (array)$responseData['items'];
            if (is_array($responseData['to_time']) && isset($responseData['to_time'][0])) {
                $tempToTime = (string)$responseData['to_time'][0];
            } else {
                $tempToTime = (string)$responseData['to_time'];
            }
        }

        if (!is_string($tempToTime) || empty($tempToTime)) {
            $tempToTime = Mage::helper('M2ePro')->getCurrentGmtDate();
        }

        $account->setData('other_listings_last_synchronization', $tempToTime)->save();
        //---------------------------

        $this->_profiler->saveTimePoint(__METHOD__.'get'.$account->getId());

        $this->_profiler->addTitle('Total count items from eBay: '.count($items));

        if (count($items) == 0) {
            return;
        }

        $this->_profiler->addTimePoint(__METHOD__.'prepare'.$account->getId(),'Processing received items from eBay');
        $status = <<<STATUS
Task "3rd Party Listings Synchronization" for eBay account: "%acc%" is in data processing state. Please wait...
STATUS;
        $tempString = str_replace('%acc%', $account->getData('title'), Mage::helper('M2ePro')->__($status));
        $this->_lockItem->setStatus($tempString);

        // Progress bar
        //---------------------------
        $percentPerListing = ($percentsForAccount - $currentPercent) / count($items);
        $tempPercent = 0;
        //---------------------------

        // Save eBay listings
        //---------------------------
        $countItems = 0;
        foreach ($items as $item) {

            $tempPercent += $percentPerListing;

            if (floor($tempPercent) > 0) {
                $currentPercent += floor($tempPercent);
                $tempPercent -= floor($tempPercent);

                $this->_lockItem->setPercents($currentPercent);
                $this->_lockItem->activate();
            }

            if ($this->_isOldPartOfExtensionRecord($item)) {
                continue;
            }

            if ($this->_isOldPartOfExistRecord($item)) {
                continue;
            }

            $item = $this->_prepareForInsert($item,$account);
            Mage::helper('M2ePro/Component_Ebay')->getModel('Listing_Other')->setData($item)->save();

            $countItems++;
        }
        //---------------------------

        $this->_profiler->addTitle('Count not related with M2ePro items: '.$countItems);

        $this->_profiler->saveTimePoint(__METHOD__.'prepare'.$account->getId());
        $this->_profiler->addEol();
    }

    //####################################

    private function _isOldPartOfExistRecord($singleNonM2eProduct)
    {
        $dbSelect = Mage::getResourceModel('core/config')->getReadConnection()
            ->select()
            ->from(Mage::getResourceModel('M2ePro/Ebay_Listing_Other')->getMainTable(),new Zend_Db_Expr('COUNT(*)'))
            ->where("`old_items` LIKE '%{$singleNonM2eProduct['id']}%'");

        $items = Mage::getResourceModel('core/config')->getReadConnection()->fetchOne($dbSelect);

        if ($items === false || (int)$items <= 0) {
            return false;
        }

        return true;
    }

    private function _isOldPartOfExtensionRecord($singleNonM2eProduct)
    {
        $dbSelect = Mage::getResourceModel('core/config')->getReadConnection()
            ->select()
            ->from(Mage::getResourceModel('M2ePro/Ebay_Item')->getMainTable(),new Zend_Db_Expr('COUNT(*)'))
            ->where('`item_id` = ?', $singleNonM2eProduct['id']);

        $items = Mage::getResourceModel('core/config')->getReadConnection()->fetchOne($dbSelect);

        if ($items === false || (int)$items <= 0) {
            return false;
        }

        return true;
    }

    //------------------------------------

    private function prepareSinceTime($lastSinceTime)
    {
        // Get last since time
        //------------------------
        if (is_null($lastSinceTime) || empty($lastSinceTime)) {
            $lastSinceTime = new DateTime('now', new DateTimeZone('UTC'));
            $lastSinceTime->modify("-1 year");
        } else {
            $lastSinceTime = new DateTime($lastSinceTime, new DateTimeZone('UTC'));
        }
        //------------------------

        // Get min shold for synch
        //------------------------
        $minSholdTime = new DateTime('now', new DateTimeZone('UTC'));
        $minSholdTime->modify("-1 month");
        //------------------------

        // Prepare last since time
        //------------------------
        if ((int)$lastSinceTime->format('U') < (int)$minSholdTime->format('U')) {
            $lastSinceTime = new DateTime('now', new DateTimeZone('UTC'));
            $lastSinceTime->modify("-10 days");
        }
        //------------------------

        return strftime("%Y-%m-%d %H:%M:%S", (int)$lastSinceTime->format('U'));
    }

    private function _prepareForInsert($item, Ess_M2ePro_Model_Account $account)
    {
        $marketplace = Mage::helper('M2ePro/Component_Ebay')->getMarketplace($item['marketplace'],'code');

        $result = array(
            'id' => Mage::getModel('M2ePro/Ebay_Listing_Other')->load($item['id'], 'item_id')->getId(),
            'account_id' => (int)$account->getId(),
            'marketplace_id' => (int)$marketplace->getId(),
            'title' => (string)$item['title'],
            'item_id' =>  (double)$item['id'],
            'currency' => (string)$item['currency'],
            'online_price' => (float)$item['currentPrice'],
            'online_qty' => (int)$item['quantity'],
            'online_qty_sold' => (int)$item['quantitySold'],
            'online_bids' => (int)$item['bidCount'],
            'start_date' => (string)Mage::helper('M2ePro')->getDate($item['startTime']),
            'end_date' => (string)Mage::helper('M2ePro')->getDate($item['endTime'])
        );

        if ($item['listingType'] == Ess_M2ePro_Model_Ebay_Template_SellingFormat::EBAY_LISTING_TYPE_AUCTION) {
            $result['online_qty'] = 1;
        }

        if (($item['listingStatus'] == self::EBAY_STATUS_COMPLETED ||
             $item['listingStatus'] == self::EBAY_STATUS_ENDED) &&
             $result['online_qty'] == $result['online_qty_sold']) {

            $result['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_SOLD;

        } else if ($item['listingStatus'] == self::EBAY_STATUS_COMPLETED) {

            $result['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_STOPPED;

        } else if ($item['listingStatus'] == self::EBAY_STATUS_ENDED) {

            $result['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_FINISHED;

        } else if ($item['listingStatus'] == self::EBAY_STATUS_ACTIVE) {

            $result['status'] = Ess_M2ePro_Model_Listing_Product::STATUS_LISTED;

        }

        return $result;
    }

    //####################################
}
