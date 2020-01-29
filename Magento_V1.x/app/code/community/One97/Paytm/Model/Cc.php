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
		$params = 	array(
			'MID' =>	trim($merid),  				
			'TXN_AMOUNT' =>	trim($price),
			'CHANNEL_ID' => Mage::helper('paytm')::CHANNEL_ID,
			'INDUSTRY_TYPE_ID' => trim($industry_type),
			'WEBSITE' => trim($website),
			'CUST_ID' => Mage::getSingleton('customer/session')->getCustomer()->getId(),
			'ORDER_ID'	=>	$orderId,   				    
			'EMAIL'=> trim($email),
			'MOBILE_NO' => preg_replace('#[^0-9]{0,13}#is','',$telephone)
		);
		if($is_callback=='1'){
			$callbackUrl=$this->getConfigData('callback_url')!=''?$this->getConfigData('callback_url'):$callbackUrl;
		}
		$params['CALLBACK_URL'] = trim($callbackUrl);
		
		//generate customer id in case this is a guest checkout
		if(empty($params['CUST_ID'])){
			$params['CUST_ID'] = trim($email);
		}
		$checksum = Mage::helper('paytm')->generateSignature($params, $mer);//generate checksum
		$params['CHECKSUMHASH'] = $checksum;
		$params['X-REQUEST-ID']=Mage::helper('paytm')::X_REQUEST_ID.Mage::getVersion()."_".date("d-F-Y", strtotime(Mage::helper('paytm')::LAST_UPDATED));
	  return $params;
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