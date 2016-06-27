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

class Lyra_Systempay_Model_Field_Gift_AddedCards extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array {

	protected $_eventPrefix = 'systempay_field_gift_addedCards';
	
	public function save() {
		$values = $this->getValue();
	
		if(is_array($values)) {
			$i = 0;
			foreach ($values as $key => $value) {
				$i++;
				
				if(empty($value)) {
					continue;
				}
				
				if(empty($value['code']) || !preg_match('#^([A-Za-z0-9\-_]+;)*[A-Za-z0-9\-_]*$#', $value['code'])) {
					$this->_throwError('Card Code', $i);
				}
				if(!preg_match('#^[^<>]*$#', $value['name'])) {
					$this->_throwError('Card Name', $i);
				}
			}
		}
	
		return parent::save();
	}
	
	private function _throwError($field, $position) {
		// main message 
		$msg = 'The field &laquo;%s&raquo; is invalid: please check &laquo;%s&raquo; of the option %s.';
		
		// translate field and option names
		$option = Mage::helper('systempay')->__('Add cards');
		$field = Mage::helper('systempay')->__($field);
		
		// throw exception
		throw new Mage_Core_Exception(Mage::helper('systempay')->__($msg, $option, $field, $position));
	}
}