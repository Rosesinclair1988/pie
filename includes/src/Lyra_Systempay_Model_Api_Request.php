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
 * Class managing parameters checking, form and signature building.
 */
class Lyra_Systempay_Model_Api_Request extends Mage_Core_Model_Abstract {
	/**
	 * The fields to send to the Systempay platform
	 * @var array
	 * @access private
	 */
	private $requestParameters;
	/**
	 * Certificate to send in TEST mode
	 * @var string
	 * @access private
	 */
	private $keyTest;
	/**
	 * Certificate to send in PRODUCTION mode
	 * @var string
	 * @access private
	 */
	private $keyProd;
	/**
	 * Url of the payment page
	 * @var string
	 * @access private
	 */
	private $platformUrl;
	/**
	 * Set to true to send the redirect_* parameters
	 * @var boolean
	 * @access private
	 */
	private $redirectEnabled;
	/**
	 * SHA-1 authentication signature
	 * @var string
	 * @access private
	 */
	private $signature;
	/**
	 * The original data encoding.
	 * @var string
	 * @access private
	 */
	private $encoding;

	/**
	 * Constructor.
	 * Initialize request fields definitions.
	 */
	public function __construct($encoding="UTF-8") {
		// Initialize encoding
		$this->encoding = in_array(strtoupper($encoding), $this->_getPaymentHelper()->getSupportedEncodings()) ? strtoupper($encoding) : "UTF-8";
			
		// Common or long regexes
		$ans = "[^<>]"; // Any character (except the dreadful "<" and ">")
		$an63 = '#^[A-Za-z0-9]{0,63}$#';
		$an255 = '#^[A-Za-z0-9]{0,255}$#';
		$ans255 = '#^' . $ans . '{0,255}$#';
		$ans127 = '#^' . $ans . '{0,127}$#';
		$supzero = '[1-9]\d*';
		$regex_payment_cfg = '#^(SINGLE|MULTI:first=\d+;count=' . $supzero . ';period=' . $supzero . ')$#';
		$regex_trans_date = '#^\d{4}' . '(1[0-2]|0[1-9])' . '(3[01]|[1-2]\d|0[1-9])' . '(2[0-3]|[0-1]\d)' . '([0-5]\d){2}$#';//AAAAMMJJhhmmss
		$regex_mail = '#^[^@]+@[^@]+\.\w{2,4}$#'; //TODO plus restrictif
		$regex_params = '#^([^&=]+=[^&=]*)?(&[^&=]+=[^&=]*)*$#'; //name1=value1&name2=value2...

		// Declaration of all parameters, their labels, their formats, ...
		$this->addRequestField('signature', 'Signature', "#^[0-9a-f]{40}$#", true);
		$this->addRequestField('vads_action_mode', 'Action mode', "#^INTERACTIVE|SILENT$#", true, 11);
		$this->addRequestField('vads_amount', 'Amount', '#^' . $supzero . '$#', true);
		$this->addRequestField('vads_available_languages', 'Available languages', "#^(|[A-Za-z]{2}(;[A-Za-z]{2})*)$#", false, 2);
		$this->addRequestField('vads_capture_delay', 'Capture delay', "#^\d*$#");
		$this->addRequestField('vads_card_number', 'Card number', "#^\d{13,19}$#");
		$this->addRequestField('vads_contracts', 'Contracts', $ans255);
		$this->addRequestField('vads_contrib', 'Contribution', $ans255);
		$this->addRequestField('vads_ctx_mode', 'Mode', "#^TEST|PRODUCTION$#", true);
		$this->addRequestField('vads_currency', 'Currency', "#^\d{3}$#", true, 3);
		$this->addRequestField('vads_cust_antecedents', 'Customer history', "#^NONE|NO_INCIDENT|INCIDENT$#");
		$this->addRequestField('vads_cust_address', 'Customer address', $ans255);
		$this->addRequestField('vads_cust_country', 'Customer country', "#^[A-Za-z]{2}$#", false, 2);
		$this->addRequestField('vads_cust_email', 'Customer email', $regex_mail, false, 127);
		$this->addRequestField('vads_cust_first_name', 'Customer first name', $an63, false, 63);
		$this->addRequestField('vads_cust_id', 'Customer id', $an63, false, 63);
		$this->addRequestField('vads_cust_cell_phone', 'Customer cell phone', $an63, false, 63);
		$this->addRequestField('vads_cust_last_name', 'Customer last name', $an63, false, 63);
		$this->addRequestField('vads_cust_phone', 'Customer phone', $an63, false, 63);
		$this->addRequestField('vads_cust_title', 'Customer title', '#^'.$ans.'{0,63}$#', false, 63);
		$this->addRequestField('vads_cust_city', 'Customer city', '#^' . $ans . '{0,63}$#', false, 63);
		$this->addRequestField('vads_cust_state', 'Customer state/region', '#^'.$ans.'{0,63}$#', false, 63);
		$this->addRequestField('vads_cust_status', 'Customer status (private or company)', "#^PRIVATE|COMPANY$#", false, 7);
		$this->addRequestField('vads_cust_zip', 'Customer zip code', $an63, false, 63);
		$this->addRequestField('vads_cvv', 'Card verification number', "#^\d{3,4}$#");
		$this->addRequestField('vads_expiry_year', 'Year of card expiration', "#^20[0-9]{2}$#");
		$this->addRequestField('vads_expiry_month', 'Month of card expiration', "#^\d[0-2]{1}$#");
		$this->addRequestField('vads_insurance_amount', 'The amount of insurance', '#^' . $supzero . '$#', false, 12);
		$this->addRequestField('vads_language', 'Language', "#^[A-Za-z]{2}$#", false, 2);
		$this->addRequestField('vads_nb_products', 'Number of products', '#^' . $supzero . '$#', false);
		$this->addRequestField('vads_order_id', 'Order id', "#^[A-za-z0-9]{0,12}$#", false, 12);
		$this->addRequestField('vads_order_info', 'Order info', $ans255);
		$this->addRequestField('vads_order_info2', 'Order info 2', $ans255);
		$this->addRequestField('vads_order_info3', 'Order info 3', $ans255);
		$this->addRequestField('vads_page_action', 'Page action', "#^PAYMENT$#", true, 7);
		$this->addRequestField('vads_payment_cards', 'Payment cards', "#^[A-Za-z0-9;]{0,127}$#", false, 127);
		$this->addRequestField('vads_payment_config', 'Payment config', $regex_payment_cfg, true);
		$this->addRequestField('vads_payment_src', 'Payment source', "#^$#", false, 0);
		$this->addRequestField('vads_redirect_error_message', 'Redirection error message', $ans255, false);
		$this->addRequestField('vads_redirect_error_timeout', 'Redirection error timeout', $ans255, false);
		$this->addRequestField('vads_redirect_success_message', 'Redirection success message', $ans255, false);
		$this->addRequestField('vads_redirect_success_timeout', 'Redirection success timeout', $ans255, false);
		$this->addRequestField('vads_return_mode', 'Return mode', "#^NONE|GET|POST?$#", false, 4);
		$this->addRequestField('vads_return_get_params', 'GET return parameters', $regex_params, false);
		$this->addRequestField('vads_return_post_params', 'POST return parameters', $regex_params, false);
		$this->addRequestField('vads_ship_to_delivery_company_name', 'Name of the delivery company', $ans127, false, 127);
		$this->addRequestField('vads_ship_to_first_name', 'Shipping first name', $an63, false, 63);
		$this->addRequestField('vads_ship_to_last_name', 'Shipping last name', $an63, false, 63);
		$this->addRequestField('vads_ship_to_phone_num', 'Shipping phone', $ans255, false, 63);
		$this->addRequestField('vads_ship_to_speed', 'Speed of the shipping method', "#^STANDARD|EXPRESS$#", false, 8);
		$this->addRequestField('vads_ship_to_street', 'Shipping street', $ans127, false, 127);
		$this->addRequestField('vads_ship_to_street2', 'Shipping street (2)', $ans127, false, 127);
		$this->addRequestField('vads_ship_to_state', 'Shipping state', $an63, false, 63);
		$this->addRequestField('vads_ship_to_status', 'Shipping status (private or company)', "#^PRIVATE|COMPANY$#", false, 7);
		$this->addRequestField('vads_ship_to_type', 'Type of the shipping method',
				"#^RECLAIM_IN_SHOP|RELAY_POINT|RECLAIM_IN_STATION|PACKAGE_DELIVERY_COMPANY|ETICKET$#", false, 24);
		$this->addRequestField('vads_ship_to_country', 'Shipping country', "#^[A-Za-z]{2}$#", false, 2);
		$this->addRequestField('vads_ship_to_city', 'Shipping city', '#^' . $ans . '{0,63}$#', false, 63);
		$this->addRequestField('vads_ship_to_zip', 'Shipping zip code', $an63, false, 63);
		$this->addRequestField('vads_shipping_amount', 'The amount of shipping', '#^' . $supzero . '$#', false, 12);
		$this->addRequestField('vads_shop_name', 'Shop name', $ans127);
		$this->addRequestField('vads_shop_url', 'Shop url', $ans127);
		$this->addRequestField('vads_site_id', 'Site id', "#^\d{8}$#", true, 8);
		$this->addRequestField('vads_tax_amount', 'The amount of tax', '#^' . $supzero . '$#', false, 12);
		$this->addRequestField('vads_theme_config', 'Theme', $ans255);
		$this->addRequestField('vads_threeds_mpi', 'Enable / disable 3D Secure', '#^[0-2]$#', false);
		$this->addRequestField('vads_trans_date', 'Transaction date', $regex_trans_date, true, 14);
		$this->addRequestField('vads_trans_id', 'Transaction id', "#^[0-8]\d{5}$#", true, 6);
		$this->addRequestField('vads_url_success', 'Success url', $ans127, false, 127);
		$this->addRequestField('vads_url_referral', 'Referral url', $ans127, false, 127);
		$this->addRequestField('vads_url_refused', 'Refused url', $ans127, false, 127);
		$this->addRequestField('vads_url_cancel', 'Cancel url', $ans127, false, 127);
		$this->addRequestField('vads_url_error', 'Error url', $ans127, false, 127);
		$this->addRequestField('vads_url_return', 'Return url', $ans127, false, 127);
		$this->addRequestField('vads_user_info', 'User info', $ans255);
		$this->addRequestField('vads_validation_mode', 'Validation mode', "#^[01]?$#", false, 1);
		$this->addRequestField('vads_version', 'Gateway version', "#^V2$#", true, 2);
			
		// Set some default parameters
		$this->set('vads_version', 'V2');
		$this->set('vads_page_action', 'PAYMENT');
		$this->set('vads_action_mode', 'INTERACTIVE');
		$this->set('vads_payment_config', 'SINGLE');
		$timestamp = time();
		$this->set('vads_trans_id', $this->_getPaymentHelper()->generateTransId($timestamp));
		$this->set('vads_trans_date', gmdate('YmdHis', $timestamp));
	}

	/**
	 * Shortcut function used in constructor to build requestParameters
	 * @param string $name
	 * @param string $label
	 * @param string $regex
	 * @param boolean $required
	 * @param mixed $value
	 * @return boolean true on success
	 * @access private
	 */
	private function addRequestField($name, $label, $regex, $required = false, $length = 255, $value = null) {
		$this->requestParameters[$name] = new Lyra_Systempay_Model_Api_Field($name, $label, $regex, $required, $length);
			
		if($value !== null) {
			return $this->set($name, $value);
		} else {
			return true;
		}
	}

	/**
	 * Shortcut for setting multiple values with one array
	 * @param array[string]mixed $parameters
	 * @return boolean true on success
	 */
	public function setFromArray($parameters) {
		$ok = true;
		foreach ($parameters as $name => $value) {
			$ok &= $this->set($name, $value);
		}
		return $ok;
	}

	/**
	 * General getter.
	 * Retrieve an api variable from its name. Automatically add 'vads_' to the name if necessary.
	 * Example : <code><?php $siteId = $api->get('site_id'); ?></code>
	 * @param string $name
	 * @return mixed null if $name was not recognised
	 */
	public function get($name) {
		if (!$name || !is_string($name)) {
			return null;
		}

		// V1/shortcut notation compatibility
		$name = (substr($name, 0, 5) != 'vads_') ? 'vads_' . $name : $name;

		if ($name == 'vads_key_test') {
			return $this->keyTest;
		} elseif ($name == 'vads_key_prod') {
			return $this->keyProd;
		} elseif ($name == 'vads_platform_url') {
			return $this->platformUrl;
		} elseif ($name == 'vads_redirect_enabled') {
			return $this->redirectEnabled;
		} elseif (array_key_exists($name, $this->requestParameters)) {
			return $this->requestParameters[$name]->getValue();
		} else {
			return null;
		}
	}

	/**
	 * General setter.
	 * Set an api variable with its name and the provided value. Automatically add 'vads_' to the name if necessary.
	 * Example : <code><?php $api->set('site_id', '12345678'); ?></code>
	 * @param string $name
	 * @param mixed $value
	 * @return boolean true on success
	 */
	public function set($name, $value) {
		if (!$name || !is_string($name)) {
			return false;
		}

		// V1/shortcut notation compatibility
		$name = (substr($name, 0, 5) != 'vads_') ? 'vads_' . $name : $name;

		// Delete < and > characters from $value
		$value = preg_replace("#[<>]+#", "", $value);
		
		// Convert the parameters if they are not encoded in utf8
		if($this->encoding !== "UTF-8") {
			$value = iconv($this->encoding, "UTF-8", $value);
		}
			
		// Search appropriate setter
		if ($name == 'vads_key_test') {
			return $this->setCertificate($value, 'TEST');
		} elseif ($name == 'vads_key_prod') {
			return $this->setCertificate($value, 'PRODUCTION');
		} elseif ($name == 'vads_platform_url') {
			return $this->setPlatformUrl($value);
		} elseif ($name == 'vads_redirect_enabled') {
			return $this->setRedirectEnabled($value);
		} elseif (array_key_exists($name, $this->requestParameters)) {
			return $this->requestParameters[$name]->setValue($value);
		} else {
			return false;
		}
	}

	/**
	 * Set target url of the payment form
	 * @param string $url
	 * @return boolean
	 */
	public function setPlatformUrl($url) {
		if (!preg_match('#https?://([^/]+/)+#', $url)) {
			return false;
		}
		$this->platformUrl = $url;
		return true;
	}

	/**
	 * Get target url of the payment form
	 * @return string
	 */
	public function getPlatformUrl() {
		return $this->platformUrl;
	}
	
	/**
	 * Enable/disable redirect_* parameters
	 * @param mixed $enabled false, '0', a null or negative integer or 'false' to disable
	 * @return boolean
	 */
	public function setRedirectEnabled($enabled) {
		$this->redirectEnabled = ($enabled && $enabled != '0' && strtolower($enabled) != 'false');
		return true;
	}

	/**
	 * Set TEST or PRODUCTION certificate
	 * @param string $key
	 * @param string $mode
	 * @return boolean true if the certificate could be set
	 */
	public function setCertificate($key, $mode) {
		// Check format
		if (!preg_match('#\d{16}#', $key)) {
			return false;
		}

		if ($mode == 'TEST') {
			$this->keyTest = $key;
		} elseif ($mode == 'PRODUCTION') {
			$this->keyProd = $key;
		} else {
			return false;
		}
		return true;
	}
	
	/**
	 * Set multiple payment configuration
	 * 
	 * @param $totalInCents total order amount in cents
	 * @param $firstInCents amount of the first payment in cents
	 * @param $count total number of payments
	 * @param $period number of days between 2 payments
	 * @return boolean true on success
	 */
	public function setMultiPayment($totalInCents = null, $firstInCents = null, $count = 3, $period = 30) {
		$result = false;
			
		if (is_numeric($count) && $count > 1 && is_numeric($period) && $period > 0) {
			// default values for first and total
			$totalInCents = ($totalInCents === null) ? $this->get('amount')	: $totalInCents;
			$firstInCents = ($firstInCents === null) ? round($totalInCents / $count) : $firstInCents;
	
			// parameter check
			if (is_numeric($totalInCents) && is_numeric($firstInCents) && $firstInCents > 0 && $totalInCents > $firstInCents) {
				// payment_config construction
				$paymentConfig = 'MULTI:first=' . $firstInCents . ';count=' . $count . ';period=' . $period;
				$result = $this->set('amount', $totalInCents);
				if ($result === true) {
					// set payment_config
					$result = $this->set('payment_config', $paymentConfig);
				}
			}
		}
	
		return $result;
	}

	/**
	* Add product infos as request parameters.
	* @param string $label
	* @param int $amount
	* @param int $qty
	* @param string $ref
	* @param string $type
	* @return boolean true if product infos are set correctly
	*/
	public function addProductRequestField($label, $amount, $qty, $ref, $type) {
		$index = $this->get("nb_products") ? $this->get("nb_products") : 0;
		
		$accordCategories = array("FOOD_AND_GROCERY", "AUTOMOTIVE", "ENTERTAINMENT", "HOME_AND_GARDEN", "HOME_APPLIANCE", "AUCTION_AND_GROUP_BUYING", "FLOWERS_AND_GIFTS", "COMPUTER_AND_SOFTWARE", "HEALTH_AND_BEAUTY", "SERVICE_FOR_INDIVIDUAL", "SERVICE_FOR_BUSINESS", "SPORTS", "CLOTHING_AND_ACCESSORIES", "TRAVEL", "HOME_AUDIO_PHOTO_VIDEO", "TELEPHONY");
		
		$ok = true;
			
		// Add product infos as request parameters
		$ok &= $this->addRequestField("vads_product_label" . $index, "Product label", '#^[^<>"+-]{0,255}$#', false, 255, $label);
		$ok &= $this->addRequestField("vads_product_amount" . $index, "Product amount", '#^[1-9]\d*$#', false, 12, $amount);
		$ok &= $this->addRequestField("vads_product_qty" . $index, "Product quantity", '#^[1-9]\d*$#', false, 255, $qty);
		$ok &= $this->addRequestField("vads_product_ref" . $index, "Product reference", '#^[A-Za-z0-9]{0,64}$#', false, 64, $ref);
		$ok &= $this->addRequestField("vads_product_type" . $index, "Product type", "#^".implode("|", $accordCategories)."$#",
				false, 30, $type);
			
		// Increment the number of products
		$ok &= $this->set("nb_products", $index + 1);
			
		return $ok;
	}

	/**
	* Add extra info as a request parameter.
	* @param string $key
	* @param string $value
	* @return boolean true if extra info is set correctly
	*/
	public function addExtInfoRequestField($key, $value) {
		return $this->addRequestField("vads_ext_info_" . $key, "Extra info " . $key, '#^.{0,255}$#', false, 255, $value);
	}


	/**
	 * Return certificate according to current mode, false if mode was not set
	 * @return string|boolean
	 */
	public function getCertificate() {
		switch ($this->requestParameters['vads_ctx_mode']->getValue()) {
					case 'TEST':
						return $this->keyTest;
						break;

					case 'PRODUCTION':
						return $this->keyProd;
						break;

					default:
						return false;
						break;
		}
	}

	/**
	 * Generate signature from a list of Lyra_Systempay_Model_Api_Field
	 * @param array[string]Lyra_Systempay_Model_Api_Field $fields
	 * @return string
	 * @access private
	 */
	private function generateSignatureFromFields($fields = null, $hashed = true) {
		$params = array();
		$fields = ($fields !== null) ? $fields : $this->requestParameters;
		foreach ($fields as $field) {
			if ($field->isRequired() || $field->isFilled()) {
				$params[$field->getName()] = $field->getValue();
			}
		}
		return $this->_getPaymentHelper()->sign($params, $this->getCertificate(), $hashed);
	}

	/**
	 * Unset the value of optionnal fields if they are unvalid
	 */
	public function clearInvalidOptionnalFields() {
		$fields = $this->getRequestFields();
		foreach ($fields as $field) {
			if (!$field->isValid() && !$field->isRequired()) {
				$field->setValue(null);
			}
		}
	}

	/**
	 * Check all payment fields
	 * @param array $errors will be filled with the name of invalid fields
	 * @return boolean
	 */
	public function isRequestReady(&$errors = null) {
		$errors = is_array($errors) ? $errors : array();
		$fields = $this->getRequestFields();
		foreach ($fields as $field) {
			if (!$field->isValid()) {
				$errors[] = $field->getName();
			}
		}
		return sizeof($errors) == 0;
	}

	/**
	 * Return the list of fields to send to the payment gateway
	 * @return array a list of Lyra_Systempay_Model_Api_Field or false if a parameter was invalid
	 * @see Lyra_Systempay_Model_Api_Field
	 */
	public function getRequestFields() {
		$fields = $this->requestParameters;

		// Filter redirect_parameters if redirect is disabled
		if (!$this->redirectEnabled) {
			$redirectFields = array(
					'vads_redirect_success_timeout',
					'vads_redirect_success_message',
					'vads_redirect_error_timeout',
					'vads_redirect_error_message');
			foreach ($redirectFields as $fieldName) {
				unset($fields[$fieldName]);
			}
		}

		foreach ($fields as $fieldName => $field) {
			if (!$field->isFilled() && !$field->isRequired()) {
				unset($fields[$fieldName]);
			}
		}

		// Compute signature
		$fields['signature']->setValue($this->generateSignatureFromFields($fields));

		// Return the list of fields
		return $fields;
	}

	/**
	 * Return the url of the payment page with urlencoded parameters (GET-like url)
	 * @return boolean|string
	 */
	public function getRequestUrl() {
		$fields = $this->getRequestFields();

		$url = $this->platformUrl . '?';
		foreach ($fields as $field) {
			if ($field->isFilled()) {
				$url .= $field->getName() . '=' . rawurlencode($field->getValue()) . '&';
			}
		}
		$url = substr($url, 0, -1); // remove last &
		return $url;
	}

	/**
	 * Return the html form to send to the payment gateway
	 * @param string $enteteAdd
	 * @param string $inputType
	 * @param string $buttonValue
	 * @param string $buttonAdd
	 * @param string $buttonType
	 * @return string
	 */
	public function getRequestHtmlForm($enteteAdd = '', $inputType = 'hidden',
			$buttonValue = 'Aller sur la plateforme de paiement', $buttonAdd = '',
			$buttonType = 'submit') {

		$html = "";
		$html .= '<form action="' . $this->platformUrl . '" method="POST" ' . $enteteAdd . '>';
		$html .= "\n";
		$html .= $this->getRequestFieldsHtml('type="' . $inputType . '"');
		$html .= '<input type="' . $buttonType . '" value="' . $buttonValue . '" ' . $buttonAdd . '/>';
		$html .= "\n";
		$html .= '</form>';
		return $html;
	}

	/**
	 * Return the html code of the form fields to send to the payment page
	 * @param string $inputAttributes
	 * @return string
	 */
	public function getRequestFieldsHtml($inputAttributes = 'type="hidden"') {
		$fields = $this->getRequestFields();
			
		$html = '';
		$format = '<input name="%s" value="%s" ' . $inputAttributes . "/>\n";
		foreach ($fields as $field) {
			if ($field->isFilled()) {
				// Convert special chars to HTML entities to avoid data troncation
				$value = htmlspecialchars($field->getValue(), ENT_QUOTES, 'UTF-8');
					
				$html .= sprintf($format, $field->getName(), $value);
			}
		}
		return $html;
	}

	/**
	 * Return the html fields to send to the payment page as a key/value array
	 * @return array[string][string]
	 */
	public function getRequestFieldsArray() {
		$fields = $this->getRequestFields();

		$result = array();
		foreach ($fields as $field) {
			if ($field->isFilled()) {
				// Convert special chars to HTML entities to avoid data troncation
				$result[$field->getName()] = htmlspecialchars($field->getValue(), ENT_QUOTES, 'UTF-8');
			}
		}
			
		return $result;
	}

	private function _getPaymentHelper() {
		return Mage::helper('systempay/payment');
	}
}