<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
    namespace PPSL\Paytm\Model;
    use PPSL\Paytm\Helper\Data as DataHelper;

    class Paytm implements \Magento\Payment\Model\MethodInterface {
        const CODE = 'paytm';
        protected $_code = self::CODE;
        protected $_isInitializeNeeded = true;
        protected $_isGateway = true;
        protected $_isOffline = true;
        protected $_canUseInternal = true;
        protected $_canUseCheckout = true;
        protected $helper;
        protected $_minAmount = null;
        protected $_maxAmount = null;
        protected $_supportedCurrencyCodes = array('INR');
        protected $_formBlockType = 'PPSL\Paytm\Block\Form\Paytm';
        protected $_infoBlockType = 'PPSL\Paytm\Block\Info\Paytm';
        protected $urlBuilder;
        protected $_scopeConfig;
        protected $_eventManager;
        protected $_storeId;
        protected $_infoInstance;
        private $resourceConnection;
        private $dateTime;
        private $productMetadata;

        public function __construct(
            \Magento\Framework\Model\Context $context,
            \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
            \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
            \Magento\Payment\Helper\Data $paymentData,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Payment\Model\Method\Logger $logger,
            \Magento\Framework\UrlInterface $urlBuilder,
            \PPSL\Paytm\Helper\Data $helper,
            \Magento\Framework\App\ResourceConnection $resourceConnection,
            \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
            \Magento\Framework\App\ProductMetadataInterface $productMetadata
        ) {
            $this->helper = $helper;
            $this->_scopeConfig = $scopeConfig;
            $this->_eventManager = $context->getEventDispatcher();
            $this->_minAmount = "0.50";
            $this->_maxAmount = "1000000";
            $this->urlBuilder = $urlBuilder;
            $this->resourceConnection = $resourceConnection;
            $this->dateTime = $dateTime;
            $this->productMetadata = $productMetadata;
        }

        public function initialize($paymentAction, $stateObject) {
            $payment = $this->getInfoInstance();
            $order = $payment->getOrder();
            $order->setCanSendNewEmailFlag(false);
            $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
            $stateObject->setStatus('pending_payment');
            $stateObject->setIsNotified(false);
            return $this;
        }

        /* this function check ammount limit */
        public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null) {
            if ($quote && (
                $quote->getBaseGrandTotal() < $this->_minAmount || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
            ) {
                return false;
            }
            if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
                return false;
            }
            $checkResult = new \Magento\Framework\DataObject();
            $checkResult->setData('is_available', true);
            $this->_eventManager->dispatch(
                'payment_method_is_active',
                [
                    'result' => $checkResult,
                    'method_instance' => $this,
                    'quote' => $quote
                ]
            );
            return $checkResult->getData('is_available');
        }

        /* this function for currency check*/
        public function canUseForCurrency($currencyCode) {
            if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
                return false;
            }
            return true;
        }

        public function getCode()
        {
            return $this->_code;
        }

        public function getFormBlockType()
        {
            return $this->_formBlockType;
        }

        public function getInfoBlockType()
        {
            return $this->_infoBlockType;
        }

        public function getTitle()
        {
            return $this->getConfigData('title');
        }

        public function setStore($storeId)
        {
            $this->_storeId = $storeId;
        }

        public function getStore()
        {
            return $this->_storeId;
        }

        public function canOrder()
        {
            return false;
        }

        public function canAuthorize()
        {
            return false;
        }

        public function canCapture()
        {
            return false;
        }

        public function canCapturePartial()
        {
            return false;
        }

        public function canCaptureOnce()
        {
            return false;
        }

        public function canRefund()
        {
            return false;
        }

        public function canRefundPartialPerInvoice()
        {
            return false;
        }

        public function canVoid()
        {
            return false;
        }

        public function canUseInternal()
        {
            return $this->_canUseInternal;
        }

        public function canUseCheckout()
        {
            return $this->_canUseCheckout;
        }

        public function canEdit()
        {
            return false;
        }

        public function canFetchTransactionInfo()
        {
            return false;
        }

        public function fetchTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $transactionId)
        {
            return [];
        }

        public function isGateway()
        {
            return $this->_isGateway;
        }

        public function isOffline()
        {
            return $this->_isOffline;
        }

        public function isInitializeNeeded()
        {
            return $this->_isInitializeNeeded;
        }

        public function canUseForCountry($country)
        {
            if ($this->getConfigData('allowspecific') == 1) {
                $availableCountries = explode(',', (string) $this->getConfigData('specificcountry'));
                if (!in_array($country, $availableCountries)) {
                    return false;
                }
            }
            return true;
        }

        public function getInfoInstance()
        {
            if (!$this->_infoInstance instanceof \Magento\Payment\Model\InfoInterface) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We cannot retrieve the payment information object.')
                );
            }
            return $this->_infoInstance;
        }

        public function setInfoInstance(\Magento\Payment\Model\InfoInterface $info)
        {
            $this->_infoInstance = $info;
        }

        public function validate()
        {
            return $this;
        }

        public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
        {
            return $this;
        }

        public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
        {
            return $this;
        }

        public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
        {
            return $this;
        }

        public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
        {
            return $this;
        }

        public function cancel(\Magento\Payment\Model\InfoInterface $payment)
        {
            return $this;
        }

        public function void(\Magento\Payment\Model\InfoInterface $payment)
        {
            return $this;
        }

        public function canReviewPayment()
        {
            return false;
        }

        public function acceptPayment(\Magento\Payment\Model\InfoInterface $payment)
        {
            return false;
        }

        public function denyPayment(\Magento\Payment\Model\InfoInterface $payment)
        {
            return false;
        }

        public function getConfigData($field, $storeId = null)
        {
            if ($storeId === null) {
                $storeId = $this->getStore();
            }
            return $this->_scopeConfig->getValue(
                'payment/' . $this->getCode() . '/' . $field,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        public function assignData(\Magento\Framework\DataObject $data)
        {
            $this->_eventManager->dispatch(
                'payment_method_assign_data_' . $this->getCode(),
                [
                    \Magento\Payment\Observer\AbstractDataAssignObserver::METHOD_CODE => $this,
                    \Magento\Payment\Observer\AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
                    \Magento\Payment\Observer\AbstractDataAssignObserver::DATA_CODE => $data
                ]
            );
            $this->_eventManager->dispatch(
                'payment_method_assign_data',
                [
                    \Magento\Payment\Observer\AbstractDataAssignObserver::METHOD_CODE => $this,
                    \Magento\Payment\Observer\AbstractDataAssignObserver::MODEL_CODE => $this->getInfoInstance(),
                    \Magento\Payment\Observer\AbstractDataAssignObserver::DATA_CODE => $data
                ]
            );
            return $this;
        }

        public function isActive($storeId = null)
        {
            return (bool) (int) $this->getConfigData('active', $storeId);
        }

        public function getConfigPaymentAction()
        {
            return $this->getConfigData('payment_action');
        }

        public function getResource(){
            return $this->resourceConnection;
        }

        public function dbConnection(){ 
            $resource = $this->getResource();
            $connection = $resource->getConnection();
            return $connection;

        }
        public function paytmTable(){
            $resource = $this->getResource();
            $paytmTable = $resource->getTableName('paytm_order_data');
            return $paytmTable;
        }
        public function getDate(){
            return $this->dateTime->gmtDate();
        }

        /* this function return Paytm redirect form post */
        public function buildPaytmRequest($order) {
            $paytmOrderId=$magentoOrderId=$order->getRealOrderId();
            if($this->helper::APPEND_TIMESTAMP){
                $paytmOrderId=$magentoOrderId.'_'.time();
            }
            
            if($this->helper::SAVE_PAYTM_RESPONSE){
                $date = $this->getDate(); 
                $connection = $this->dbConnection(); 
                $tableName = $this->paytmTable();
                $connection->insert(
                    $tableName,
                    [
                        'order_id' => (string) $magentoOrderId,
                        'paytm_order_id' => (string) $paytmOrderId,
                        'date_added' => $date,
                        'date_modified' => $date
                    ]
                );
            }

            $callBackUrl=$this->urlBuilder->getUrl('paytm/Standard/Response', ['_secure' => true]);
            if($this->helper::CUSTOM_CALLBACK_URL!=''){
                $callBackUrl=$this->helper::CUSTOM_CALLBACK_URL;
            }
            if($this->getConfigData("custom_callbackurl")=='1'){
                $callBackUrl=$this->getConfigData("callback_url")!=''?$this->getConfigData("callback_url"):$callBackUrl;
            }
            $params = array(
                'MID' => trim((string) $this->getConfigData("MID")),               
                'TXN_AMOUNT' => round($order->getGrandTotal(), 2),
                'CHANNEL_ID' => $this->helper::CHANNEL_ID,
                //'INDUSTRY_TYPE_ID' => trim($this->getConfigData("Industry_id")),
                'WEBSITE' => trim((string) $this->getConfigData("Website")),
                'CUST_ID' => $order->getCustomerEmail(),
                'ORDER_ID' => $paytmOrderId,                     
                'EMAIL' => $order->getCustomerEmail(),
                'CALLBACK_URL' => trim((string) $callBackUrl)
            );    
            if(isset($order->paytmPromoCode)){
                $params['PROMO_CAMP_ID']=$order->paytmPromoCode;
            }
            if($this->getConfigData("environment") ==1){
                $paytmDmain = $this->helper::PRODUCTION_HOST;
                $url = $this->helper::TRANSACTION_TOKEN_URL_PRODUCTION.$params["MID"] . "&orderId=" . $params["ORDER_ID"];
            }else{
                $paytmDmain = $this->helper::STAGING_HOST;
                $url = $this->helper::TRANSACTION_TOKEN_URL_STAGING.$params["MID"] . "&orderId=" . $params["ORDER_ID"];
            }
            $checksum = $this->helper->generateSignature($params, $this->getConfigData("merchant_key"));
            $params['CHECKSUMHASH'] = $checksum;
                $paytmParams = array();
                $paytmParams["body"] = array(
                    "requestType" => "Payment",
                    "mid" => $params["MID"],
                    "websiteName" => $params["WEBSITE"],
                    "orderId" => $params["ORDER_ID"],
                    "callbackUrl" => $params["CALLBACK_URL"],
                    "txnAmount" => array(
                        "value" => $params["TXN_AMOUNT"],
                        "currency" => "INR",
                    ),
                    "userInfo" => array(
                        "custId" => $params["CUST_ID"],
                    ),
                );
                // for bank offers
                if($this->getConfigData('bankoffer') ==1){
                    $paytmParams["body"]["simplifiedPaymentOffers"]["applyAvailablePromo"]= "true";
                }
                // for emi subvention
                if($this->getConfigData('emisubvention') ==1){
                    $paytmParams["body"]["simplifiedSubvention"]["customerId"]= $params['CUST_ID'];
                    $paytmParams["body"]["simplifiedSubvention"]["subventionAmount"]= $params['TXN_AMOUNT'];
                    $paytmParams["body"]["simplifiedSubvention"]["selectPlanOnCashierPage"]= "true";
                    //$paytmParams["body"]["simplifiedSubvention"]["offerDetails"]["offerId"]= 1;
                }
                // for dc emi
                if($this->getConfigData('dcemi') ==1){
                    $paytmParams["body"]["userInfo"]["mobile"]= $order->getShippingAddress()->getTelephone();
                }
                $generateSignature = $this->helper->generateSignature(json_encode($paytmParams['body'], JSON_UNESCAPED_SLASHES), $this->getConfigData("merchant_key"));

                $paytmParams["head"] = array(
                    "signature" => $generateSignature
                );

                
                $post_data_string = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
                $headers = array("Content-Type: application/json");
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                $response = curl_exec($ch);
                $response_array = json_decode($response, TRUE); 
                if(!empty($response_array['body']['txnToken'])){
                     $txnToken = $response_array['body']['txnToken'];
                }else{
                     $txnToken = " ";
                }
                $version = $this->getLastUpdate();
                $params['X-REQUEST-ID']=$this->helper::X_REQUEST_ID.str_replace('|', '_', str_replace(' ', '-', $version));
                $inputForm='<script type="application/javascript" crossorigin="anonymous" src="'.$paytmDmain.'"merchantpgpui/checkoutjs/merchants/'.$params['MID'].'.js"></script><input type="hidden" value="'.$txnToken.'"  name="txn_token" id="txn_token" /> <input id="OrderID" value="'.$params['ORDER_ID'].'"  type="hidden" /><input id="amount" value="'.$params['TXN_AMOUNT'].'"  type="hidden" />';
                $datareturn['inputForm'] = $inputForm;
                $datareturn['ORDER_ID'] = $params['ORDER_ID'];
                $datareturn['txnToken'] = $txnToken;
                $datareturn['TXN_AMOUNT'] = $params['TXN_AMOUNT'];
                $datareturn['MAGENTO_VERSION'] = $this->getMagentoVerionInPlugin();
                $datareturn['PLUGIN_VERSION'] = $this->getpluginversion();
                return array('response'=>$datareturn);
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
           return str_replace('MID', $this->getConfigData("MID"), $this->helper->getPaytmURL($this->helper::CHECKOUT_JS_URL,$this->getConfigData('environment'),$this->getConfigData("MID")));
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


        public function getMagentoVerionInPlugin() {
            return $this->productMetadata->getVersion();
        }

        /* this function return Magento version and last update date of plugin */
        public function getLastUpdate(){
            $version = $this->productMetadata->getVersion();

            $lastUpdated=date('d M Y',strtotime($this->helper::LAST_UPDATED));
            return $version."|".$lastUpdated;
        }

        /* this function for return Invert Logo Option */
        public function getLogo() {      
            if($this->getConfigData("invertlogo") == 1){
                return "https://staticpg.paytmpayments.com/pg_plugins_logo/paytm_logo_invert.svg";
            }      
            return "https://staticpg.paytmpayments.com/pg_plugins_logo/paytm_logo_paymodes.svg";
        }
    
    }
?>
