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

class Lyra_Systempay_Helper_Data extends Mage_Core_Helper_Abstract {
	
	public function getConfigData($field, $storeId=null) {
		$model = Mage::getModel('systempay/payment_standard');
		
		return $model->getConfigData($field, $storeId);
	}
	
	/**
	 * Returns a configuration parameter from xml files
	 * @param $name the name of the parameter to retrieve
	 * @return array code=>name
	 */
	public function getConfigArray($name="") {
		$result = array();
	
		$xml_node = 'global/payment/systempay/'.$name;
		foreach(Mage::getConfig()->getNode($xml_node)->asArray() as $xml_data) {
			$result[$xml_data['code']] = $xml_data['name'];
		}
		
		return $result;
	}
	
	/**
	 *  Return user's IP Address
	 *  @return	  string
	 */
	public function getIpAddress() {
		if (isset($_SERVER)) {
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
		} else {
			if (getenv('HTTP_X_FORWARDED_FOR')) {
				$ip = getenv('HTTP_X_FORWARDED_FOR');
			} elseif (getenv('HTTP_CLIENT_IP')) {
				$ip = getenv('HTTP_CLIENT_IP');
			} else {
				$ip = getenv('REMOTE_ADDR');
			}
		}
		return $ip;
	}
	
	/**
	 * Returns the url matching the $url argument. If $url is not already an absolute url, treats it as a magento path.
	 * @param string $url
	 * @param int $store_id
	 */
	public function prepareUrl($url, $store_id) {
		$result = '';
		// Preserve absolute urls
		if(strpos($url, 'http') === 0) {
			$result = $url;
		} else {
			// transform path to url
			$result = Mage::getUrl($url, array('_nosid'=>true, '_secure'=>true,'_store'=>$store_id));
		}
		
		$this->log('Complete URL is ' . $result);
		
		return $result;
	}
	
	public function updateOptionModelConfig($count) {
		Mage::getConfig()->saveConfig('payment/systempay_multi_' . $count . 'x/model', 'systempay/payment_multix');
	}
	
	/**
	 * Log function. Uses Mage::log with built-in extra data (module version, method called...)
	 * @param $message
	 * @param $level
	 */
	public function log($message, $level=null) {
		$currentMethod = $this->getCallerMethod();
		 
		if (!Mage::getStoreConfig('dev/log/active')) {
			return;
		}
	
		$log  = '';
		$log .= 'Systempay 1.2';
		$log .= ' - ' . $currentMethod;
		$log .= ' : ' . $message;
		
		Mage::log($log, $level, 'systempay.log');
	}
	
	/**
	 * Find the name of the method that called the log method.
	 * @return the name of the method that is logging a message
	 */
	private function getCallerMethod() {
		$traces = debug_backtrace();
	
		if (isset($traces[2])) {
			return $traces[2]['function'];
		}
	
		return null;
	}
}