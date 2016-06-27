<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
*/

class Ess_M2ePro_Model_Ebay_Synchronization_Tasks_Orders extends Ess_M2ePro_Model_Ebay_Synchronization_Tasks
{
    const PERCENTS_START = 0;
    const PERCENTS_END = 100;
    const PERCENTS_INTERVAL = 100;

    protected $_orderFixedItemTransactionInfo = array();
    protected $_orderAuctionItemTransactionInfo = array();

    protected $_iterationNumber = 0;
    protected $_percentInIteration = 0;

    protected $_configGroup = '/synchronization/settings/orders/';

    //####################################

    public function process()
    {
        // PREPARE SYNCH
        //---------------------------
        $this->prepareSynch();
        //$this->createRunnerActions();
        //---------------------------

        // RUN SYNCH
        //---------------------------
        $this->execute();
        //---------------------------

        // CANCEL SYNCH
        //---------------------------
        //$this->executeRunnerActions();
        $this->cancelSynch();
        //---------------------------
    }

    //####################################

    private function prepareSynch()
    {
        $this->_lockItem->activate();
        $this->_logs->setSynchronizationTask(Ess_M2ePro_Model_Synchronization_Log::SYNCH_TASK_ORDERS);

        if (count(Mage::helper('M2ePro/Component')->getActiveComponents()) > 1) {
            $componentName = Ess_M2ePro_Helper_Component_Ebay::TITLE.' ';
        } else {
            $componentName = '';
        }

        $this->_profiler->addEol();
        $this->_profiler->addTitle($componentName.'Orders Synchronization');
        $this->_profiler->addTitle('--------------------------');
        $this->_profiler->addTimePoint(__CLASS__, 'Total time');
        $this->_profiler->increaseLeftPadding(5);

        $this->_lockItem->setTitle(Mage::helper('M2ePro')->__($componentName.'Orders Synchronization'));
        $this->_lockItem->setPercents(self::PERCENTS_START);
        $this->_lockItem->setStatus(
            Mage::helper('M2ePro')->__('Task "Orders Synchronization" is started. Please wait...')
        );
    }

    private function cancelSynch()
    {
        $this->_lockItem->setPercents(self::PERCENTS_END);
        $this->_lockItem->setStatus(
            Mage::helper('M2ePro')->__('Task "Orders Synchronization" is finished. Please wait...')
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
        // Get accounts with enabled order synchronization
        //---------------------------
        $accounts = Mage::helper('M2ePro/Component_Ebay')->getCollection('Account')
            ->addFieldToFilter('orders_mode', Ess_M2ePro_Model_Ebay_Account::ORDERS_MODE_YES)
            ->getItems();

        if (count($accounts) == 0) {
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

    protected function processAccount(Ess_M2ePro_Model_Account $account, $percentsForAccount)
    {
        $this->_profiler->addEol();
        $this->_profiler->addTitle('Starting account "'.$account->getTitle().'"');

        $this->_profiler->addEol();
        $this->_profiler->addTimePoint(__METHOD__.'get'.$account->getId(),'Get orders from eBay');

        // ->__('Task "Orders Synchronization" for eBay account: "%acc%" is started. Please wait...')
        $status = 'Task "Orders Synchronization" for eBay account: "%acc%" is started. Please wait...';
        $tempString = str_replace('%acc%',$account->getTitle(),Mage::helper('M2ePro')->__($status));
        $this->_lockItem->setStatus($tempString);

        $currentPercent = $this->_lockItem->getPercents();

        // Get from time
        //---------------------------
        $fromTime = $this->prepareSinceTime($account->getData('orders_last_synchronization'));
        //---------------------------

        // Get orders from eBay
        //---------------------------
        $request = array(
            'last_update' => $fromTime
        );

        $response = Mage::getModel('M2ePro/Connector_Server_Ebay_Dispatcher')
                                ->processVirtualAbstract('sales', 'get', 'list',
                                                         $request, NULL,
                                                         NULL, $account, NULL);

        $ebayOrders = array();
        $toTime = $fromTime;

        if (isset($response['orders']) && isset($response['updated_to'])) {
            $ebayOrders = $response['orders'];
            $toTime = $response['updated_to'];
        }

        if (count($ebayOrders) == 0 && is_null($account->getData('orders_last_synchronization'))) {
            $account->setData('orders_last_synchronization', $toTime)->save();
            return;
        }
        //---------------------------

        $currentPercent = $currentPercent + $percentsForAccount * 0.15;
        $this->_lockItem->setPercents($currentPercent);
        $this->_lockItem->activate();

        $this->_profiler->saveTimePoint(__METHOD__.'get'.$account->getId());

        $this->_profiler->addTitle('Total count orders received from eBay: '.count($ebayOrders));
        $this->_profiler->addTimePoint(__METHOD__.'process'.$account->getId(),'Processing received orders from eBay');

        // ->__('Task "Orders Synchronization" for eBay account: "%acc%" is in data processing state. Please wait...')
        $status = 'Task "Orders Synchronization" for eBay account: "%acc%" is in data processing state. Please wait...';
        $tempString = str_replace('%acc%',$account->getTitle(),Mage::helper('M2ePro')->__($status));
        $this->_lockItem->setStatus($tempString);

        // Save eBay orders
        //---------------------------
        $orders = array();

        foreach ($ebayOrders as $ebayOrderData) {
            /** @var $ebayOrder Ess_M2ePro_Model_Ebay_Order_Builder */
            $ebayOrder = Mage::getModel('M2ePro/Ebay_Order_Builder');
            $ebayOrder->setAccount($account);
            $ebayOrder->initialize($ebayOrderData);

            $orders[] = $ebayOrder->process();
        }
        //---------------------------

        $orders = array_filter($orders);

        if (count($orders) == 0) {
            $account->setData('orders_last_synchronization', $toTime)->save();
            return;
        }

        $currentPercent = $currentPercent + $percentsForAccount * 0.05;
        $this->_lockItem->setPercents($currentPercent);
        $this->_lockItem->activate();

        $this->_profiler->saveTimePoint(__METHOD__.'process'.$account->getId());

        $this->_profiler->addEol();
        $this->_profiler->addTimePoint(__METHOD__.'magento_orders_process'.$account->getId(),'Creating magento orders');

        // ->__('Task "Orders Synchronization" for eBay account: "%acc%" is in order creation state.. Please wait...')
        $status = 'Task "Orders Synchronization" for eBay account: "%acc%" is in order creation state.. Please wait...';
        $tempString = str_replace('%acc%',$account->getTitle(),Mage::helper('M2ePro')->__($status));
        $this->_lockItem->setStatus($tempString);

        // Create magento orders
        //---------------------------
        $magentoOrders = $paymentTransactions = $invoices = $shipments = $tracks = 0;

        $percentPerOrder = ($percentsForAccount - $currentPercent) / count($orders);
        $tempPercent = 0;

        foreach ($orders as $order) {
            /** @var $order Ess_M2ePro_Model_Order */
            $order->createMagentoOrder() && $magentoOrders++;
            $order->updateMagentoOrderStatus();
            $order->createPaymentTransactions() && $paymentTransactions++;
            $order->createInvoice() && $invoices++;
            $order->createShipment() && $shipments++;
            $order->createTracks() && $tracks++;

            $tempPercent += $percentPerOrder;

            if (floor($tempPercent) > 0) {
                $currentPercent += floor($tempPercent);
                $tempPercent -= floor($tempPercent);

                $this->_lockItem->setPercents($currentPercent);
                $this->_lockItem->activate();
            }
        }
        //---------------------------

        $this->_profiler->saveTimePoint(__METHOD__.'magento_orders_process'.$account->getId());

        $this->_profiler->addTitle('Total count magento orders created: ' . $magentoOrders);
        $this->_profiler->addTitle('Total count payment transactions created: ' . $paymentTransactions);
        $this->_profiler->addTitle('Total count invoices created: ' . $invoices);
        $this->_profiler->addTitle('Total count shipments created: ' . $shipments);

        $this->_profiler->addEol();
        $this->_profiler->addTitle('End account "'.$account->getTitle().'"');

        $account->setData('orders_last_synchronization', $toTime)->save();
    }

    //####################################

    private function prepareSinceTime($sinceTime)
    {
        if (is_null($sinceTime)) {
            $sinceTime = new DateTime('now', new DateTimeZone('UTC'));
            $sinceTime->modify('-10 days');
        } else {
            $sinceTime = new DateTime($sinceTime, new DateTimeZone('UTC'));
        }

        return Ess_M2ePro_Model_Connector_Server_Ebay_Abstract::ebayTimeToString($sinceTime);
    }

    //####################################
}
