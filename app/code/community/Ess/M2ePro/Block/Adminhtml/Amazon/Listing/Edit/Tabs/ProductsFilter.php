<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Listing_Edit_Tabs_ProductsFilter extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('amazonListingEditTabsProductsFilter');
        //------------------------------

        $this->setTemplate('M2ePro/amazon/listing/tabs/products_filter.phtml');
    }

    protected function _beforeToHtml()
    {
        //-------------------------------
        $accounts = Mage::helper('M2ePro/Component_Amazon')->getCollection('Account')
            ->setOrder('title', 'ASC')
            ->toArray();
        $this->setData('accounts', $accounts['items']);
        //-------------------------------

        //-------------------------------
        $formData = Mage::helper('M2ePro')->getGlobalValue('temp_data');
        $this->setData('attributes',
                       Mage::helper('M2ePro/Magento')->getAttributesByAttributeSets($formData['attribute_sets']));
        //-------------------------------

        return parent::_beforeToHtml();
    }
}
