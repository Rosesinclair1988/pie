<?php

/*
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 * The first PHP Library to support OAuth for Twitter's REST API.
 */

/* Load OAuth lib. You can find it at http://oauth.net */
// vim: foldmethod=marker

/* Generic exception class
 */
//class OAuthException extends Exception {
//  // pass
//}

class OAuthConsumer {
  public $key;
  public $secret;

  function __construct($key, $secret, $callback_url=NULL) {
    $this->key = $key;
    $this->secret = $secret;
    $this->callback_url = $callback_url;
  }

  function __toString() {
    return "OAuthConsumer[key=$this->key,secret=$this->secret]";
  }
}

class OAuthToken {
  // access tokens and request tokens
  public $key;
  public $secret;

  /**
   * key = the token
   * secret = the token secret
   */
  function __construct($key, $secret) {
    $this->key = $key;
    $this->secret = $secret;
  }

  /**
   * generates the basic string serialization of a token that a server
   * would respond to request_token and access_token calls with
   */
  function to_string() {
    return "oauth_token=" .
           OAuthUtil::urlencode_rfc3986($this->key) .
           "&oauth_token_secret=" .
           OAuthUtil::urlencode_rfc3986($this->secret);
  }

  function __toString() {
    return $this->to_string();
  }
}

/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
abstract class OAuthSignatureMethod {
  /**
   * Needs to return the name of the Signature Method (ie HMAC-SHA1)
   * @return string
   */
  abstract public function get_name();

  /**
   * Build up the signature
   * NOTE: The output of this function MUST NOT be urlencoded.
   * the encoding is handled in OAuthRequest when the final
   * request is serialized
   * @param OAuthRequest $request
   * @param OAuthConsumer $consumer
   * @param OAuthToken $token
   * @return string
   */
  abstract public function build_signature($request, $consumer, $token);

  /**
   * Verifies that a given signature is correct
   * @param OAuthRequest $request
   * @param OAuthConsumer $consumer
   * @param OAuthToken $token
   * @param string $signature
   * @return bool
   */
  public function check_signature($request, $consumer, $token, $signature) {
    $built = $this->build_signature($request, $consumer, $token);
    return $built == $signature;
  }
}

/**
 * The HMAC-SHA1 signature method uses the HMAC-SHA1 signature algorithm as defined in [RFC2104] 
 * where the Signature Base String is the text and the key is the concatenated values (each first 
 * encoded per Parameter Encoding) of the Consumer Secret and Token Secret, separated by an '&' 
 * character (ASCII code 38) even if empty.
 *   - Chapter 9.2 ("HMAC-SHA1")
 */
class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {
  function get_name() {
    return "HMAC-SHA1";
  }

  public function build_signature($request, $consumer, $token) {
    $base_string = $request->get_signature_base_string();
    $request->base_string = $base_string;

    $key_parts = array(
      $consumer->secret,
      ($token) ? $token->secret : ""
    );

    $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
    $key = implode('&', $key_parts);

    return base64_encode(hash_hmac('sha1', $base_string, $key, true));
  }
}

/**
 * The PLAINTEXT method does not provide any security protection and SHOULD only be used 
 * over a secure channel such as HTTPS. It does not use the Signature Base String.
 *   - Chapter 9.4 ("PLAINTEXT")
 */
class OAuthSignatureMethod_PLAINTEXT extends OAuthSignatureMethod {
  public function get_name() {
    return "PLAINTEXT";
  }

  /**
   * oauth_signature is set to the concatenated encoded values of the Consumer Secret and 
   * Token Secret, separated by a '&' character (ASCII code 38), even if either secret is 
   * empty. The result MUST be encoded again.
   *   - Chapter 9.4.1 ("Generating Signatures")
   *
   * Please note that the second encoding MUST NOT happen in the SignatureMethod, as
   * OAuthRequest handles this!
   */
  public function build_signature($request, $consumer, $token) {
    $key_parts = array(
      $consumer->secret,
      ($token) ? $token->secret : ""
    );

    $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
    $key = implode('&', $key_parts);
    $request->base_string = $key;

    return $key;
  }
}

/**
 * The RSA-SHA1 signature method uses the RSASSA-PKCS1-v1_5 signature algorithm as defined in 
 * [RFC3447] section 8.2 (more simply known as PKCS#1), using SHA-1 as the hash function for 
 * EMSA-PKCS1-v1_5. It is assumed that the Consumer has provided its RSA public key in a 
 * verified way to the Service Provider, in a manner which is beyond the scope of this 
 * specification.
 *   - Chapter 9.3 ("RSA-SHA1")
 */
abstract class OAuthSignatureMethod_RSA_SHA1 extends OAuthSignatureMethod {
  public function get_name() {
    return "RSA-SHA1";
  }

  // Up to the SP to implement this lookup of keys. Possible ideas are:
  // (1) do a lookup in a table of trusted certs keyed off of consumer
  // (2) fetch via http using a url provided by the requester
  // (3) some sort of specific discovery code based on request
  //
  // Either way should return a string representation of the certificate
  protected abstract function fetch_public_cert(&$request);

  // Up to the SP to implement this lookup of keys. Possible ideas are:
  // (1) do a lookup in a table of trusted certs keyed off of consumer
  //
  // Either way should return a string representation of the certificate
  protected abstract function fetch_private_cert(&$request);

  public function build_signature($request, $consumer, $token) {
    $base_string = $request->get_signature_base_string();
    $request->base_string = $base_string;

    // Fetch the private key cert based on the request
    $cert = $this->fetch_private_cert($request);

    // Pull the private key ID from the certificate
    $privatekeyid = openssl_get_privatekey($cert);

    // Sign using the key
    $ok = openssl_sign($base_string, $signature, $privatekeyid);

    // Release the key resource
    openssl_free_key($privatekeyid);

    return base64_encode($signature);
  }

  public function check_signature($request, $consumer, $token, $signature) {
    $decoded_sig = base64_decode($signature);

    $base_string = $request->get_signature_base_string();

    // Fetch the public key cert based on the request
    $cert = $this->fetch_public_cert($request);

    // Pull the public key ID from the certificate
    $publickeyid = openssl_get_publickey($cert);

    // Check the computed signature against the one passed in the query
    $ok = openssl_verify($base_string, $decoded_sig, $publickeyid);

    // Release the key resource
    openssl_free_key($publickeyid);

    return $ok == 1;
  }
}

class OAuthRequest {
  private $parameters;
  private $http_method;
  private $http_url;
  // for debug purposes
  public $base_string;
  public static $version = '1.0';
  public static $POST_INPUT = 'php://input';

  function __construct($http_method, $http_url, $parameters=NULL) {
    @$parameters or $parameters = array();
    $parameters = array_merge( OAuthUtil::parse_parameters(parse_url($http_url, PHP_URL_QUERY)), $parameters);
    $this->parameters = $parameters;
    $this->http_method = $http_method;
    $this->http_url = $http_url;
  }


  /**
   * attempt to build up a request from what was passed to the server
   */
  public static function from_request($http_method=NULL, $http_url=NULL, $parameters=NULL) {
    $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
              ? 'http'
              : 'https';
    @$http_url or $http_url = $scheme .
                              '://' . $_SERVER['HTTP_HOST'] .
                              ':' .
                              $_SERVER['SERVER_PORT'] .
                              $_SERVER['REQUEST_URI'];
    @$http_method or $http_method = $_SERVER['REQUEST_METHOD'];

    // We weren't handed any parameters, so let's find the ones relevant to
    // this request.
    // If you run XML-RPC or similar you should use this to provide your own
    // parsed parameter-list
    if (!$parameters) {
      // Find request headers
      $request_headers = OAuthUtil::get_headers();

      // Parse the query-string to find GET parameters
      $parameters = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);

      // It's a POST request of the proper content-type, so parse POST
      // parameters and add those overriding any duplicates from GET
      if ($http_method == "POST"
          && @strstr($request_headers["Content-Type"],
                     "application/x-www-form-urlencoded")
          ) {
        $post_data = OAuthUtil::parse_parameters(
          file_get_contents(self::$POST_INPUT)
        );
        $parameters = array_merge($parameters, $post_data);
      }

      // We have a Authorization-header with OAuth data. Parse the header
      // and add those overriding any duplicates from GET or POST
      if (@substr($request_headers['Authorization'], 0, 6) == "OAuth ") {
        $header_parameters = OAuthUtil::split_header(
          $request_headers['Authorization']
        );
        $parameters = array_merge($parameters, $header_parameters);
      }

    }

    return new OAuthRequest($http_method, $http_url, $parameters);
  }

  /**
   * pretty much a helper function to set up the request
   */
  public static function from_consumer_and_token($consumer, $token, $http_method, $http_url, $parameters=NULL) {
    @$parameters or $parameters = array();
    $defaults = array("oauth_version" => OAuthRequest::$version,
                      "oauth_nonce" => OAuthRequest::generate_nonce(),
                      "oauth_timestamp" => OAuthRequest::generate_timestamp(),
                      "oauth_consumer_key" => $consumer->key);
    if ($token)
      $defaults['oauth_token'] = $token->key;

    $parameters = array_merge($defaults, $parameters);

    return new OAuthRequest($http_method, $http_url, $parameters);
  }

  public function set_parameter($name, $value, $allow_duplicates = true) {
    if ($allow_duplicates && isset($this->parameters[$name])) {
      // We have already added parameter(s) with this name, so add to the list
      if (is_scalar($this->parameters[$name])) {
        // This is the first duplicate, so transform scalar (string)
        // into an array so we can add the duplicates
        $this->parameters[$name] = array($this->parameters[$name]);
      }

      $this->parameters[$name][] = $value;
    } else {
      $this->parameters[$name] = $value;
    }
  }

  public function get_parameter($name) {
    return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
  }

  public function get_parameters() {
    return $this->parameters;
  }

  public function unset_parameter($name) {
    unset($this->parameters[$name]);
  }

  /**
   * The request parameters, sorted and concatenated into a normalized string.
   * @return string
   */
  public function get_signable_parameters() {
    // Grab all parameters
    $params = $this->parameters;

    // Remove oauth_signature if present
    // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
    if (isset($params['oauth_signature'])) {
      unset($params['oauth_signature']);
    }

    return OAuthUtil::build_http_query($params);
  }

  /**
   * Returns the base string of this request
   *
   * The base string defined as the method, the url
   * and the parameters (normalized), each urlencoded
   * and the concated with &.
   */
  public function get_signature_base_string() {
    $parts = array(
      $this->get_normalized_http_method(),
      $this->get_normalized_http_url(),
      $this->get_signable_parameters()
    );

    $parts = OAuthUtil::urlencode_rfc3986($parts);

    return implode('&', $parts);
  }

  /**
   * just uppercases the http method
   */
  public function get_normalized_http_method() {
    return strtoupper($this->http_method);
  }

  /**
   * parses the url and rebuilds it to be
   * scheme://host/path
   */
  public function get_normalized_http_url() {
    $parts = parse_url($this->http_url);

    $port = @$parts['port'];
    $scheme = $parts['scheme'];
    $host = $parts['host'];
    $path = @$parts['path'];

    $port or $port = ($scheme == 'https') ? '443' : '80';

    if (($scheme == 'https' && $port != '443')
        || ($scheme == 'http' && $port != '80')) {
      $host = "$host:$port";
    }
    return "$scheme://$host$path";
  }

  /**
   * builds a url usable for a GET request
   */
  public function to_url() {
    $post_data = $this->to_postdata();
    $out = $this->get_normalized_http_url();
    if ($post_data) {
      $out .= '?'.$post_data;
    }
    return $out;
  }

  /**
   * builds the data one would send in a POST request
   */
  public function to_postdata() {
    return OAuthUtil::build_http_query($this->parameters);
  }

  /**
   * builds the Authorization: header
   */
  public function to_header($realm=null) {
    $first = true;
	if($realm) {
      $out = 'Authorization: OAuth realm="' . OAuthUtil::urlencode_rfc3986($realm) . '"';
      $first = false;
    } else
      $out = 'Authorization: OAuth';

    $total = array();
    foreach ($this->parameters as $k => $v) {
      if (substr($k, 0, 5) != "oauth") continue;
      if (is_array($v)) {
        throw new OAuthException('Arrays not supported in headers');
      }
      $out .= ($first) ? ' ' : ',';
      $out .= OAuthUtil::urlencode_rfc3986($k) .
              '="' .
              OAuthUtil::urlencode_rfc3986($v) .
              '"';
      $first = false;
    }
    return $out;
  }

  public function __toString() {
    return $this->to_url();
  }


  public function sign_request($signature_method, $consumer, $token) {
    $this->set_parameter(
      "oauth_signature_method",
      $signature_method->get_name(),
      false
    );
    $signature = $this->build_signature($signature_method, $consumer, $token);
    $this->set_parameter("oauth_signature", $signature, false);
  }

  public function build_signature($signature_method, $consumer, $token) {
    $signature = $signature_method->build_signature($this, $consumer, $token);
    return $signature;
  }

  /**
   * util function: current timestamp
   */
  private static function generate_timestamp() {
    return time();
  }

  /**
   * util function: current nonce
   */
  private static function generate_nonce() {
    $mt = microtime();
    $rand = mt_rand();

    return md5($mt . $rand); // md5s look nicer than numbers
  }
}

class OAuthServer {
  protected $timestamp_threshold = 300; // in seconds, five minutes
  protected $version = '1.0';             // hi blaine
  protected $signature_methods = array();

  protected $data_store;

  function __construct($data_store) {
    $this->data_store = $data_store;
  }

  public function add_signature_method($signature_method) {
    $this->signature_methods[$signature_method->get_name()] =
      $signature_method;
  }

  // high level functions

  /**
   * process a request_token request
   * returns the request token on success
   */
  public function fetch_request_token(&$request) {
    $this->get_version($request);

    $consumer = $this->get_consumer($request);

    // no token required for the initial token request
    $token = NULL;

    $this->check_signature($request, $consumer, $token);

    // Rev A change
    $callback = $request->get_parameter('oauth_callback');
    $new_token = $this->data_store->new_request_token($consumer, $callback);

    return $new_token;
  }

  /**
   * process an access_token request
   * returns the access token on success
   */
  public function fetch_access_token(&$request) {
    $this->get_version($request);

    $consumer = $this->get_consumer($request);

    // requires authorized request token
    $token = $this->get_token($request, $consumer, "request");

    $this->check_signature($request, $consumer, $token);

    // Rev A change
    $verifier = $request->get_parameter('oauth_verifier');
    $new_token = $this->data_store->new_access_token($token, $consumer, $verifier);

    return $new_token;
  }

  /**
   * verify an api call, checks all the parameters
   */
  public function verify_request(&$request) {
    $this->get_version($request);
    $consumer = $this->get_consumer($request);
    $token = $this->get_token($request, $consumer, "access");
    $this->check_signature($request, $consumer, $token);
    return array($consumer, $token);
  }

  // Internals from here
  /**
   * version 1
   */
  private function get_version(&$request) {
    $version = $request->get_parameter("oauth_version");
    if (!$version) {
      // Service Providers MUST assume the protocol version to be 1.0 if this parameter is not present. 
      // Chapter 7.0 ("Accessing Protected Ressources")
      $version = '1.0';
    }
    if ($version !== $this->version) {
      throw new OAuthException("OAuth version '$version' not supported");
    }
    return $version;
  }

  /**
   * figure out the signature with some defaults
   */
  private function get_signature_method(&$request) {
    $signature_method =
        @$request->get_parameter("oauth_signature_method");

    if (!$signature_method) {
      // According to chapter 7 ("Accessing Protected Ressources") the signature-method
      // parameter is required, and we can't just fallback to PLAINTEXT
      throw new OAuthException('No signature method parameter. This parameter is required');
    }

    if (!in_array($signature_method,
                  array_keys($this->signature_methods))) {
      throw new OAuthException(
        "Signature method '$signature_method' not supported " .
        "try one of the following: " .
        implode(", ", array_keys($this->signature_methods))
      );
    }
    return $this->signature_methods[$signature_method];
  }

  /**
   * try to find the consumer for the provided request's consumer key
   */
  private function get_consumer(&$request) {
    $consumer_key = @$request->get_parameter("oauth_consumer_key");
    if (!$consumer_key) {
      throw new OAuthException("Invalid consumer key");
    }

    $consumer = $this->data_store->lookup_consumer($consumer_key);
    if (!$consumer) {
      throw new OAuthException("Invalid consumer");
    }

    return $consumer;
  }

  /**
   * try to find the token for the provided request's token key
   */
  private function get_token(&$request, $consumer, $token_type="access") {
    $token_field = @$request->get_parameter('oauth_token');
    $token = $this->data_store->lookup_token(
      $consumer, $token_type, $token_field
    );
    if (!$token) {
      throw new OAuthException("Invalid $token_type token: $token_field");
    }
    return $token;
  }

  /**
   * all-in-one function to check the signature on a request
   * should guess the signature method appropriately
   */
  private function check_signature(&$request, $consumer, $token) {
    // this should probably be in a different method
    $timestamp = @$request->get_parameter('oauth_timestamp');
    $nonce = @$request->get_parameter('oauth_nonce');

    $this->check_timestamp($timestamp);
    $this->check_nonce($consumer, $token, $nonce, $timestamp);

    $signature_method = $this->get_signature_method($request);

    $signature = $request->get_parameter('oauth_signature');
    $valid_sig = $signature_method->check_signature(
      $request,
      $consumer,
      $token,
      $signature
    );

    if (!$valid_sig) {
      throw new OAuthException("Invalid signature");
    }
  }

  /**
   * check that the timestamp is new enough
   */
  private function check_timestamp($timestamp) {
    if( ! $timestamp )
      throw new OAuthException(
        'Missing timestamp parameter. The parameter is required'
      );
    
    // verify that timestamp is recentish
    $now = time();
    if (abs($now - $timestamp) > $this->timestamp_threshold) {
      throw new OAuthException(
        "Expired timestamp, yours $timestamp, ours $now"
      );
    }
  }

  /**
   * check that the nonce is not repeated
   */
  private function check_nonce($consumer, $token, $nonce, $timestamp) {
    if( ! $nonce )
      throw new OAuthException(
        'Missing nonce parameter. The parameter is required'
      );

    // verify that the nonce is uniqueish
    $found = $this->data_store->lookup_nonce(
      $consumer,
      $token,
      $nonce,
      $timestamp
    );
    if ($found) {
      throw new OAuthException("Nonce already used: $nonce");
    }
  }

}

class OAuthDataStore {
  function lookup_consumer($consumer_key) {
    // implement me
  }

  function lookup_token($consumer, $token_type, $token) {
    // implement me
  }

  function lookup_nonce($consumer, $token, $nonce, $timestamp) {
    // implement me
  }

  function new_request_token($consumer, $callback = null) {
    // return a new token attached to this consumer
  }

  function new_access_token($token, $consumer, $verifier = null) {
    // return a new access token attached to this consumer
    // for the user associated with this token if the request token
    // is authorized
    // should also invalidate the request token
  }

}

class OAuthUtil {
  public static function urlencode_rfc3986($input) {
  if (is_array($input)) {
    return array_map(array('OAuthUtil', 'urlencode_rfc3986'), $input);
  } else if (is_scalar($input)) {
    return str_replace(
      '+',
      ' ',
      str_replace('%7E', '~', rawurlencode($input))
    );
  } else {
    return '';
  }
}


  // This decode function isn't taking into consideration the above
  // modifications to the encoding process. However, this method doesn't
  // seem to be used anywhere so leaving it as is.
  public static function urldecode_rfc3986($string) {
    return urldecode($string);
  }

  // Utility function for turning the Authorization: header into
  // parameters, has to do some unescaping
  // Can filter out any non-oauth parameters if needed (default behaviour)
  public static function split_header($header, $only_allow_oauth_parameters = true) {
    $pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
    $offset = 0;
    $params = array();
    while (preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
      $match = $matches[0];
      $header_name = $matches[2][0];
      $header_content = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];
      if (preg_match('/^oauth_/', $header_name) || !$only_allow_oauth_parameters) {
        $params[$header_name] = OAuthUtil::urldecode_rfc3986($header_content);
      }
      $offset = $match[1] + strlen($match[0]);
    }

    if (isset($params['realm'])) {
      unset($params['realm']);
    }

    return $params;
  }

  // helper to try to sort out headers for people who aren't running apache
  public static function get_headers() {
    if (function_exists('apache_request_headers')) {
      // we need this to get the actual Authorization: header
      // because apache tends to tell us it doesn't exist
      $headers = apache_request_headers();

      // sanitize the output of apache_request_headers because
      // we always want the keys to be Cased-Like-This and arh()
      // returns the headers in the same case as they are in the
      // request
      $out = array();
      foreach( $headers AS $key => $value ) {
        $key = str_replace(
            " ",
            "-",
            ucwords(strtolower(str_replace("-", " ", $key)))
          );
        $out[$key] = $value;
      }
    } else {
      // otherwise we don't have apache and are just going to have to hope
      // that $_SERVER actually contains what we need
      $out = array();
      if( isset($_SERVER['CONTENT_TYPE']) )
        $out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
      if( isset($_ENV['CONTENT_TYPE']) )
        $out['Content-Type'] = $_ENV['CONTENT_TYPE'];

      foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) == "HTTP_") {
          // this is chaos, basically it is just there to capitalize the first
          // letter of every word that is not an initial HTTP and strip HTTP
          // code from przemek
          $key = str_replace(
            " ",
            "-",
            ucwords(strtolower(str_replace("_", " ", substr($key, 5))))
          );
          $out[$key] = $value;
        }
      }
    }
    return $out;
  }

  // This function takes a input like a=b&a=c&d=e and returns the parsed
  // parameters like this
  // array('a' => array('b','c'), 'd' => 'e')
  public static function parse_parameters( $input ) {
    if (!isset($input) || !$input) return array();

    $pairs = explode('&', $input);

    $parsed_parameters = array();
    foreach ($pairs as $pair) {
      $split = explode('=', $pair, 2);
      $parameter = OAuthUtil::urldecode_rfc3986($split[0]);
      $value = isset($split[1]) ? OAuthUtil::urldecode_rfc3986($split[1]) : '';

      if (isset($parsed_parameters[$parameter])) {
        // We have already recieved parameter(s) with this name, so add to the list
        // of parameters with this name

        if (is_scalar($parsed_parameters[$parameter])) {
          // This is the first duplicate, so transform scalar (string) into an array
          // so we can add the duplicates
          $parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
        }

        $parsed_parameters[$parameter][] = $value;
      } else {
        $parsed_parameters[$parameter] = $value;
      }
    }
    return $parsed_parameters;
  }

  public static function build_http_query($params) {
    if (!$params) return '';

    // Urlencode both keys and values
    $keys = OAuthUtil::urlencode_rfc3986(array_keys($params));
    $values = OAuthUtil::urlencode_rfc3986(array_values($params));
    $params = array_combine($keys, $values);

    // Parameters are sorted by name, using lexicographical byte value ordering.
    // Ref: Spec: 9.1.1 (1)
    uksort($params, 'strcmp');

    $pairs = array();
    foreach ($params as $parameter => $value) {
      if (is_array($value)) {
        // If two or more parameters share the same name, they are sorted by their value
        // Ref: Spec: 9.1.1 (1)
        natsort($value);
        foreach ($value as $duplicate_value) {
          $pairs[] = $parameter . '=' . $duplicate_value;
        }
      } else {
        $pairs[] = $parameter . '=' . $value;
      }
    }
    // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
    // Each name-value pair is separated by an '&' character (ASCII code 38)
    return implode('&', $pairs);
  }
}

/**
 * Twitter OAuth class
 */
class TwitterOAuth {
  /* Contains the last HTTP status code returned. */
  public $http_code;
  /* Contains the last API call. */
  public $url;
  /* Set up the API root URL. */
  public $host = "https://api.twitter.com/1/";
  /* Set timeout default. */
  public $timeout = 30;
  /* Set connect timeout. */
  public $connecttimeout = 30; 
  /* Verify SSL Cert. */
  public $ssl_verifypeer = FALSE;
  /* Respons format. */
  public $format = 'json';
  /* Decode returned json data. */
  public $decode_json = TRUE;
  /* Contains the last HTTP headers returned. */
  public $http_info;
  /* Set the useragnet. */
  public $useragent = 'TwitterOAuth v0.2.0-beta2';
  /* Immediately retry the API call if the response was not successful. */
  //public $retry = TRUE;




  /**
   * Set API URLS
   */
  function accessTokenURL()  { return 'https://api.twitter.com/oauth/access_token'; }
  function authenticateURL() { return 'https://twitter.com/oauth/authenticate'; }
  function authorizeURL()    { return 'https://twitter.com/oauth/authorize'; }
  function requestTokenURL() { return 'https://api.twitter.com/oauth/request_token'; }

  /**
   * Debug helpers
   */
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * construct TwitterOAuth object
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }


  /**
   * Get a request_token from Twitter
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken($oauth_callback = NULL) {
    $parameters = array();
    if (!empty($oauth_callback)) {
      $parameters['oauth_callback'] = $oauth_callback;
    } 
    $request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * Get the authorize URL
   *
   * @returns a string
   */
  function getAuthorizeURL($token, $sign_in_with_twitter = TRUE) {
    if (is_array($token)) {
      $token = $token['oauth_token'];
    }
    if (empty($sign_in_with_twitter)) {
      return $this->authorizeURL() . "?oauth_token={$token}";
    } else {
       return $this->authenticateURL() . "?oauth_token={$token}";
    }
  }

  /**
   * Exchange request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham")
   */
  function getAccessToken($oauth_verifier = FALSE) {
    $parameters = array();
    if (!empty($oauth_verifier)) {
      $parameters['oauth_verifier'] = $oauth_verifier;
    }
    $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * One time exchange of username and password for access token and secret.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham",
   *                "x_auth_expires" => "0")
   */  
  function getXAuthToken($username, $password) {
    $parameters = array();
    $parameters['x_auth_username'] = $username;
    $parameters['x_auth_password'] = $password;
    $parameters['x_auth_mode'] = 'client_auth';
    $request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * GET wrapper for oAuthRequest.
   */
  function get($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'GET', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }
  
  /**
   * POST wrapper for oAuthRequest.
   */
  function post($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'POST', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }

  /**
   * DELETE wrapper for oAuthReqeust.
   */
  function delete($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'DELETE', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }

  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $method, $parameters) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
      $url = "{$this->host}{$url}.{$this->format}";
    }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
    case 'GET':
      return $this->http($request->to_url(), 'GET');
    default:
      return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
    }
  }

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $method, $postfields = NULL) {
    $this->http_info = array();
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
    curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
    curl_setopt($ci, CURLOPT_HEADER, FALSE);

    switch ($method) {
      case 'POST':
        curl_setopt($ci, CURLOPT_POST, TRUE);
        if (!empty($postfields)) {
          curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
        }
        break;
      case 'DELETE':
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($postfields)) {
          $url = "{$url}?{$postfields}";
        }
    }

    curl_setopt($ci, CURLOPT_URL, $url);
    $response = curl_exec($ci);
    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
    $this->url = $url;
    curl_close ($ci);
    return $response;
  }

  /**
   * Get the header info to store.
   */
  function getHeader($ch, $header) {
    $i = strpos($header, ':');
    if (!empty($i)) {
      $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
      $value = trim(substr($header, $i + 2));
      $this->http_header[$key] = $value;
    }
    return strlen($header);
  }
}

class Eternal_Venedor_Helper_Data extends Mage_Core_Helper_Abstract
{
    // texture path
    protected $_texturePath;
    
    // background image path
    protected $_backgroundImagePath;
    
    public function __construct()
    {
        $this->_texturePath = 'wysiwyg/eternal/venedor/_textures/';        
    }
    
    // get config for theme setting
    public function getConfig($optionName) 
    {
        if (Mage::registry('venedor_css_generate_store')) {
            $store_code = Mage::registry('venedor_css_generate_store');
            $store_id = Mage::getModel('core/store')->load($store_code)->getId();
        } else {
            $store_id = NULL;
        }
        return Mage::getStoreConfig('venedor_setting/' . $optionName, $store_id);
    }
    
    // get config group for theme setting section
    public function getConfigGroup($storeId = NULL)
    {
        if (!$storeId) {
            $store_code = Mage::registry('venedor_css_generate_store');
            $storeId = Mage::getModel('core/store')->load($store_code)->getId();
        }

        if ($storeId)
            return Mage::getStoreConfig('venedor_setting', $storeId);
        else
            return Mage::getStoreConfig('venedor_setting');
    }    
    
    // get config for theme design
    public function getConfigDesign($optionName)
    {
        if (Mage::registry('venedor_css_generate_store')) {
            $store_code = Mage::registry('venedor_css_generate_store');
            $store_id = Mage::getModel('core/store')->load($store_code)->getId();
        } else {
            $store_id = NULL;
        }
        return Mage::getStoreConfig('venedor_design/' . $optionName, $store_id);
    }
    
    // get config group for theme design section
    public function getConfigGroupDesign($storeId = NULL)
    {
        if (!$storeId) {
            $store_code = Mage::registry('venedor_css_generate_store');
            $storeId = Mage::getModel('core/store')->load($store_code)->getId();
        }

        if ($storeId)
            return Mage::getStoreConfig('venedor_design', $storeId);
        else
            return Mage::getStoreConfig('venedor_design');
    }
    
    // get config for theme import, export
    public function getConfigData($optionName, $storeId = NULL)
    {
        if ($storeId)
            return Mage::getStoreConfig('venedor_data/' . $optionName, $storeId);
        else
            return Mage::getStoreConfig('venedor_data/' . $optionName);
    }
    
    private function file_get_contents_curl($url, $useragent = null) {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($useragent)
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $data = curl_exec($ch);
        curl_close($ch);
        
        return $data;
    }
    
    // get jquery url
    public function getJqueryUrl()
    {
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . 'eternal/jquery/jquery-1.8.2.min.js');
    }
    
    public function getJqueryNoConflictUrl()
    {
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . 'eternal/jquery/jquery-noconflict.js');
    }
    
    // get bootstrap js
    public function getBootstrapJsFile() {
        return 'eternal/bootstrap/' . $this->getBootstrapVersion() . '/js/bootstrap.min.js';
    }
    
    //get bootstrap css
    public function getBootstrapCssFile() {
        $bootstrap_version = $this->getBootstrapVersion();
        if ((int)(substr($bootstrap_version, 0, 1)) < 3)
            return 'css/bootstrap/' . $this->getBootstrapVersion() . '/css/bootstrap.css';
        return 'css/bootstrap/' . $this->getBootstrapVersion() . '/css/bootstrap.min.css';
    }
    
    //get bootstrap css
    public function getBootstrapThemeCssFile() {
        $bootstrap_version = $this->getBootstrapVersion();
        if ((int)(substr($bootstrap_version, 0, 1)) < 3)
            return 'css/empty.css';
        return 'css/bootstrap/' . $this->getBootstrapVersion() . '/css/bootstrap-theme.min.css';
    }
    
    //get bootstrap css
    public function getBootstrapResponsiveCssFile() {
        $bootstrap_version = $this->getBootstrapVersion();
        if ((int)(substr($bootstrap_version, 0, 1)) < 3) {
            if ($this->isResponsive())
                return 'css/bootstrap/' . $this->getBootstrapVersion() . '/css/bootstrap-responsive.css';
            return 'css/empty.css';
        }
        if ( !$this->isResponsive() ) 
            return 'css/bootstrap/' . $this->getBootstrapVersion() . '/css/non-responsive.css';
        return 'css/empty.css';
    }
    
    // get twitter bootstrap version
    public function getBootstrapVersion()
    {
        $version = $this->getConfig('general/bootstrap_version');    
        if ($version)
            return $version;
        return '2.3.2';
    }
    
    // get bootstrap base url
    public function getBootstrapUrl() 
    {
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS) . 'eternal/bootstrap/' . $this->getBootstrapVersion());
    }
    
    // get class for bootstrap version
    public function getBootstrapClass() {
        $bootstrap_version = $this->getBootstrapVersion();
        if ((int)(substr($bootstrap_version, 0, 1)) >= 3)
            return 'bv3';
        return '';
    }
    
    // get bootstrap container class
    public function getContainerClass() {
        $bootstrap_version = $this->getBootstrapVersion();
        if ((int)(substr($bootstrap_version, 0, 1)) >= 3)
            return 'container';
        if ($this->isFluid())
            return 'container-fluid';
        return 'container';
    }
    
    // get bootstrap row class
    public function getRowClass() {
        $bootstrap_version = $this->getBootstrapVersion();
        if ((int)(substr($bootstrap_version, 0, 1)) >= 3)
            return 'row';
        return 'row-fluid';
    }
    
    // get bootstrap span class
    public function getSpanClass() {
        $bootstrap_version = $this->getBootstrapVersion();
        if ((int)(substr($bootstrap_version, 0, 1)) >= 3)
            if ($this->isResponsive())
                return 'col-sm-';
            else
                return 'col-xs-';
        return 'span';
    }
    
    // get bootstrap offset class
    public function getOffsetClass() {
        $bootstrap_version = $this->getBootstrapVersion();
        if ((int)(substr($bootstrap_version, 0, 1)) >= 3)
            return 'col-md-offset-';
        return 'offset';
    }
    
    // check responsive
    public function isResponsive()
    {
        if ($this->getConfig('general/active_responsive'))
            return true;
        return false;
    }
    
    // check fluid layout
    public function isFluid()
    {
        if ($this->getConfig('general/bootstrap_layout') == 'fixed')
            return false;
        return true;
    }
    
    // get favicon
    public function getFavicon() 
    {
        $icon = $this->getConfig('general/favicon');
        if ($icon)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'favicon/' . $icon);
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/favicon.ico');
    }

    // get logo
    public function getLogo() 
    {
        $icon = $this->getConfig('general/logo');
        if ($icon)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/' . $icon);
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/logo.png');
    }
        
    // get apple iphone icon
    public function getIphoneIcon() 
    {
        $icon = $this->getConfig('general/icon_iphone');
        if ($icon)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/' . $icon);
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/apple-touch-icon.png');
    }
    
    // get apple ipad icon
    public function getIpadIcon() 
    {
        $icon = $this->getConfig('general/icon_ipad');
        if ($icon)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/' . $icon);
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/apple-touch-icon-72x72.png');
    }
    
    // get apple iphone retina icon
    public function getIphoneRetinaIcon()
    {
        $icon = $this->getConfig('general/icon_iphone_retina');
        if ($icon)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/' . $icon);
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/apple-touch-icon-114x114.png');
    }
    
    // get apple ipad retina icon
    public function getIpadRetinaIcon()
    {
        $icon = $this->getConfig('general/icon_ipad_retina');
        if ($icon)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/' . $icon);
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/apple-touch-icon-144x144.png');
    }
    
    // get googlemap maker
    public function getGmapMaker()
    {
        $icon = $this->getConfig('contactus/gmap_maker');
        if ($icon)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/icon/' . $icon);
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/icon/icon_gmap.png');
    }
    
    // get footer twitter icon
    public function getTwitterIcon()
    {
        $icon = $this->getConfigDesign('twitter/icon');
        if ($icon)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/icon/' . $icon);
        return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/icon/icon_twitter.png');
    }
    
    // get texture path
    public function getTexturePath() 
    {
        return $this->_texturePath;        
    }
    
    // get texture url
    public function getTextureUrl() 
    {
        $texture = $this->getConfigDesign('page/texture');
        if ($texture)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . $this->getTexturePath() . $texture . '.png');
    }
    
    // get background image url
    public function getBackgroundImageUrl() 
    {
        $image = $this->getConfigDesign('page/bg_image');
        if ($image)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/background/' . $image);
    }
    
    // get header texture url
    public function getHeaderTextureUrl() 
    {
        $texture = $this->getConfigDesign('header/texture');
        if ($texture)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . $this->getTexturePath() . $texture . '.png');
    }
    
    // get main menu texture url
    public function getMenuTextureUrl() 
    {
        $texture = $this->getConfigDesign('top_menu/texture');
        if ($texture)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . $this->getTexturePath() . $texture . '.png');
    }
    
    // get footer texture url
    public function getFooterTextureUrl() 
    {
        $texture = $this->getConfigDesign('footer/texture');
        if ($texture)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . $this->getTexturePath() . $texture . '.png');
    }
    
    // get breadcrumbs texture url
    public function getBreadcrumbsTextureUrl() 
    {
        $texture = $this->getConfigDesign('breadcrumbs/texture');
        if ($texture)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . $this->getTexturePath() . $texture . '.png');
    }
    
    // show all attributes in layer navigation
    public function showAllAttributes() {
        return $this->getConfig('navigation/show_all');
    }
    
    // get attributes to show in layer navigation
    public function getShowAttributes() {
        $attributes = $this->getConfig('navigation/show_attributes');
        return explode(',', $attributes);
    }
    
    // get header fixed
    public function isHeaderFixed() {
        return $this->getConfig('header/fixed');
    }
    
    public function isHeaderFixedLogo() {
        return $this->getConfig('header/fixed_logo');
    }
    
    // want to add facebook script after body tag
    public function enabledFacebook()
    {
        if ($this->getConfig('facebook/enable'))
            return true;
        return false;
    }
    
    // get facebook config
    public function getFacebookConfig() 
    {
        return Mage::getStoreConfig('venedor_setting/facebook');
    }
    
    // get twitter config
    public function getTwitterConfig() 
    {
        return Mage::getStoreConfig('venedor_setting/twitter');
    }
    
    // get category banner texture url
    public function getCategoryBannerTextureUrl() 
    {
        $texture = $this->getConfigDesign('category/texture');
        if ($texture)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . $this->getTexturePath() . $texture . '.png');
    }
    
    // get slider texture url
    public function getSliderTextureUrl() 
    {
        $texture = $this->getConfigDesign('home_slider/texture');
        if ($texture)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . $this->getTexturePath() . $texture . '.png');
    }
    
    // get image for product
    public function getImage($product, $imgWidth, $imgHeight, $imgVersion='small_image', $file=NULL) 
    {
        $url = '';
        if ($imgHeight <= 0)
        {
            $url = Mage::helper('catalog/image')
                ->init($product, $imgVersion, $file)
                //->constrainOnly(true)
                ->keepAspectRatio(true)
                //->setQuality(100)
                ->keepFrame(false)
                ->resize($imgWidth);
        }
        else
        {
            $url = Mage::helper('catalog/image')
                ->init($product, $imgVersion, $file)
                ->resize($imgWidth, $imgHeight);
        }
        return $url;
    }
    
    // get hover image for product
    public function getHoverImageHtml($product, $imgWidth, $imgHeight, $imgVersion='small_image') 
    {
        $product->load('media_gallery');
        $order = $this->getConfig('category/image_order');
        if ($gallery = $product->getMediaGalleryImages())
        {
            if ($hoverImage = $gallery->getItemByColumnValue('position', $order))
            {
                $url = '';
                if ($imgHeight <= 0)
                {
                    $url = Mage::helper('catalog/image')
                        ->init($product, $imgVersion, $hoverImage->getFile())
                        ->constrainOnly(true)
                        ->keepAspectRatio(true)
                        ->keepFrame(false)
                        ->resize($imgWidth);
                }
                else
                {
                    $url = Mage::helper('catalog/image')
                        ->init($product, $imgVersion, $hoverImage->getFile())
                        ->resize($imgWidth, $imgHeight);
                }
                return '<img class="hover-image" src="' . $url . '" alt="' . $product->getName() . '" />';
            }
        }
        
        return '';
    }
    
    // get slider background image url
    public function getSliderBackgroundImageUrl() 
    {
        $image = $this->getConfigDesign('home_slider/bg_image');
        if ($image)
            return str_replace(array('http://', 'http://'), '//', Mage::getBaseUrl('media') . 'eternal/venedor/background/' . $image);
    }
    
    function getAttribute($attrib, $tag){
          //get attribute from html tag
          $re = '/'.$attrib.'=["\']?([^"\' ]*)["\' ]/is';
          preg_match($re, $tag, $match);
          
          if($match){
            return urldecode($match[1]);
          }else {
            return false;
          }
    }
    
    function fetch_fb_fans($fb_id, $limit = 10){
        $ret = array();
        $matches = array();
        $url = 'https://www.facebook.com/plugins/likebox.php?href=https://www.facebook.com/' . $fb_id . '&connections=' . $limit;
        
        $html = '';
        $like_html = $this->file_get_contents_curl($url, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:15.0) Gecko/20100101 Firefox/15.0.1');
        $doc = new DOMDocument('1.0', 'utf-8');
        @$doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . $like_html);
        $peopleList = array();
        $i = 0;

		if (!$doc)
			return false;

		$result = array();
		$link = $doc->getElementById('u_0_4');
		$result['link'] = 'https://www.facebook.com/'.$fb_id;
		$result['like'] = '';
		if (isset($link)) {
			foreach ($link->childNodes as $child) {
				$result['like'] .= $link->ownerDocument->saveHTML($child);
			} 
		}

        foreach ($doc->getElementsByTagName('ul')->item(0)->childNodes as $child) {
            $raw = $doc->saveXML($child);
            $li = preg_replace("/<li[^>]+\>/i", "", $raw);
            $peopleList[$i] = preg_replace("/<\/li>/i", "", $li);
            $i++;
        }
        
        foreach ($peopleList as $key => $code) {
            $name = $this->getAttribute('title', $code);
            $nm = substr($name, 0, 7);
            //print_r(strlen($nm));echo "\n";
            if (strlen($nm) != strlen($name)) $nm = $nm."...";

            $image = $this->getAttribute('src', $code);
            $link = $this->getAttribute('href', $code);

            //$data = file_get_contents($image);
            //$img_in_base64 = 'data:image/jpg;base64,' . base64_encode($data);
            $protocols = array("http:","https:"); 
            $img_in_base64 = str_replace($protocols, "", $image);

            $html .= '<div class="fb-person">';
            if ($link != "") {
                $html .= "<a href=\"".$link."\" title=\"".$name."\" target=\"_blank\"><img src=\"".$img_in_base64."\" alt=\"\" /></a>";
            } else {
                $html .= "<span title=\"".$name."\"><img src=\"".$img_in_base64."\" alt=\"\" /></span>";
            }
            $html .= $name.'</div>';
        }
		$result['fans'] = $html;
        return $result;
    }
    
    // get facebook fans
    public function getFBFans() {
        $fb = $this->getFacebookConfig();
        
        if (!$fb['enable'])
            return false;
            
        $limit = $fb['limit'];
        $fb_name = $fb['name'];
        
        // get page info from graph
	    return $this->fetch_fb_fans($fb_name, $limit);
    }
    
    // get twitter tweets
    public function getTwitterTweets() {
        $tt = $this->getTwitterConfig();
        
        if (!$tt['enable'])
            return false;
            
        $twitteruser = $tt['name'];
        $limit = $tt['limit'];
        $consumerkey = $tt['consumer_key'];
        $consumersecret = $tt['consumer_secret'];
        $accesstoken = $tt['access_token'];
        $accesstokensecret = $tt['access_token_secret'];
        
        $connection = new TwitterOAuth($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);

        $tweets = $connection->get("https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=".$twitteruser."&count=".$limit);
        
        return $tweets;
    }
    
    // get advertising block ids
    public function getAdsBlockIds() {
        $blockIdsString = $this->getConfig('sidebar/ads_blocks');
        $blockIds = explode(",", str_replace(" ", "", $blockIdsString));
        return $blockIds;
    }
}