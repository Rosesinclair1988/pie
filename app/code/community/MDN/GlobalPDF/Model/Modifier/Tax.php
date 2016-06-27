<?php

class MDN_GlobalPDF_Model_Modifier_Tax extends MDN_GlobalPDF_Model_Modifier_Abstract
{
	public function apply($modifierParam, $value)
	{
		$product = mage::getModel('catalog/product')->load($modifierParam);
		if ($product->getId())
			$value = Mage::helper('GlobalPDF/Tax')->getPriceInclTax($product, $value);
		return $value;
	}
}
