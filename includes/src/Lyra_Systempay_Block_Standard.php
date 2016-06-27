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

class Lyra_Systempay_Block_Standard extends Lyra_Systempay_Block_Abstract {
	
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('systempay/standard.phtml');
    }

    public function getAvailableCcTypes() {
    	$cards = array();
    
    	foreach (Mage::helper('systempay')->getConfigArray('payment_cards') as $code => $name) {
    		if($code != '') {
    			$cards[] = $code;
    		}
    	}
    
    	return $cards;
    }
    
    public function getCcTypeLabel($code) {
    	$cards = Mage::helper('systempay')->getConfigArray('payment_cards');
    	return $cards[$code];
    }
    
    public function getCcTypeNetwork($code) {
    	$xmlNode = 'global/payment/systempay/payment_cards';
    	foreach(Mage::getConfig()->getNode($xmlNode)->asArray() as $xmlData) {
    		if($xmlData['code'] == $code) {
    			return $xmlData['network'];
    		}
    	}
    	
    	return false;
    }
    
    public function getCcTypeImageSrc($card) {
    	return $this->_checkAndGetSkinUrl(strtolower($card) . '.gif');
    }
    
    public function getConfigData($name) {
    	return Mage::getModel('systempay/payment_standard')->getConfigData($name);
    }
    
    protected function _getModelName() {
    	return 'systempay/payment_standard';
    }
}