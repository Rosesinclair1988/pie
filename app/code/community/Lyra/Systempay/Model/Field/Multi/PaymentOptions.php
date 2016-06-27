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

class Lyra_Systempay_Model_Field_Multi_PaymentOptions extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array {

	protected $_eventPrefix = 'systempay_field_multi_paymentOptions';
	
	public function save() {
		$values = $this->getValue();
	
		if(is_array($values)) {
			$i = 0;
			foreach ($values as $key => $value) {
				$i++;
				
				if(empty($value)) {
					continue;
				}
				
				if(empty($value['label'])) {
					$this->_throwError('Label', $i);
				}
				if(!empty($value['minimum']) && !preg_match('#^\d+(\.\d+)?$#', $value['minimum'])) {
					$this->_throwError('Minimum amount', $i);
				}
				if(!empty($value['maximum']) && !preg_match('#^\d+(\.\d+)?$#', $value['maximum'])) {
					$this->_throwError('Maximum amount', $i);
				}
				if(!empty($value['contract']) && !preg_match('#^[^;=]+$#', $value['contract'])) {
					$this->_throwError('Contract', $i);
				}
				if(!preg_match('#^[1-9]\d*$#', $value['count'])) {
					$this->_throwError('Count', $i);
				}
				if(!preg_match('#^[1-9]\d*$#', $value['period'])) {
					$this->_throwError('Period', $i);
				}
				if(!empty($value['first']) && (!is_numeric($value['first']) || $value['first'] >= 100)) {
					$this->_throwError('1st payment', $i);
				}
				
				Mage::helper('systempay')->updateOptionModelConfig($value['count']);
			}
		}
	
		return parent::save();
	}
	
	private function _throwError($field, $position) {
		// main message 
		$msg = 'The field &laquo;%s&raquo; is invalid: please check &laquo;%s&raquo; of the option %s.';
		
		// translate field and option names
		$option = Mage::helper('systempay')->__('Payment Options');
		$field = Mage::helper('systempay')->__($field);
		
		// throw exception
		throw new Mage_Core_Exception(Mage::helper('systempay')->__($msg, $option, $field, $position));
	}
}