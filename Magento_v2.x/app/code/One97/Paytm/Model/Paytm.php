<?php
    namespace One97\Paytm\Model;
    use One97\Paytm\Helper\Data as DataHelper;

    class Paytm extends \Magento\Payment\Model\Method\AbstractMethod {
        const CODE = 'paytm';
        protected $_code = self::CODE;
        protected $_isInitializeNeeded = true;
        protected $_isGateway = true;
        protected $_isOffline = true;
        protected $helper;
        protected $_minAmount = null;
        protected $_maxAmount = null;
        protected $_supportedCurrencyCodes = array('INR');
        protected $_formBlockType = 'One97\Paytm\Block\Form\Paytm';
        protected $_infoBlockType = 'One97\Paytm\Block\Info\Paytm';
        protected $urlBuilder;

        public function __construct(
            \Magento\Framework\Model\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
            \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
            \Magento\Payment\Helper\Data $paymentData,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Payment\Model\Method\Logger $logger,
            \Magento\Framework\UrlInterface $urlBuilder,
            \One97\Paytm\Helper\Data $helper
        ) {
            $this->helper = $helper;
            parent::__construct(
                $context,
                $registry,
                $extensionFactory,
                $customAttributeFactory,
                $paymentData,
                $scopeConfig,
                $logger
            );
            $this->_minAmount = "0.50";
            $this->_maxAmount = "1000000";
            $this->urlBuilder = $urlBuilder;
        }

        public function initialize($paymentAction, $stateObject) {
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false);
            $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $stateObject->setStatus('pending_payment');
            $stateObject->setIsNotified(false);
        }

        /* this function check ammount limit */
        public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
            if ($quote && (
                $quote->getBaseGrandTotal() < $this->_minAmount || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
            ) {
                return false;
            }
            return parent::isAvailable($quote);
        }

        /* this function for currency check*/
        public function canUseForCurrency($currencyCode) {
            if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
                return false;
            }
            return true;
        }

        /* this function return Paytm redirect form post */
        public function buildPaytmRequest($order) {
            $paytmOrderId=$magentoOrderId=$order->getRealOrderId();
            if($this->helper::APPEND_TIMESTAMP){
                $paytmOrderId=$magentoOrderId.'_'.time();
            }
            
            if($this->helper::SAVE_PAYTM_RESPONSE){
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                
                $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
                $date = $objDate->gmtDate();

                $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                $connection = $resource->getConnection();

                $tableName = $resource->getTableName('paytm_order_data');
                if(!$connection->isTableExists($tableName)){
                    $sql = "CREATE TABLE ".$tableName."(id INT(11) PRIMARY KEY AUTO_INCREMENT, order_id TEXT NOT NULL, paytm_order_id TEXT NOT NULL, transaction_id TEXT, status TINYINT(1)  DEFAULT '0', paytm_response TEXT, date_added DATETIME, date_modified DATETIME )";
                    $connection->query($sql);
                }
                $tableName = $resource->getTableName('paytm_order_data');
                $sql = "INSERT INTO ".$tableName."(order_id, paytm_order_id, date_added, date_modified) VALUES ('".$magentoOrderId."', '".$paytmOrderId."', '".$date."', '".$date."')";
                $connection->query($sql);
            }

            $amount         = round($order->getGrandTotal(), 2);

            $paramData = array('amount' => $amount, 'order_id' => $paytmOrderId, 'cust_id' => $order->getCustomerEmail(), 'email' => $order->getCustomerEmail());
            $checkout_url          = str_replace('MID',$this->getConfigData("MID"), $this->helper->getPaytmURL($this->getConfigData('CHECKOUT_JS_URL'), $this->getConfigData('environment')));
            $data                  = $this->blinkCheckoutSend($paramData);
            $txn_token             = $data['txnToken'];
            return $data;
            // $version = $this->getLastUpdate();
            // $params['X-REQUEST-ID']=$this->helper::X_REQUEST_ID.str_replace('|', '_', str_replace(' ', '-', $version));
            
        }

        /* this function for checksum validation */
        public function validateResponse($res,$order_id) {
            $checksum = @$res["CHECKSUMHASH"];
            unset($res["CHECKSUMHASH"]);
            if ($this->helper->verifySignature($res,$this->getConfigData("merchant_key"),$checksum)) {
                $result = true;
            } else {
                $result = false;
            }
            return $result;
        }

        /* this function for php curl version */
        public function getcURLversion() {
            return $this->helper->getcURLversion();
        }

        /* this function for php curl version */
        public function getpluginversion() {
            return $this->helper::PLUGIN_VERSION;
        }
        
        /* this function for genrating checksum */
        public function generateStatusChecksum($requestParamList) {
            $result = $this->helper->generateSignature($requestParamList,$this->getConfigData("merchant_key"));            
            return $result;
        }

        /* this function for return MID */
        public function getMID() {            
            return $this->getConfigData("MID");
        }

        /* this function for return checkoutjs url */
        public function getcheckoutjsurl() {  
           return str_replace('MID', $this->getConfigData("MID"), $this->helper->getPaytmURL($this->helper::CHECKOUT_JS_URL,$this->getConfigData('environment')));
        }

        public function autoInvoiceGen() {
            $result = $this->getConfigData("payment_action");            
            return $result;
        }

        /* this function return transaction URL from admin config */
        public function getRedirectUrl() {
            return $this->helper->getTransactionURL($this->getConfigData('environment'));
        }
        
        /* this function return transaction status URL from admin config */
        public function getNewStatusQueryUrl() {
            return $this->helper->getTransactionStatusURL($this->getConfigData('environment'));
        }

        /* this function return success order status */
        public function getSuccessOrderStatus() {
            return $this->getConfigData("success_order_status");
        }
        
        /* this function return fail order status */
        public function getFailOrderStatus() {
            return $this->getConfigData("fail_order_status");
        }

        /* this function return Magento version and last update date of plugin */
        public function getLastUpdate(){
            $objectManagerVs = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata = $objectManagerVs->get('Magento\Framework\App\ProductMetadataInterface');
            $version = $productMetadata->getVersion();

            $lastUpdated=date('d M Y',strtotime($this->helper::LAST_UPDATED));
            return $version."|".$lastUpdated;
        }
        public function blinkCheckoutSend($paramData = array()){

            $callBackUrl= $this->urlBuilder->getUrl('paytm/Standard/Response', ['_secure' => true]);
            if($this->helper::CUSTOM_CALLBACK_URL!=''){
                $callBackUrl=$this->helper::CUSTOM_CALLBACK_URL;
            }
            if($this->getConfigData("custom_callbackurl")=='1'){
                $callBackUrl=$this->getConfigData("callback_url")!=''?$this->getConfigData("callback_url"):$callBackUrl;
            }
            
            $apiURL = $this->helper->getPaytmURL($this->helper::INITIATE_TRANSACTION_URL, $this->getConfigData('environment')) . '?mid='.$this->getConfigData("MID").'&orderId='.$paramData['order_id'];
           $paytmParams = array();
    
           $paytmParams["body"] = array(
               "requestType"   => "Payment",
               "mid"           => $this->getConfigData("MID"),
               "websiteName"   => trim($this->getConfigData("Website")),
               "orderId"       => $paramData['order_id'],
               "callbackUrl"   => $callBackUrl,
               "txnAmount"     => array(
                   "value"     => strval($paramData['amount']),
                   "currency"  => "INR",
               ),
               "userInfo"      => array(
                   "custId"    => $paramData['cust_id'],
               ),
           );
    
           /*
           * Generate checksum by parameters we have in body
           * Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
           */
           $checksum = $this->helper->generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES),$this->getConfigData("merchant_key"));
    
           $paytmParams["head"] = array(
               "signature"	=> $checksum
           );
           //print_r($paytmParams);
    
           $response = $this->helper->executecUrl($apiURL, $paytmParams);
           $data = array('orderId' => $paramData['order_id'], 'amount' => $paramData['amount']);

           $data['pluginVersion'] = $this->helper::PLUGIN_VERSION;
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
           $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
           $version = $productMetadata->getVersion(); //will return the magento version
            $data['magentoVersion'] = $version;
           if(!empty($response['body']['txnToken'])){
               $data['txnToken'] = $response['body']['txnToken'];
           }else{
               $data['txnToken'] = '';
           }
           $data['apiurl'] = $apiURL;
           return $data;
       }
       public function getDefaultCallbackUrl(){
        $callBackUrl=$this->urlBuilder->getUrl('paytm/Standard/Response', ['_secure' => true]);
        if($this->helper::CUSTOM_CALLBACK_URL!=''){
            $callBackUrl=$this->helper::CUSTOM_CALLBACK_URL;
        }
        if($this->getConfigData("custom_callbackurl")=='1'){
            $callBackUrl=$this->getConfigData("callback_url")!=''?$this->getConfigData("callback_url"):$callBackUrl;
        }
        return $callBackUrl;

       }
    }
?>
