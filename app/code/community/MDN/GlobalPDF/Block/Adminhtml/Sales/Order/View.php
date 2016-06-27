<?php

class MDN_GlobalPDF_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{

    public function __construct()
    {
        parent::__construct();

		$printUrl = $this->getUrl('GlobalPDF/AdminOrder/Print', array('order_id' => $this->getOrder()->getId()));
		$this->_addButton('order_print', array(
			'label'     => Mage::helper('GlobalPDF')->__('Print'),
			'onclick'   => 'setLocation(\'' . $printUrl . '\')',
			'class'     => 'save'
		));
		
    }

}
