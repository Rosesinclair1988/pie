<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Block_Adminhtml_Ebay_Order_View extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayOrderView');
        $this->_blockGroup = 'M2ePro';
        $this->_controller = 'adminhtml_ebay_order';
        $this->_mode = 'view';
        //------------------------------

        // Set header text
        //------------------------------
        $this->_headerText = Mage::helper('M2ePro')->__('View Order Details');
        //------------------------------

        /** @var $order Ess_M2ePro_Model_Order */
        $this->order = Mage::helper('M2ePro')->getGlobalValue('temp_data');

        // Set buttons actions
        //------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->_addButton('back', array(
            'label'     => Mage::helper('M2ePro')->__('Back'),
            'onclick'   => 'CommonHandlerObj.back_click(\''
                           .Mage::helper('M2ePro')->getBackUrl('*/adminhtml_order/index')
                           .'\')',
            'class'     => 'back'
        ));

        if ($this->order->getChildObject()->canUpdateShippingStatus()) {

            $this->_addButton('ship', array(
                'label'     => Mage::helper('M2ePro')->__('Mark as Shipped'),
                'onclick'   => "setLocation('"
                               .$this->getUrl('*/*/updateShippingStatus',
                                              array('id' => $this->order->getId()))
                               ."');",
                'class'     => 'scalable'
            ));

        }

        if ($this->order->getChildObject()->canUpdatePaymentStatus()) {

            $this->_addButton('pay', array(
                'label'     => Mage::helper('M2ePro')->__('Mark as Paid'),
                'onclick'   => "setLocation('"
                               .$this->getUrl('*/*/updatePaymentStatus',
                                              array('id' => $this->order->getId()))
                               ."');",
                'class'     => 'scalable'
            ));

        }

        if ($this->order->getChildObject()->canCreateMagentoOrder(true)) {

            $this->_addButton('order', array(
                'label'     => Mage::helper('M2ePro')->__('Create Order'),
                'onclick'   => "setLocation('"
                               .$this->getUrl('*/*/createMagentoOrder',
                                              array('id' => $this->order->getId()))
                               ."');",
                'class'     => 'scalable'
            ));

        }
        //------------------------------
    }
}
