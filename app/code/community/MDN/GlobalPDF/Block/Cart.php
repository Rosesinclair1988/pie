<?php

class MDN_GlobalPDF_Block_Cart extends Mage_Core_Block_Template
{
	public function getPrintUrl()
	{
		return $this->getUrl('GlobalPDF/Cart/Print');
	}

}
