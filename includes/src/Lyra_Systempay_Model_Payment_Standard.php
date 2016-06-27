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

class Lyra_Systempay_Model_Payment_Standard extends Lyra_Systempay_Model_Payment_Front {
	protected $_code = 'systempay';
	protected $_formBlockType = 'systempay/standard';
	
	protected $_canSaveCc = true;
	
	protected $_logo = 'standard.png';
	
	protected function _setExtraFields($order) {
		$info = $this->getInfoInstance();
		
		if($this->_isLocalCcType() || $this->_isLocalCcInfo()) {
			// override payment_cards
			$this->_systempayRequest->set('payment_cards', $info->getCcType());
		}
		
		if($this->_isLocalCcInfo()) {
			$this->_systempayRequest->set('card_number', $info->getCcNumber());
			$this->_systempayRequest->set('cvv', $info->getAdditionalData());
			$this->_systempayRequest->set('expiry_year', $info->getCcExpYear());
			$this->_systempayRequest->set('expiry_month', $info->getCcExpMonth());
			
			// override action_mode 
			$this->_systempayRequest->set('action_mode', 'SILENT');
		}
	}
	
	/**
	 * Assign data to info model instance
	 *
	 * @param   mixed $data
	 * @return  Mage_Payment_Model_Info
	 */
	public function assignData($data) {
		if(!$this->_isLocalCcType() && !$this->_isLocalCcInfo()) {
			return $this;
		}
	
		if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}
	
		$info = $this->getInfoInstance();
		
		// set card info
		$info->setCcType($data->getSystempayCcType());
		if($this->_isLocalCcInfo()) {
			$info->setCcLast4(substr($data->getSystempayCcNumber(), -4))
				->setCcNumber($data->getSystempayCcNumber())
				->setCcCid($data->getSystempayCcCvv())
				->setCcExpMonth($data->getSystempayCcExpMonth())
				->setCcExpYear($data->getSystempayCcExpYear())
				->setAdditionalData($data->getSystempayCcCvv())
			;
		}
	
		return $this;
	}
	
	/**
	 * Prepare info instance for save
	 *
	 * @return Mage_Payment_Model_Abstract
	 */
	public function prepareSave() {
		$info = $this->getInfoInstance();
		if ($this->_canSaveCc && !is_null($info->getCcNumber())) {
			$info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
			$info->setCcCidEnc($info->encrypt($info->getCcCid()));
		}
	
		$info->setCcNumber(null);
		$info->setCcCid(null);
		return $this;
	}
	
	/**
	 * Check if the silent payment option is selected
	 * @return boolean
	 */
	private function _isLocalCcInfo() {
		return $this->getConfigData('card_info_mode') == 3;
	}
	
	/**
	 * Check if the local card type selection option is choosen
	 * @return boolean
	 */
	private function _isLocalCcType() {
		return $this->getConfigData('card_info_mode') == 2;
	}
}