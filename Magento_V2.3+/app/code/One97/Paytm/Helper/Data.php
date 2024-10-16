<?php
	namespace One97\Paytm\Helper;
	use Magento\Framework\App\Helper\Context;
	use Magento\Sales\Model\Order;
	use Magento\Framework\App\Helper\AbstractHelper;
	use Magento\CatalogInventory\Model\Stock\StockItemRepository;

	/* Paytm lib file */
	class Data extends AbstractHelper {
	    protected $session;
		protected $stockRegistry;
		private $stockItemRepository;
	    
	    // PaytmConstants.php start
	    CONST TRANSACTION_URL_PRODUCTION			= "https://secure.paytmpayments.com/order/process";
		CONST TRANSACTION_STATUS_URL_PRODUCTION		= "https://secure.paytmpayments.com/order/status";

		CONST PRODUCTION_HOST				= "https://secure.paytmpayments.com/";
		CONST STAGING_HOST				= "https://securestage.paytmpayments.com/";
		CONST PRODUCTION_PPBL_HOST				= "https://securepg.paytm.in/";

    	CONST PPBL = false;		

		CONST TRANSACTION_URL_STAGING			= "https://securestage.paytmpayments.com/order/process";
		CONST TRANSACTION_STATUS_URL_STAGING		= "https://securestage.paytmpayments.com/order/status";


	    CONST TRANSACTION_TOKEN_URL_PRODUCTION		= "https://secure.paytmpayments.com/theia/api/v1/initiateTransaction?mid=";

		CONST TRANSACTION_TOKEN_URL_STAGING		= "https://securestage.paytmpayments.com/theia/api/v1/initiateTransaction?mid=";
		CONST CHECKOUT_JS_URL				= "merchantpgpui/checkoutjs/merchants/MID.js";

		CONST SAVE_PAYTM_RESPONSE 			= true;
		CONST CHANNEL_ID				= "WEB";
		CONST APPEND_TIMESTAMP				= true;
		CONST X_REQUEST_ID				= "PLUGIN_MAGENTO_";

		CONST MAX_RETRY_COUNT				= 3;
		CONST CONNECT_TIMEOUT				= "10";
		CONST TIMEOUT					= "10";

		CONST LAST_UPDATED				= "20241015";
		CONST PLUGIN_VERSION				= "2.7.0";

		CONST CUSTOM_CALLBACK_URL			= "";
	    // PaytmConstants.php end

	    public function __construct(
	    	Context $context, 
	    	\Magento\Checkout\Model\Session $session,
	    	\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,			 
			StockItemRepository $stockItemRepository
	    ) {
	        $this->session = $session;
	        $this->stockRegistry = $stockRegistry;
			$this->stockItemRepository = $stockItemRepository;
	        parent::__construct($context);
			 
	    } 
	    public function cancelCurrentOrder($comment) {
	        $order = $this->session->getLastRealOrder();
	        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
	            $order->registerCancellation($comment)->save();
	            return true;
	        }
	        return false;
	    }

	    public function restoreQuote() {
	        return $this->session->restoreQuote();
	    }

	    public function getUrl($route, $params = []) {
	        return $this->_getUrl($route, $params);
	    }

	    // PaytmChecksum.php start
	    private static $iv = "@@@@&&&&####$$$$";

		static public function encrypt($input, $key) {
			$key = html_entity_decode($key);
	
			if (function_exists('openssl_encrypt')) {
				$data = openssl_encrypt($input, "AES-128-CBC", $key, 0, self::$iv);
			} else {
				throw new Exception('OpenSSL extension is not available. Please install the OpenSSL extension.');
			}
			return $data;
		}
	
		static public function decrypt($encrypted, $key) {
			$key = html_entity_decode($key);
			
			if(function_exists('openssl_decrypt')){
				$data = openssl_decrypt ( $encrypted , "AES-128-CBC" , $key, 0, self::$iv );
			} else {
				throw new Exception('OpenSSL extension is not available. Please install the OpenSSL extension.');
			}
			return $data;
		}

	    static public function generateSignature($params, $key) {
	    	if(!is_array($params) && !is_string($params)){
	    		throw new Exception("string or array expected, ".gettype($params)." given");			
	    	}
	    	if(is_array($params)){
	    		$params = self::getStringByParams($params);			
	    	}
	    	return self::generateSignatureByString($params, $key);
	    }

	    static public function verifySignature($params, $key, $checksum){
	    	if(!is_array($params) && !is_string($params)){
	    		throw new Exception("string or array expected, ".gettype($params)." given");
	    	}
	    	if(is_array($params)){
	    		$params = self::getStringByParams($params);
	    	}		
	    	return self::verifySignatureByString($params, $key, $checksum);
	    }

	    static private function generateSignatureByString($params, $key){
	    	$salt = self::generateRandomString(4);
	    	return self::calculateChecksum($params, $key, $salt);
	    }

	    static private function verifySignatureByString($params, $key, $checksum){
	    	$paytm_hash = self::decrypt($checksum, $key);
	    	$salt = substr($paytm_hash, -4);
	    	return $paytm_hash == self::calculateHash($params, $salt) ? true : false;
	    }

	    static private function generateRandomString($length) {
	    	$random = "";
	    	//srand((double) microtime() * 1000000);

	    	$data = "9876543210ZYXWVUTSRQPONMLKJIHGFEDCBAabcdefghijklmnopqrstuvwxyz!@#$&_";	

	    	for ($i = 0; $i < $length; $i++) {
	    		$random .= substr($data, (rand() % (strlen($data))), 1);
	    	}

	    	return $random;
	    }

	    static private function getStringByParams($params) {
	    	ksort($params);		
	    	$params = array_map(function ($value){
	    		return ($value == null) ? "" : $value;
	      	}, $params);
	    	return implode("|", $params);
	    }

	    static private function calculateHash($params, $salt){
	    	$finalString = $params . "|" . $salt;
	    	$hash = hash("sha256", $finalString);
	    	return $hash . $salt;
	    }

	    static private function calculateChecksum($params, $key, $salt){
	    	$hashString = self::calculateHash($params, $salt);
	    	return self::encrypt($hashString, $key);
	    }

	    static private function pkcs5Pad($text, $blocksize) {
	    	$pad = $blocksize - (strlen($text) % $blocksize);
	    	return $text . str_repeat(chr($pad), $pad);
	    }

	    static private function pkcs5Unpad($text) {
	    	$pad = ord($text[strlen($text) - 1]);
	    	if ($pad > strlen($text))
	    		return false;
	    	return substr($text, 0, -1 * $pad);
	    }
	    // PaytmChecksum.php end

	    // PaytmHelper.php start
	    /**
	    * exclude timestap with order id
	    */
	    public static function getTransactionURL($isProduction = 0){		
	    	if($isProduction == 1){
	    		return Data::TRANSACTION_URL_PRODUCTION;
	    	}else{
	    		return Data::TRANSACTION_URL_STAGING;			
	    	}
	    }
	    /**
	    * exclude timestap with order id
	    */
	    public static function getTransactionStatusURL($isProduction = 0){		
	    	if($isProduction == 1){
	    		return Data::TRANSACTION_STATUS_URL_PRODUCTION;
	    	}else{
	    		return Data::TRANSACTION_STATUS_URL_STAGING;			
	    	}
	    }
	    
		public static function getcURLversion(){    
			if(function_exists('curl_version')){
				$curl_version = curl_version();
				if(!empty($curl_version['version'])){
					return $curl_version['version'];
				}
			}
			return false;
		}

	    public static function executecUrl($apiURL, $requestParamList) {
	    	$responseParamList = array();
	    	$JsonData = json_encode($requestParamList);
	    	$postData = 'JsonData='.urlencode($JsonData);
	    	$ch = curl_init($apiURL);
	    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Data::CONNECT_TIMEOUT);
	    	curl_setopt($ch, CURLOPT_TIMEOUT, Data::TIMEOUT);
	    	
	    	/*
	    	** default value is 2 and we also want to use 2
	    	** so no need to specify since older PHP version might not support 2 as valid value
	    	** see https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html
	    	*/
	    	// curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);

	    	// TLS 1.2 or above required
	    	// curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

	    	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    		'Content-Type: application/json', 
	    		'Content-Length: ' . strlen($postData))
	    	);
	    	$jsonResponse = curl_exec($ch);   

	    	if (!curl_errno($ch)) {
	    		return json_decode($jsonResponse, true);
	    	} else {
	    		return false;
	    	}
	    }



	    public static function getPaytmURL($url = false, $isProduction = 0, $mid=''){
			if(!$url) return false; 
			if($isProduction == 1){
                if(Data::PPBL==false){
                    return Data::PRODUCTION_HOST . $url;
                }    				
            $midLength = strlen(preg_replace("/[^A-Za-z]/", "", $mid));
	            if($midLength == 6){
	                return Data::PRODUCTION_HOST . $url;
	            }
	            if($midLength == 7){
	                return Data::PRODUCTION_PPBL_HOST . $url;
	            }  
			}else{
				return Data::STAGING_HOST . $url;			
			}
		}



	    // PaytmHelper.php start
	}
?>
