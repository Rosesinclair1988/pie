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

class Lyra_Systempay_Model_Field_ShopUrl extends Mage_Core_Model_Config_Data {

	public function save() {
		$msg = Mage::helper('systempay')->__("Invalid value for field ") . '&laquo;' . Mage::helper('systempay')->__("Shop URL") . '&raquo;';
		
		$value = $this->getValue(); 

		if(!empty($value) && !preg_match('|^https?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $value)) {
			throw new Mage_Core_Exception($msg);
		} 
		
		return parent::save();
	}
	
}