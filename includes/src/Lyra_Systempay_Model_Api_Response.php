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
 * Class representing the result of a transaction (sent by the check url or by the client return)
 */
class Lyra_Systempay_Model_Api_Response extends Mage_Core_Model_Abstract {
	/**
	 * Raw response parameters array
	 * @var array
	 * @access private
	 */
	private $rawResponse = array();
	/**
	 * Certificate used to check the signature
	 * @see SystempayApi::sign
	 * @var boolean
	 * @access private
	 */
	private $certificate;
	/**
	 * Value of vads_result
	 * @var string
	 * @access private
	 */
	private $code;
	/**
	 * Translation of $code (vads_result)
	 * @var string
	 * @access private
	 */
	private $message;
	/**
	 * Value of vads_extra_result
	 * @var string
	 * @access private
	 */
	private $extraCode;
	/**
	 * Translation of $extraCode (vads_extra_result)
	 * @var string
	 * @access private
	 */
	private $extraMessage;
	/**
	 * Value of vads_auth_result
	 * @var string
	 * @access private
	 */
	private $authCode;
	/**
	 * Translation of $authCode (vads_auth_result)
	 * @var string
	 * @access private
	 */
	private $authMessage;
	/**
	 * Value of vads_warranty_result
	 * @var string
	 * @access private
	 */
	private $warrantyCode;
	/**
	 * Translation of $warrantyCode (vads_warranty_result)
	 * @var string
	 * @access private
	 */
	private $warrantyMessage;

	/**
	 * Associative array containing human-readable translations of response codes. Initialized to french translations.
	 * @var array
	 * @access private
	 */
	private static $translations = array(
			'no_code' => '',
			'no_translation' => '',
			'results' => array(
					'00' => 'Paiement réalisé avec succès',
					'02' => 'Le commerçant doit contacter la banque du porteur',
					'05' => 'Paiement refusé',
					'17' => 'Annulation client',
					'30' => 'Erreur de format de la requête',
					'96' => 'Erreur technique lors du paiement'
			),
			'extra_results_default' => array(
					'empty' => 'Pas de contrôle effectué',
					'00' => 'Tous les contrôles se sont déroulés avec succès',
					'02' => 'La carte a dépassé l’encours autorisé',
					'03' => 'La carte appartient à la liste grise du commerçant',
					'04' => 'Le pays d’émission de la carte appartient à la liste grise du commerçant',
					'05' => 'L’adresse IP appartient à la liste grise du commerçant',
					'06' => 'Le code BIN appartient à la liste grise du commerçant',
					'07' => 'Détection d\'une e-carte bleue',
					'08' => 'Détection d\'une carte commerciale nationale',
					'09' => 'Détection d\'une carte commerciale étrangère',
					'14' => 'La carte est une carte à autorisation systématique',
					'20' => 'Aucun pays ne correspond (pays IP, pays carte, pays client)',
					'99' => 'Problème technique rencontré par le serveur lors du traitement d’un des contrôles locaux'),
					'extra_results_30' => array(
							'00' => 'signature',
							'01' => 'version',
							'02' => 'merchant_site_id',
							'03' => 'transaction_id',
							'04' => 'date',
							'05' => 'validation_mode',
							'06' => 'capture_delay',
							'07' => 'config',
							'08' => 'payment_cards',
							'09' => 'amount',
							'10' => 'currency',
							'11' => 'ctx_mode',
							'12' => 'language',
							'13' => 'order_id',
							'14' => 'order_info',
							'15' => 'cust_email',
							'16' => 'cust_id',
							'17' => 'cust_title',
							'18' => 'cust_name',
							'19' => 'cust_address',
							'20' => 'cust_zip',
							'21' => 'cust_city',
							'22' => 'cust_country',
							'23' => 'cust_phone',
							'24' => 'url_success',
							'25' => 'url_refused',
							'26' => 'url_referral',
							'27' => 'url_cancel',
							'28' => 'url_return',
							'29' => 'url_error',
							'30' => 'identifier',
							'31' => 'contrib',
							'32' => 'theme_config',
							'34' => 'redirect_success_timeout',
							'35' => 'redirect_success_message',
							'36' => 'redirect_error_timeout',
							'37' => 'redirect_error_message',
							'38' => 'return_post_params',
							'39' => 'return_get_params',
							'40' => 'card_number',
							'41' => 'expiry_month',
							'42' => 'expiry_year',
							'43' => 'card_cvv',
							'44' => 'card_info',
							'45' => 'card_options',
							'46' => 'page_action',
							'47' => 'action_mode',
							'48' => 'return_mode',
							'50' => 'secure_mpi',
							'51' => 'secure_enrolled',
							'52' => 'secure_cavv',
							'53' => 'secure_eci',
							'54' => 'secure_xid',
							'55' => 'secure_cavv_alg',
							'56' => 'secure_status',
							'60' => 'payment_src',
							'61' => 'user_info',
							'62' => 'contracts',
							'70' => 'empty_params',
							'99' => 'other'
					),
					'auth_results' => array(
							'00' => 'transaction approuvée ou traitée avec succès',
							'02' => 'contacter l’émetteur de carte',
							'03' => 'accepteur invalide',
							'04' => 'conserver la carte',
							'05' => 'ne pas honorer',
							'07' => 'conserver la carte, conditions spéciales',
							'08' => 'approuver après identification',
							'12' => 'transaction invalide',
							'13' => 'montant invalide',
							'14' => 'numéro de porteur invalide',
							'30' => 'erreur de format',
							'31' => 'identifiant de l’organisme acquéreur inconnu',
							'33' => 'date de validité de la carte dépassée',
							'34' => 'suspicion de fraude',
							'41' => 'carte perdue',
							'43' => 'carte volée',
							'51' => 'provision insuffisante ou crédit dépassé',
							'54' => 'date de validité de la carte dépassée',
							'56' => 'carte absente du fichier',
							'57' => 'transaction non permise à ce porteur',
							'58' => 'transaction interdite au terminal',
							'59' => 'suspicion de fraude',
							'60' => 'l’accepteur de carte doit contacter l’acquéreur',
							'61' => 'montant de retrait hors limite',
							'63' => 'règles de sécurité non respectées',
							'68' => 'réponse non parvenue ou reçue trop tard',
							'90' => 'arrêt momentané du système',
							'91' => 'émetteur de cartes inaccessible',
							'96' => 'mauvais fonctionnement du système',
							'94' => 'transaction dupliquée',
							'97' => 'échéance de la temporisation de surveillance globale',
							'98' => 'serveur indisponible routage réseau demandé à nouveau',
							'99' => 'incident domaine initiateur'
					),
					'warranty_results' => array(
							'YES' => 'Le paiement est garanti',
							'NO' => 'Le paiement n\'est pas garanti',
							'UNKNOWN' => 'Suite à une erreur technique, le paiment ne peut pas être garanti'
					)
			);

	/**
	 * Constructor for SystempayResponse class. Prepare to analyse check url or return url call.
	 * @param array[string]string $parameters $_REQUEST by default
	 * @param string $ctx_mode
	 * @param string $key_test
	 * @param string $key_prod
	 * @param string $encoding
	 */
	public function __construct($ctxMode, $keyTest, $keyProd, $parameters = null) {
		$params = $this->getPaymentHelper()->uncharm(is_null($parameters) ? $_REQUEST : $parameters);
		
		// Load site credentials 
		$key = null;
		
		if($ctxMode == 'TEST') {
			$key = $keyTest;
		} elseif($ctxMode == 'PRODUCTION') {
			$key = $keyProd;
		}
		
		$this->initialize($params, $key);
	}

	/**
	 * Load response codes and translations from a parameter array.
	 * @param array[string]string $raw
	 * @param boolean $authentified
	 */
	private function initialize($params, $key) {
		$this->rawResponse = is_array($params) ? $params : array();
		$this->certificate = $key;

		// Get codes
		$code = $this->findInArray('vads_result', $this->rawResponse, null);
		$extraCode = $this->findInArray('vads_extra_result', $this->rawResponse, null);
		$authCode = $this->findInArray('vads_auth_result', $this->rawResponse, null);
		$warrantyCode = $this->findInArray('vads_warranty_code', $this->rawResponse, null);

		// Common translations
		$noCode = self::$translations['no_code'];
		$noTrans = self::$translations['no_translation'];

		// Result and extra result
		if ($code == null) {
			$message = $noCode;
			$extraMessage = ($extraCode == null) ? $noCode : $noTrans;
		} else {
			$message = $this->findInArray($code, self::$translations['results'], $noTrans);

			if ($extraCode == null) {
				$extraMessage = $noCode;
			} elseif ($code == 30) {
				$extraMessage = $this->findInArray($extraCode, self::$translations['extra_results_30'], $noTrans);
			} else {
				$extraMessage = $this->findInArray($extraCode, self::$translations['extra_results_default'], $noTrans);
			}
		}

		// auth_result
		if ($authCode == null) {
			$authMessage = $noCode;
		} else {
			$authMessage = $this->findInArray($authCode, self::$translations['auth_results'], $noTrans);
		}

		// warranty_result
		if ($warrantyCode == null) {
			$warrantyMessage = $noCode;
		} else {
			$warrantyMessage = $this->findInArray($warrantyCode, self::$translations['warranty_results'], $noTrans);
		}

		$this->code = $code;
		$this->message = $message;
		$this->authCode = $authCode;
		$this->authMessage = $authMessage;
		$this->extraCode = $extraCode;
		$this->extraMessage = $extraMessage;
		$this->warrantyCode = $warrantyCode;
		$this->warrantyMessage = $warrantyMessage;
	}

	/**
	 * Check response signature
	 * @return boolean
	 */
	public function isAuthentified() {
		return $this->getPaymentHelper()->sign($this->rawResponse, $this->certificate) == $this->getSignature();
	}

	/**
	 * Return the signature computed from the received parameters, for log/debug purposes.
	 * @param boolean $hashed apply sha1, false by default
	 * @return string
	 */
	public function getComputedSignature($hashed = false) {
		return $this->getPaymentHelper()->sign($this->rawResponse, $this->certificate, $hashed);
	}

	/**
	 * Check if the payment was successful (waiting confirmation or captured)
	 * @return boolean
	 */
	public function isAcceptedPayment() {
		return $this->code == '00';
	}

	/**
	 * Check if the payment is waiting confirmation (successful but the amount has not been transfered and is not yet guaranteed)
	 * @return boolean
	 */
	public function isPendingPayment() {
		return $this->get('auth_mode') == 'MARK';
	}

	/**
	 * Check if the payment process was interrupted by the client
	 * @return boolean
	 */
	public function isCancelledPayment() {
		return $this->code == '17';
	}
	
	/**
	 * Return the payment response message.
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}
	
	/**
	 * Return the payment response code.
	 * @return string
	 */
	public function getCode() {
		return $this->code;
	}
	

	/**
	 * Return the value of a response parameter.
	 * @param string $name
	 * @return string
	 */
	public function get($name) {
		// Manage shortcut notations by adding 'vads_'
		$name = (substr($name, 0, 5) != 'vads_') ? 'vads_' . $name : $name;
			
		return @$this->rawResponse[$name];
	}

	/**
	* Shortcut for getting ext_info_* fields.
	* @param string $key
	* @return string
	*/
	public function getExtInfo($key) {
		return $this->get("ext_info_$key");
	}

	/**
	* Returns the expected signature received from gateway.
	* @return string
	*/
	public function getSignature() {
		return @$this->rawResponse['signature'];
	}

	/**
	 * Return the paid amount converted from cents (or currency equivalent) to a decimal value
	 * @return float
	 */
	public function getFloatAmount() {
		$currency = $this->getPaymentHelper()->findCurrencyByNumCode($this->get('currency'));
		return $currency->convertAmountToFloat($this->get('amount'));
	}

	/**
	 * Return a short description of the payment result, useful for logging
	 * @return string
	 */
	function getLogString() {
		$log = $this->code . ' : ' . $this->message;
		if ($this->code == '30') {
			$log .= ' (' . $this->extraCode . ' : ' . $this->extraMessage . ')';
		}
		return $log;
	}

	/**
	 * Return a formatted string to output as a response to the check url call
	 * @param string $case shortcut code for current situations. Most useful : payment_ok, payment_ko, auth_fail
	 * @param string $extraMessage some extra information to output to the payment gateway
	 * @return string
	 */
	public function getOutputForGateway($case = '', $extraMessage = '', $originalEncoding="UTF-8") {
		$success = false;
		$message = '';

		// Messages prédéfinis selon le cas
		$cases = array(
				'payment_ok' => array(true, 'Paiement valide traité'),
				'payment_ko' => array(true, 'Paiement invalide traité'),
				'payment_ok_already_done' => array(true, 'Paiement valide traité, déjà enregistré'),
				'payment_ko_already_done' => array(true, 'Paiement invalide traité, déjà enregistré'),
				'order_not_found' => array(false, 'Impossible de retrouver la commande'),
				'payment_ko_on_order_ok' => array(false, 'Code paiement invalide reçu pour une commande déjà validée'),
				'auth_fail' => array(false, 'Echec authentification'),
				'ok' => array(true, ''),
				'ko' => array(false, ''));

		if (array_key_exists($case, $cases)) {
			$success = $cases[$case][0];
			$message = $cases[$case][1];
		}

		$message .= ' ' . $extraMessage;
		$message = str_replace("\n", '', $message);
			
		// Set original CMS encoding to convert if necessary response to send to platform
		$encoding = in_array(strtoupper($originalEncoding), $this->getPaymentHelper()->getSupportedEncodings()) ? strtoupper($originalEncoding) : "UTF-8";
			
		if($encoding !== "UTF-8") {
			$message = iconv($encoding, "UTF-8", $message);
		}

		$response = '';
		$response .= '<span style="display:none">';
		$response .= $success ? "OK-" : "KO-";
		$response .= $this->get('trans_id');
		$response .= ($message === ' ') ? "\n" : "=$message\n";
		$response .= '</span>';
		return $response;
	}

	/**
	 * Search the value correspending to key in the given array. Return $default if nothing found.
	 * @param string $key
	 * @param array[string]string $array
	 * @param string $default
	 * @return the value correspending to key in array or defaut
	 */
	private function findInArray($key, $array, $default) {
		if (is_array($array) && array_key_exists($key, $array)) {
			return $array[$key];
		}
		return $default;
	}
	
	private function getPaymentHelper() {
		return Mage::helper('systempay/payment');
	}
}