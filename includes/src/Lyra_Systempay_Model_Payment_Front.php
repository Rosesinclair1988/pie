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

abstract class Lyra_Systempay_Model_Payment_Front extends Mage_Payment_Model_Method_Abstract {
	protected $_infoBlockType = 'systempay/info';
	
	protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canCapturePartial = true;
	protected $_canRefund = false;
	protected $_canRefundInvoicePartial = false;
	protected $_canVoid = true;
	protected $_canUseForMultishipping = false;
	protected $_canUseInternal = false;
	protected $_canUseCheckout = true;
	protected $_isInitializeNeeded = true;
	
	/** 
	 * Default logo on payment method selection page. 
	 * Image must be located at skin/[your_theme]/default/images/systempay directory.
	 */
	protected $_logo = false;
	
	protected $_systempayRequest = null;
	
	public function __construct() {
		parent::__construct();
		
		$this->_systempayRequest = new Lyra_Systempay_Model_Api_Request();
	}
	
	/**
	 * @param Mage_Sales_Model_Order $order
	 * @return <string:mixed> array of params as key=>value
	 */
	public function getFormFields($order) {
		// Set order_id
		$this->_systempayRequest->set('order_id', $order->getIncrementId());
		
		// Amount in current order currency
		$amount = $order->getGrandTotal();
		
		// Set currency
		$currency = $this->_getPaymentHelper()->findCurrencyByAlphaCode($order->getOrderCurrencyCode());
		if($currency == null) {
			// If currency is not supported, use base currency 
			$currency = $this->_getPaymentHelper()->findCurrencyByAlphaCode($order->getBaseCurrencyCode());
			
			// ... and order total in base currency
			$amount = $order->getBaseGrandTotal();
		}
		$this->_systempayRequest->set('currency', $currency->getNum());
				 
		// Set the amount to pay
		$this->_systempayRequest->set('amount', $currency->convertAmountToInteger($amount));
		
		$this->_systempayRequest->set('contrib', 'Magento1.5.1.0_1.2/' . Mage::getVersion());
		 
		// Set config parameters
		$configFields = array('key_test', 'key_prod', 'capture_delay', 'ctx_mode', 'site_id', 'validation_mode', 
				'shop_name', 'shop_url', 'theme_config', 'return_mode', 'redirect_enabled', 'redirect_success_timeout',
				'redirect_success_message',	'redirect_error_timeout','redirect_error_message'
		);
		foreach($configFields as $field) {
			$this->_systempayRequest->set($field, $this->_getHelper()->getConfigData($field));
		}
		
		// Set return url (build it if only path has been configured)
		$this->_systempayRequest->set('url_return', $this->_getHelper()->prepareUrl('systempay/payment/return', $order->getStore()->getId()));
		 
		// Set the language code
		$currentLang = substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2);
		if(in_array($currentLang, $this->_getPaymentHelper()->getSupportedLanguages())) {
			$this->_systempayRequest->set('language', $currentLang);
		} else {
			$this->_systempayRequest->set('language', $this->_getHelper()->getConfigData('language'));
		}
		 
		// available_languages is given as csv by magento
		$availableLanguages = explode(',', $this->_getHelper()->getConfigData('available_languages'));
		$availableLanguages = in_array('', $availableLanguages) ? '' : implode(';', $availableLanguages);
		$this->_systempayRequest->set('available_languages', $availableLanguages);
		
		// $payment_cards is given as csv by magento
		$paymentCards = explode(',', $this->_getHelper()->getConfigData('payment_cards'));
		$paymentCards = in_array('', $paymentCards) ? '' : implode(';', $paymentCards);
		$this->_systempayRequest->set('payment_cards', $paymentCards);
		
		// activate 3ds ?
		$threedsMpi = '';
		$threedsMinAmount = $this->_getHelper()->getConfigData('threeds_min_amount');
		if($threedsMinAmount != '' && $order->getTotalDue() < $threedsMinAmount) {
			$threedsMpi = '2';
		}
		$this->_systempayRequest->set('threeds_mpi', $threedsMpi);
		 
		$this->_systempayRequest->set('cust_email', $order->getCustomerEmail());
		$this->_systempayRequest->set('cust_id', $order->getCustomerId());
		$this->_systempayRequest->set('cust_title', $order->getBillingAddress()->getPrefix() ? $order->getBillingAddress()->getPrefix() : null);
		$this->_systempayRequest->set('cust_first_name', $order->getBillingAddress()->getFirstname());
		$this->_systempayRequest->set('cust_last_name', $order->getBillingAddress()->getLastname());
		$this->_systempayRequest->set('cust_address', $order->getBillingAddress()->getStreet(1) . ' ' . $order->getBillingAddress()->getStreet(2));
		$this->_systempayRequest->set('cust_zip', $order->getBillingAddress()->getPostcode());
		$this->_systempayRequest->set('cust_city', $order->getBillingAddress()->getCity());
		$this->_systempayRequest->set('cust_state', $order->getBillingAddress()->getRegion());
		$this->_systempayRequest->set('cust_country', $order->getBillingAddress()->getCountryId());
		$this->_systempayRequest->set('cust_phone', $order->getBillingAddress()->getTelephone());
		 
		$address = $order->getShippingAddress();
		if(is_object($address)) { // shipping is supported
			$this->_systempayRequest->set('ship_to_first_name', $address->getFirstname());
			$this->_systempayRequest->set('ship_to_last_name', $address->getLastname());
			$this->_systempayRequest->set('ship_to_city', $address->getCity());
			$this->_systempayRequest->set('ship_to_street', $address->getStreet(1));
			$this->_systempayRequest->set('ship_to_street2', $address->getStreet(2));
			$this->_systempayRequest->set('ship_to_state', $address->getRegion());
			$this->_systempayRequest->set('ship_to_country', $address->getCountryId());
			$this->_systempayRequest->set('ship_to_phone_num', $address->getTelephone());
			$this->_systempayRequest->set('ship_to_zip', $address->getPostcode());
		}
		
		// set method-specific parameters
		$this->_setExtraFields($order);
		
		$params = $this->_systempayRequest->getRequestFieldsArray();
		$this->_getHelper()->log('Payment parameters : ' . print_r($params, true), Zend_Log::INFO);
		
		return $params;
	}
	
	abstract protected function _setExtraFields($order);
	
	/**
	 * Return the payment platform url
	 * 
	 * @return string
	 */
	public function getPlatformUrl() {
		return $this->_getHelper()->getConfigData('platform_url');
	}
	
	/**
	 *  The url the customer is redirected to after clicking on "Confirm order"
	 *
	 *  @return	  string Order Redirect URL
	 */
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('systempay/payment/form', array('_secure' => true));
	}
	
	/**
	 *  Retuns the logo to show when method is listed in checkout page
	 *
	 *  @return string the logo image name
	 */
	public function getLogo() {
		return $this->_logo;
	}
	
	/**
	 * Method that will be executed instead of authorize or capture
	 * if flag isInitializeNeeded set to true
	 *
	 * @param string $paymentAction
	 * @param object $stateObject
	 *
	 * @return Mage_Payment_Model_Abstract
	 */
	public function initialize($paymentAction, $stateObject) {
		// this method is intentionally let empty
	
		return $this;
	}
	
	/**
	 * Check method for processing with base currency
	 *
	 * @param string $currencyCode
	 * @return boolean
	 */
	public function canUseForCurrency($currencyCode) {
		// Check currency support
		$currency = $this->_getPaymentHelper()->findCurrencyByAlphaCode($currencyCode);
		if($currency == null) {
			$this->_getHelper()->log("Could not find currency numeric code for currency : " . $currencyCode);
			return false;
		}
	
		return true;
	}
	
	/**
	 * Return systempay data helper.
	 *
	 * @return Lyra_Systempay_Helper_Data
	 */
	protected function _getHelper() {
		return Mage::helper('systempay');
	}
	
	/**
	 * Return systempay payment method helper.
	 *
	 * @return Mage_Systempay_Helper_Payment
	 */
	protected function _getPaymentHelper() {
		return Mage::helper('systempay/payment');
	}
	
}