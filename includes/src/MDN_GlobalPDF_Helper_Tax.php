<?php 

class MDN_GlobalPDF_Helper_Tax extends Mage_Core_Helper_Abstract 
{
	public function getPriceInclTax($product, $price)
	{
		if (Mage::getStoreConfig('tax/calculation/price_includes_tax') == 1)
		{
			return $price;
		}
		else
		{
			$helper = mage::helper('tax');
			return $helper->getPrice($product, $price, true);
		}
	}	
}
