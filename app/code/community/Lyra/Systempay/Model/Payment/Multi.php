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

class Lyra_Systempay_Model_Payment_Multi extends Lyra_Systempay_Model_Payment_Front {
	protected $_code = 'systempay_multi';
	protected $_formBlockType = 'systempay/multi';
	
	protected $_canSaveCc = false;
	
	protected $_logo = 'multi.png';
	
	protected function _setExtraFields($order) {
		
	}
	
	/**
	 * Assign data to info model instance
	 *
	 * @param   mixed $data
	 * @return  Mage_Payment_Model_Info
	 */
	public function assignData($data) {
		if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}
		
		$info = $this->getInfoInstance();
		
		// load option informations
		$option = $this->getOption($data->getSystempayMultiOption());
		
		$info->setAdditionalInformation('systempay_multi_option', $option);
		$info->setMethod($this->_code . '_' . $option['count'] . 'x'); 
		
		return $this;
	}
	
	/**
	 * Return true if the method can be used at this time
	 *
	 * @return bool
	 */
	public function isAvailable($quote=null) {
		if(! parent::isAvailable($quote)) {
			return false;
		}
		
		$amount = $quote ? $quote->getBaseGrandTotal() : null;
		if($amount) {
			$options = $this->getAvailableOptions($amount);
			return count($options) > 0;
		}
		
		return false;
	}
	
	public function getAvailableOptions($amount=null) {
		$configOptions = unserialize($this->getConfigData('payment_options'));
		 
		$options = array();
		if(is_array($configOptions) && count($configOptions) > 0) {
			foreach ($configOptions as $code => $value) {
				if(empty($value)) {
					continue;
				}
	
				if((!$amount || !$value['minimum'] || $amount > $value['minimum']) 
				&& (!$amount || !$value['maximum'] || $amount < $value['maximum'])) {
					// option will be available
					$options[$code]	= $value;
				}
			}
		}
		 
		return $options;
	}
	
	public function getOption($code) {
		$options = $this->getAvailableOptions();
		return $options[$code];
	}
	
}