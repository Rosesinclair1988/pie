<?php
class Miura_Coupsdecoeur_Block_Coupsdecoeur extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getCoupsdecoeur()     
     { 
        if (!$this->hasData('coupsdecoeur')) {
            $this->setData('coupsdecoeur', Mage::registry('coupsdecoeur'));
        }
        return $this->getData('coupsdecoeur');
        
    }
}
