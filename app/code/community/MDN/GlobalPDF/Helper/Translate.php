<?php 

class MDN_GlobalPDF_Helper_Translate extends Mage_Core_Helper_Abstract 
{
	/**
	 * Translate label
	 */
	public function translate($label)
	{
		return $this->__($label);
	}
}
