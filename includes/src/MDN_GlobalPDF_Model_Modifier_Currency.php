<?php

class MDN_GlobalPDF_Model_Modifier_Currency extends MDN_GlobalPDF_Model_Modifier_Abstract
{
	public function apply($modifierParam, $value)
	{
		$currency = Mage::getModel('directory/currency')->load($modifierParam);
		return $currency->formatTxt($value);
	}
}
