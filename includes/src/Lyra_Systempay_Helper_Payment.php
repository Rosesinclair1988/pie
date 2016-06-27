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

class Lyra_Systempay_Helper_Payment extends Mage_Core_Helper_Abstract {
	
	/**
	 * The list of encodings supported by Systempay API.
	 */
	const SUPPORTED_ENCODINGS = "UTF-8,ASCII,Windows-1252,ISO-8859-15,ISO-8859-1,ISO-8859-6,CP1256";
	
	/**
	 * Generate a trans_id.
	 * To be independent from shared/persistent counters, we use the number of 1/10seconds since midnight,
	 * which has the appropriate format (000000-899999) and has great chances to be unique.
	 * @return string the generated trans_id
	 * @access private
	 */
	public function generateTransId($timestamp) {
		list($usec, $sec) = explode(" ", microtime()); // microseconds, php4 compatible
		$temp = ($timestamp + $usec - strtotime('today 00:00')) * 10;
		$temp = sprintf('%06d', $temp);
	
		return $temp;
	}
	
	/**
	 * Returns the iso codes of language accepted by the payment page
	 * @return array
	 */
	public function getSupportedLanguages() {
		return array('fr', 'de', 'en', 'es', 'zh', 'it', 'ja', 'pt', 'nl');
	}
	
	/**
	 * Return the list of currencies recognized by the Systempay platform
	 * @return array
	 */
	public function getSupportedCurrencies() {
		$currencies = array(
				array('ARS', 32, 2), array('AUD', 36, 2), array('KHR', 116, 0), array('CAD', 124, 2), array('CNY', 156, 1), array('HRK', 191, 2), array('CZK', 203, 2), array('DKK', 208, 2), array('EKK', 233, 2), array('HKD', 344, 2), array('HUF', 348, 2), array('ISK', 352, 0), array('IDR', 360, 0), array('JPY', 392, 0), array('KRW', 410, 0), array('LVL', 428, 2), array('LTL', 440, 2), array('MYR', 458, 2), array('MXN', 484, 2), array('NZD', 554, 2), array('NOK', 578, 2), array('PHP', 608, 2), array('RUB', 643, 2), array('SGD', 702, 2), array('ZAR', 710, 2), array('SEK', 752, 2), array('CHF', 756, 2), array('THB', 764, 2), array('GBP', 826, 2), array('USD', 840, 2), array('TWD', 901, 1), array('RON', 946, 2), array('TRY', 949, 2), array('XOF', 952, 0), array('BGN', 975, 2), array('EUR', 978, 2), array('PLN', 985, 2), array('BRL', 986, 2)
		);
			
		$systempayCurrencies = array();
			
		foreach($currencies as $currency) {
			$systempayCurrencies[] = new Lyra_Systempay_Model_Api_Currency($currency[0], $currency[1], $currency[2]);
		}
			
		return $systempayCurrencies;
	}
	
	/**
	 * Return a currency from its iso 3-letters code
	 * @param string $alpha3
	 * @return Lyra_Systempay_Model_Api_Currency
	 */
	public function findCurrencyByAlphaCode($alpha3) {
		$list = $this->getSupportedCurrencies();
		foreach ($list as $currency) {
			/** @var Lyra_Systempay_Model_Api_Currency $currency */
			if ($currency->getAlpha3() == $alpha3) {
				return $currency;
			}
		}
		return null;
	}
	
	/**
	 * Returns a currency form its iso numeric code
	 * @param int $num
	 * @return Lyra_Systempay_Model_Api_Currency
	 */
	public function findCurrencyByNumCode($numeric) {
		$list = $this->getSupportedCurrencies();
		foreach ($list as $currency) {
			/** @var Lyra_Systempay_Model_Api_Currency $currency */
			if ($currency->getNum() == $numeric) {
				return $currency;
			}
		}
		return null;
	}
	
	/**
	 * Returns a currency numeric code from its 3-letters code
	 * @param string $alpha3
	 * @return int
	 */
	public function getCurrencyNumCode($alpha3) {
		$currency = $this->findCurrencyByAlphaCode($alpha3);
		return is_a($currency, 'Lyra_Systempay_Model_Api_Currency') ? $currency->getNum() : null;
	}
	
	/**
	 * Return the list of encoding supported by the Systempay platform
	 * @return array
	 */
	public function getSupportedEncodings() {
		return explode(",", self::SUPPORTED_ENCODINGS);
	}
	
	/**
	 * Public static method to compute a Systempay signature. Parameters must be in utf-8.
	 * @param array[string]string $parameters payment gateway request/response parameters
	 * @param string $key shop certificate
	 * @param boolean $hashed set to false to get the raw, unhashed signature
	 * @access public
	 */
	public function sign($parameters, $key, $hashed = true) {
		$signContent = "";
		ksort($parameters);
		foreach ($parameters as $name => $value) {
			if (substr($name, 0, 5) == 'vads_') {
				$signContent .= $value . '+';
			}
		}
		$signContent .= $key;
		$sign = $hashed ? sha1($signContent) : $signContent;
		return $sign;
	}
	
	/**
	 * PHP is not yet a sufficiently advanced technology to be indistinguishable from magic...
	 * so don't use magic_quotes, they mess up with the gateway response analysis.
	 *
	 * @param array $potentiallyMagicallyQuotedData
	 */
	public function uncharm($potentiallyMagicallyQuotedData) {
		if (get_magic_quotes_gpc()) {
			$sane = array();
			foreach ($potentiallyMagicallyQuotedData as $k => $v) {
				$saneKey = stripslashes($k);
				$saneValue = is_array($v) ? $this->uncharm($v) : stripslashes($v);
				$sane[$saneKey] = $saneValue;
			}
		} else {
			$sane = $potentiallyMagicallyQuotedData;
		}
		return $sane;
	}
}