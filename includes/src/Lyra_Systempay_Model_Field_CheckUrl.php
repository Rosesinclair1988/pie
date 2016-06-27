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

class Lyra_Systempay_Model_Field_CheckUrl extends Mage_Core_Model_Config_Data {

	/**
	 * Processing object after load data
	 *
	 * @return Mage_Core_Model_Abstract
	 */
	protected function _afterLoad() {
		$stores = Mage::app()->getStores();
		
		$defaultStore = '';
		foreach ($stores as $store) {
			$defaultStore = $store->getId();
			break;
		}
		
		$checkUrl = Mage::getUrl($this->getValue(), array('_secure'=>true, '_store'=> $defaultStore));
		$this->setValue($checkUrl);
		
		parent::_afterLoad();
		return $this;
	}
	
}

?>