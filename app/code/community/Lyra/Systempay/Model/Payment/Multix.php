<?php

class Lyra_Systempay_Model_Payment_Multix extends Lyra_Systempay_Model_Payment_Multi {

	protected function _setExtraFields($order) {
		$info = $this->getInfoInstance();
	
		// set mutiple payment option
		$option = $info->getAdditionalInformation('systempay_multi_option');
		
		$amount = $this->_systempayRequest->get('amount');
		$first = ($option['first'] != '') ? round(($option['first'] / 100) * $amount) : null;
		$this->_systempayRequest->setMultiPayment($amount, $first, $option['count'], $option['period']);
		$this->_systempayRequest->set('contracts', ($option['contract']) ? 'CB=' . $option['contract'] : null);
	
		$this->_getHelper()->log('Multiple payment configuration is ' . $this->_systempayRequest->get('payment_config'), Zend_Log::INFO);
	}
	
	/**
	 * Return true if the method can be used at this time
	 *
	 * @return bool
	 */
	public function isAvailable($quote=null) {
		return false;
	}
}