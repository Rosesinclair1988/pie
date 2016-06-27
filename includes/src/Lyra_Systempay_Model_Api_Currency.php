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

/**
 * Class representing a currency, used for converting alpha/numeric iso codes and float/integer amounts
 */
class Lyra_Systempay_Model_Api_Currency extends Mage_Core_Model_Abstract {
	private $alpha3;
	private $num;
	private $decimals;

	public function __construct($alpha3, $num, $decimals = 2) {
		$this->alpha3 = $alpha3;
		$this->num = $num;
		$this->decimals = $decimals;
	}
	
	public function convertAmountToInteger($float) {
		$coef = pow(10, $this->decimals);

		return intval(strval($float * $coef));
	}
	
	public function convertAmountToFloat($integer) {
		$coef = pow(10, $this->decimals);
	
		return floatval($integer) / $coef;
	}
	
	public function getAlpha3() {
		return $this->alpha3;
	}
	
	public function getNum() {
		return $this->num;
	}
	
	public function getDecimals() {
		return $this->decimals;
	}
}