<?php

class MDN_GlobalPDF_Block_Product extends Mage_Core_Block_Template
{
	public function getPrintUrl()
	{
		return $this->getUrl('GlobalPDF/Product/Print', array('id' => $this->getProduct()->getId()));
	}
	
	public function getProduct()
	{
		return mage::registry('current_product');
	}

}
