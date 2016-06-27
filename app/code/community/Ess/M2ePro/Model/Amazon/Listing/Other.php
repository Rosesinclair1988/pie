<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Listing_Other extends Ess_M2ePro_Model_Component_Child_Amazon_Abstract
{
    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('M2ePro/Amazon_Listing_Other');
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

    /**
     * @return Ess_M2ePro_Model_Magento_Product
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    // ########################################

    public function getItemId()
    {
        return $this->getData('item_id');
    }

    public function getSku()
    {
        return $this->getData('sku');
    }

    public function getGeneralId()
    {
        return $this->getData('general_id');
    }

    //-----------------------------------------

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getDescription()
    {
        return (string)$this->getData('description');
    }

    public function getNotice()
    {
        return (string)$this->getData('notice');
    }

    //-----------------------------------------

    public function getOnlinePrice()
    {
        return (float)$this->getData('online_price');
    }

    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    //-----------------------------------------

    public function isAfnChannel()
    {
        return (int)$this->getData('is_afn_channel') ==
            Ess_M2ePro_Model_Amazon_Listing_Product::IS_AFN_CHANNEL_YES;
    }

    public function isIsbnGeneralId()
    {
        return (int)$this->getData('is_isbn_general_id') ==
            Ess_M2ePro_Model_Amazon_Listing_Product::IS_ISBN_GENERAL_ID_YES;
    }

    //-----------------------------------------

    public function getStartDate()
    {
        return $this->getData('start_date');
    }

    public function getEndDate()
    {
        return $this->getData('end_date');
    }

    // ########################################

    public function getRelatedStoreId()
    {
        $marketplaceId = $this->getParentObject()->getMarketplaceId();
        $marketplaceItem = $this->getAccount()->getChildObject()->getMarketplaceItem($marketplaceId);
        return is_null($marketplaceItem)
            ? Mage_Core_Model_App::ADMIN_STORE_ID : (int)$marketplaceItem['related_store_id'];
    }

    // ########################################
}
