<?php
#####################################################################################################
#
#					Module pour la plateforme de paiement Systempay
#						Version : 1.2 (révision 46959)
#									########################
#					Développé pour Magento
#						Version : 1.5.1.0
#						Compatibilité plateforme : V2
#									########################
#					Développé par Lyra Network
#						http://www.lyra-network.com/
#						13/05/2013
#						Contact : supportvad@lyra-network.com
#
#####################################################################################################

class Lyra_Systempay_Block_Multi extends Lyra_Systempay_Block_Abstract {
	
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('systempay/multi.phtml');
    }

    public function getAvailableOptions() {
    	$amount = $this->getMethod()->getInfoInstance()->getQuote()->getBaseGrandTotal();
    	return Mage::getModel('systempay/payment_multi')->getAvailableOptions($amount);
    }
    
    protected function _getModelName() {
    	return 'systempay/payment_multi';
    }
}