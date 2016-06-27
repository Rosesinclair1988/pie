<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Amazon_Order_View_Form extends Mage_Adminhtml_Block_Widget_Container
{
    public $shippingAddress = array();

    public $realMagentoOrderId = NULL;

    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('amazonOrderViewForm');
        $this->setTemplate('M2ePro/amazon/order.phtml');
        //------------------------------

        /** @var $order Ess_M2ePro_Model_Order */
        $this->order = Mage::helper('M2ePro')->getGlobalValue('temp_data');
    }

    protected function _beforeToHtml()
    {
        // Magento order data
        // ---------------
        $this->realMagentoOrderId = NULL;

        $magentoOrder = $this->order->getMagentoOrder();
        if (!is_null($magentoOrder)) {
            $this->realMagentoOrderId = $magentoOrder->getRealOrderId();
        }
        // ---------------

        // Shipping data
        // ---------------
        $this->shippingAddress = $this->order->getChildObject()->getShippingAddress();

        if (empty($this->shippingAddress['country_name']) && !empty($this->shippingAddress['country_code'])) {
            $country = Mage::getModel('directory/country')->load($this->shippingAddress['country_code']);
            if (!is_null($country->getId())) {
                $this->shippingAddress['country_name'] = $country->getName();
            }
        }
        // ---------------

        $this->setChild('item', $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_order_view_item'));
        $this->setChild('log', $this->getLayout()->createBlock('M2ePro/adminhtml_amazon_order_view_log'));

        return parent::_beforeToHtml();
    }
}
