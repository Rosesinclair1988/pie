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

class Lyra_Systempay_Model_Payment_Gift extends Lyra_Systempay_Model_Payment_Front {
	protected $_code = 'systempay_gift';
	protected $_formBlockType = 'systempay/gift';
	
	protected $_canSaveCc = false;
	
	protected $_logo = 'gift.png';
	
	protected function _setExtraFields($order) {
		$info = $this->getInfoInstance();
		
		// override payment_cards
		$this->_systempayRequest->set('payment_cards', $info->getCcType());
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
		$info->setCcType($data->getSystempayGiftCcType());
	
		return $this;
	}
	
	/**
	 * Return true if the method can be used at this time
	 *
	 * @return bool
	 */
	public function isAvailable($quote=null) {
		if(!$this->getConfigData('gift_cards')) {
			return false;
		}
		
		return parent::isAvailable($quote);
	}
	
	public function getAvailableGiftCards() {
		$options = Mage::helper('systempay')->getConfigArray('gift_cards'); // the default gift cards
		
		$addedCards = unserialize($this->getConfigData('added_gift_cards')); // the user-added gift cards
		if(is_array($addedCards) && count($addedCards) > 0) {
			foreach ($addedCards as $code => $value) {
				if(empty($value)) {
					continue;
				}
	
				$options[$value['code']] = $value['name']; 
			}
		}
			
		return $options;
	}
}