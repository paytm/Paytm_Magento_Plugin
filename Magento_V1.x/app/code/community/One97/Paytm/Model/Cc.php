<?php


class One97_paytm_Model_Cc extends Mage_Payment_Model_Method_Abstract

{	
	//unique internal payment method identifier
	
	
	protected $_code = 'paytm_cc';
    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_paymentMethod			= 'cc';
    protected $_defaultLocale			= 'en';
    protected $_liveUrl	= NULL;
    protected $_formBlockType = 'paytm/form';
    protected $_infoBlockType = 'paytm/info';
    protected $_order;

    public function isAvailable($quote = null) {
		if($this->getConfigData('active')==1){
        	return true;
		}
		return false;
    }
    
    //Get order model
    
	 
    public function getOrder()
    {
		if (!$this->_order) {
			$this->_order = $this->getInfoInstance()->getOrder();
		}
		return $this->_order;
    }

    public function getOrderPlaceRedirectUrl()
    {
          return Mage::getUrl('paytm/processing/redirect');
    }

   
    // Return payment method type string
     
    public function getPaymentMethodType()
    {
        return $this->_paymentMethod;
    }

    public function getUrl() {
		$this->_liveUrl=Mage::helper('paytm')->getTransactionURL(Mage::getStoreConfig('payment/paytm_cc/transaction_environment'));
		return $this->_liveUrl;
    }

    

	public function executecUrl($apiURL, $postData) {
		
		$ch = curl_init($apiURL);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Mage::helper('paytm')::CONNECT_TIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, Mage::helper('paytm')::TIMEOUT);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json', 
			'Content-Length: ' . strlen($postData))
		);
		$jsonResponse = curl_exec($ch);   
		//return $jsonResponse;
		 if (!curl_errno($ch)) {
			return json_decode($jsonResponse, true);
		} else {
			 return false; 
		}  
	}
    
    //prepare params array to send it to gateway page via POST
    public function getFormFields()
    {
		
		$price      = number_format($this->getOrder()->getGrandTotal(),2,'.','');
        $currency   = $this->getOrder()->getOrderCurrencyCode();
 		$locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
		if (is_array($locale) && !empty($locale))
			$locale = $locale[0];
		else
			$locale = $this->getDefaultLocale();
		 
		
		$const = (string)Mage::getConfig()->getNode('global/crypt/key');// Mage::getStoreConfig('payment/paytm_cc/constpaytm');
		$mer = Mage::helper('paytm')->decrypt($this->getConfigData('inst_key'),$const);
		$merid = Mage::helper('paytm')->decrypt($this->getConfigData('inst_id'),$const);
		$website = $this->getConfigData('website');
		$industry_type = $this->getConfigData('industrytype');
		$is_callback = $this->getConfigData('custom_callbackurl');
		if(Mage::helper('paytm')::CUSTOM_CALLBACK_URL==""){
			$callbackUrl = rtrim(Mage::getUrl('paytm/processing/response',array('_nosid'=>true)),'/');
		}else{
			$callbackUrl = Mage::helper('paytm')::CUSTOM_CALLBACK_URL;
		}
		$lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();
		$order = Mage::getSingleton('sales/order');
		$order->load($lastOrderId);
		$_totalData = $order->getData();
		$email = $_totalData['customer_email'];
		$telephone = $order->getBillingAddress()->getTelephone();
		//create array using which checksum is calculated
		$oldOrderId=$orderId=$this->getOrder()->getRealOrderId();
		if(Mage::helper('paytm')::APPEND_TIMESTAMP){
			$orderId=$orderId."_".time();
		}
		if(Mage::helper('paytm')::SAVE_PAYTM_RESPONSE){
			$tableName = Mage::getSingleton('core/resource')->getTableName('paytm_order_data');
			$tableCheck=Mage::getSingleton('core/resource')->getConnection('core_write')->isTableExists($tableName);
			$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
			if(!$tableCheck){
				$query="CREATE TABLE `".$tableName."` (`id` int(10) unsigned NOT NULL auto_increment, `order_id` text NOT NULL, `paytm_order_id` text NOT NULL, `transaction_id` text, `status` tinyint(1) DEFAULT 0, `paytm_response` text, `date_added` datetime DEFAULT CURRENT_TIMESTAMP, `date_modified` datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"; 
				$connection->query($query);
			}
			$query = "INSERT INTO ".$tableName." (`order_id`, `paytm_order_id`, `date_added`, `date_modified`) VALUES ('".$oldOrderId."', '".$orderId."', '".date('Y-m-d H:i:s')."', '".date('Y-m-d H:i:s')."')";
			$connection->query($query);
		}
		if($is_callback=='1'){
			$callbackUrl=$this->getConfigData('callback_url')!=''?$this->getConfigData('callback_url'):$callbackUrl;
		}
		$paytmParams = array();
		$paytmParams["body"] = array(
			"requestType"   => "Payment",
			"mid"           => trim($merid),
			"websiteName"   => trim($website),
			"orderId"       => $orderId,
			"callbackUrl"   => trim($callbackUrl),
			"txnAmount"     => array(
				"value"     => trim($price),
				"currency"  => "INR",
			),
			"userInfo"      => array(
				"custId"    => Mage::getSingleton('customer/session')->getCustomer()->getId()?Mage::getSingleton('customer/session')->getCustomer()->getId():trim($email),
			),
		);


		$checksum = Mage::helper('paytm')->generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $mer);//generate checksum
		$paytmParams["head"] = array(
			"signature"	=> $checksum
		);
		$postData = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
		$apiURL = Mage::helper('paytm')->getPaytmURL(Mage::helper('paytm')::INITIATE_TRANSACTION_URL,Mage::getStoreConfig('payment/paytm_cc/transaction_environment')).'?mid='.trim($merid).'&orderId='.$orderId;
		$result = $this->executecUrl($apiURL, $postData);
		
		$data['scripturl']=  str_replace('MID',trim($merid), Mage::helper('paytm')->getPaytmURL(Mage::helper('paytm')::CHECKOUT_JS_URL,Mage::getStoreConfig('payment/paytm_cc/transaction_environment')));
		$data['txntoken']= $result['body']['txnToken'];
		$data['orderid']= $orderId;
		$data['amount']= trim($price);

	  /* return $params; */
	  return $data;
    }

    protected function _debug($debugData)
    {
        if (method_exists($this, 'getDebugFlag')) {
            return parent::_debug($debugData);
        }

        if ($this->getConfigData('debug')) {
            Mage::log($debugData, null, 'payment_' . $this->getCode() . '.log', true);
        }
    }
}