<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Ebay_Listing_Other extends Ess_M2ePro_Model_Component_Child_Ebay_Abstract
{
    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Ebay_Listing_Other');
    }

    // ########################################

    /**
     * @return Ess_M2ePro_Model_Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return Ess_M2ePro_Model_Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    // ########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getItemId()
    {
        return (double)$this->getData('item_id');
    }

    public function getCurrency()
    {
        return $this->getData('currency');
    }

    public function getOnlinePrice()
    {
        return (float)$this->getData('online_price');
    }

    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    public function getOnlineQtySold()
    {
        return (int)$this->getData('online_qty_sold');
    }

    public function getOnlineBids()
    {
        return (int)$this->getData('online_bids');
    }

    // ########################################

    public function getRelatedStoreId()
    {
        return Mage_Core_Model_App::ADMIN_STORE_ID;
    }

    //-----------------------------------------

    public function relistAction(array $params = array())
    {
        return $this->processDispatcher(Ess_M2ePro_Model_Connector_Server_Ebay_Item_Dispatcher::ACTION_RELIST,$params);
    }

    public function stopAction(array $params = array())
    {
        return $this->processDispatcher(Ess_M2ePro_Model_Connector_Server_Ebay_Item_Dispatcher::ACTION_STOP,$params);
    }

    //-----------------------------------------

    protected function processDispatcher($action, array $params = array())
    {
        if (is_null($this->getId())) {
             throw new Exception('Method require loaded instance first');
        }

        $dispatcher = Mage::getModel('M2ePro/Connector_Server_Ebay_OtherItem_Dispatcher');

        return $dispatcher->process($action, $this->getId(), $params);
    }

    // ########################################
}
