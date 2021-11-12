<?php
	namespace One97\Paytm\Helper;
	use Magento\Framework\App\Helper\Context;
	use Magento\Sales\Model\Order;
	use Magento\Framework\App\Helper\AbstractHelper;

	/* Paytm lib file */
	class Data extends AbstractHelper {
	    protected $session;
	    
	    // PaytmConstants.php start
	    CONST TRANSACTION_URL_PRODUCTION			= "https://securegw.paytm.in/order/process";
		CONST TRANSACTION_STATUS_URL_PRODUCTION		= "https://securegw.paytm.in/order/status";

		CONST PRODUCTION_HOST				= "https://securegw.paytm.in/";
		CONST STAGING_HOST				= "https://securegw-stage.paytm.in/";

		CONST TRANSACTION_URL_STAGING			= "https://securegw-stage.paytm.in/order/process";
		CONST TRANSACTION_STATUS_URL_STAGING		= "https://securegw-stage.paytm.in/order/status";


	    CONST TRANSACTION_TOKEN_URL_PRODUCTION		= "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=";

		CONST TRANSACTION_TOKEN_URL_STAGING		= "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=";
		CONST CHECKOUT_JS_URL				= "merchantpgpui/checkoutjs/merchants/MID.js";

		CONST SAVE_PAYTM_RESPONSE 			= true;
		CONST CHANNEL_ID				= "WEB";
		CONST APPEND_TIMESTAMP				= true;
		CONST X_REQUEST_ID				= "PLUGIN_MAGENTO_";

		CONST MAX_RETRY_COUNT				= 3;
		CONST CONNECT_TIMEOUT				= "10";
		CONST TIMEOUT					= "10";

		CONST LAST_UPDATED				= "20211110";
		CONST PLUGIN_VERSION				= "2.6.3";

		CONST CUSTOM_CALLBACK_URL			= "";
	    // PaytmConstants.php end

	    public function __construct(
	    	Context $context, 
	    	\Magento\Checkout\Model\Session $session,
	    	\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
	    ) {
	        $this->session = $session;
	        $this->stockRegistry = $stockRegistry;
	        parent::__construct($context);
	    }

        public function updateStockQty($order) {
    		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    		$stockItem = $objectManager->create('\Magento\CatalogInventory\Model\Stock\StockItemRepository');
    		$orderItems = $order->getAllItems();
    		foreach ($orderItems as $item) {
    			$product_id = $item->getProductId();

    			$sku = $item->getSku();
    			$current_qty = $this->stockRegistry->getStockItemBySku($sku)->getQty();
    			$order_qty = (int)$item->getQtyOrdered();
    			$update_stock_qty = $current_qty + $order_qty;

    			$stockItem = $this->stockRegistry->getStockItemBySku($sku);
    			$stockItem->setQty($update_stock_qty);
    			$stockItem->setIsInStock((bool)$update_stock_qty); // this line
    			$this->stockRegistry->updateStockItemBySku($sku, $stockItem);
    		}
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

	    	if(function_exists('openssl_encrypt')){
	    		$data = openssl_encrypt ( $input , "AES-128-CBC" , $key, 0, self::$iv );
	    	} else {
	    		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
	    		$input = self::pkcs5Pad($input, $size);
	    		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
	    		mcrypt_generic_init($td, $key, self::$iv);
	    		$data = mcrypt_generic($td, $input);
	    		mcrypt_generic_deinit($td);
	    		mcrypt_module_close($td);
	    		$data = base64_encode($data);
	    	}
	    	return $data;
	    }

	    static public function decrypt($encrypted, $key) {
	    	$key = html_entity_decode($key);
	    	
	    	if(function_exists('openssl_decrypt')){
	    		$data = openssl_decrypt ( $encrypted , "AES-128-CBC" , $key, 0, self::$iv );
	    	} else {
	    		$encrypted = base64_decode($encrypted);
	    		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
	    		mcrypt_generic_init($td, $key, self::$iv);
	    		$data = mdecrypt_generic($td, $encrypted);
	    		mcrypt_generic_deinit($td);
	    		mcrypt_module_close($td);
	    		$data = self::pkcs5Unpad($data);
	    		$data = rtrim($data);
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
	    	srand((double) microtime() * 1000000);

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



	    public static function getPaytmURL($url = false, $isProduction = 0){
			if(!$url) return false; 
			if($isProduction == 1){
				return Data::PRODUCTION_HOST . $url;
			}else{
				return Data::STAGING_HOST . $url;			
			}
		}



	    // PaytmHelper.php start
	}
?>
