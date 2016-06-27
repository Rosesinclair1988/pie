<?php
class Miura_Astucespro_Block_Astucespro extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getAstucespro()     
     { 
        if (!$this->hasData('astucespro')) {
            $this->setData('astucespro', Mage::registry('astucespro'));
        }
        return $this->getData('astucespro');
        
    }
}
