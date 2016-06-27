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

class Lyra_Systempay_Block_Gift extends Lyra_Systempay_Block_Abstract {
	
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('systempay/gift.phtml');
    }

    public function getGcTypeLabel($code) {
    	$cards = Mage::getModel('systempay/payment_gift')->getAvailableGiftCards();
	    if(key_exists($code, $cards)) {
			return $cards[$code];    		
	    } else {
	   		return '';
	    }
    }
    
    public function getGcTypeImageSrc($card) {
    	if(substr($card, -3) === '_SB' ) {
    		$card = substr($card, 0, strpos($card, '_SB'));
    	}
    	
    	return $this->_checkAndGetSkinUrl(strtolower($card) . '.gif');
    }
    
    public function getConfigData($name) {
    	return Mage::getModel('systempay/payment_gift')->getConfigData($name);
    }
    
    protected function _getModelName() {
    	return 'systempay/payment_gift';
    }
}