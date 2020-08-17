<?php

class One97_paytm_ProcessingController extends Mage_Core_Controller_Front_Action {
    protected $_successBlockType = 'paytm/success';
    protected $_failureBlockType = 'paytm/failure';
    protected $_cancelBlockType = 'paytm/cancel';
    protected $_order = NULL;
    protected $_paymentInst = NULL;
    public $isvalid;
    
    //Get singleton of Checkout Session Model
    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
    
    //when customer selects paytm payment method
    public function redirectAction() {
        try {
            Mage::log("Request getting from Magento for Paytm",null,'paytmDebugLogFile.log',true);
            $session = $this->_getCheckout();
            //get order singleton
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
            }

            //set order status
            if ($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                $order->setState(
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    $this->_getPendingPaymentStatus(),
                    Mage::helper('paytm')->__('Customer was redirected to paytm.')
                )->save();
            }
            //save order and quote ids
             if ($session->getQuoteId() && $session->getLastSuccessQuoteId()) {
                $session->setpaytmQuoteId($session->getQuoteId());
                $session->setpaytmSuccessQuoteId($session->getLastSuccessQuoteId());
                $session->setpaytmRealOrderId($session->getLastRealOrderId());
                $session->getQuote()->setIsActive(false)->save();
                $session->clear();
            }
            //load basic blank page
            $this->loadLayout();
            $this->renderLayout();
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }
    
    //handle callback values and takes appropriate action
    public function responseAction() {
        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');
        Mage::log($request);
        if (!$order->getId()) {
            Mage::log('No order for processing found');
        }
        $request = $this->_checkReturnedPost();
        Mage::log("Response getting from Paytm",null,'paytmDebugLogFile.log',true);
        Mage::log($request,null,'paytmDebugLogFile.log',true);
        $orderIdArr=explode('_', $request['ORDERID']);
        $orderIdMagento=$orderIdArr[0];
        try {
            $parameters = array();
            foreach($request as $key=>$value) {
                $parameters[$key] = $request[$key];
            }
            $session = $this->_getCheckout();
            $txnstatus = false;
            $authStatus = false;
            $mer_encrypted = Mage::getStoreConfig('payment/paytm_cc/inst_key');
            $const = (string)Mage::getConfig()->getNode('global/crypt/key');
            $mer_decrypted= Mage::helper('paytm')->decrypt($mer_encrypted,$const);
            
            $transactionProcess="STATUS_FRAUD";
            $tableName = Mage::getSingleton('core/resource')->getTableName('paytm_order_data');
            if(trim($request['ORDERID'])!='' && trim($request['MID'])){
                $isValidChecksum = false;
                if(isset($parameters['CHECKSUMHASH'])){
                    $tmpChecksum=$parameters['CHECKSUMHASH'];
                    unset($parameters['CHECKSUMHASH']);
                    $return = Mage::helper('paytm')->verifySignature($parameters, $mer_decrypted, $tmpChecksum);
                    if($return == "TRUE")
                        $isValidChecksum = true;
                }
                if($isValidChecksum){
                    $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $query="SELECT * FROM ".$tableName." WHERE order_id='".$orderIdMagento."' AND paytm_order_id='".$request['ORDERID']."' ORDER BY ID DESC";
                    $results = $connectionRead->fetchAll($query);
                    if(isset($results[0]['id'])){
                        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                        $query = "UPDATE ".$tableName." SET transaction_id='".@$request['TXNID']."', paytm_response='".json_encode($request, JSON_UNESCAPED_SLASHES)."', date_modified='".date('Y-m-d H:i:s')."' WHERE id='".$results[0]['id']."'";
                        $connection->query($query);
                    }
                    $requestParamList = array("MID" => $request['MID'] , "ORDERID" => $request['ORDERID']);
                    $StatusCheckSum = Mage::helper('paytm')->generateSignature($requestParamList, $mer_decrypted);
                    $requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
                    
                    $transactionEnvironment = Mage::getStoreConfig('payment/paytm_cc/transaction_environment');
                    $const = (string)Mage::getConfig()->getNode('global/crypt/key');
                    $check_status_url=Mage::helper('paytm')->getTransactionStatusURL($transactionEnvironment);
                    $responseParamList=array();
                    $retry = Mage::helper('paytm')::MAX_RETRY_COUNT;
                    do{
                        $responseParamList = Mage::helper('paytm')->executecUrl($check_status_url, $requestParamList);
                        $retry++;
                    } while(empty($responseParamList) && $retry < $maxRetryCount);
                    if(!empty($responseParamList)){
                        if($responseParamList['STATUS']=="TXN_SUCCESS" && $responseParamList['TXNAMOUNT']==$request["TXNAMOUNT"]){
                            if($request["STATUS"]!="TXN_SUCCESS"){
                                $request=array();
                                $request=$responseParamList;
                            }
                            $transactionProcess="STATE_SUCCESS";
                        }else if($responseParamList['STATUS']=="PENDING"){
                            if($request["STATUS"]!="PENDING"){
                                $request=array();
                                $request=$responseParamList;
                            }
                            $transactionProcess="STATE_PENDING_PAYMENT";
                        }else{
                            // $request=array();
                            // $request=$responseParamList;
                            $transactionProcess="STATE_CANCELED";
                        }
                    }else{
                        $transactionProcess="CURL_NOT_ENABLE";
                    }
                }
            }
            if(isset($results[0]['id'])){
                $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                if($transactionProcess=="STATE_SUCCESS"){
                    $query = "UPDATE ".$tableName." SET paytm_response='".json_encode($request, JSON_UNESCAPED_SLASHES)."', status='1', date_modified='".date('Y-m-d H:i:s')."' WHERE id='".$results[0]['id']."'";
                }else{
                    $query = "UPDATE ".$tableName." SET paytm_response='".json_encode($request, JSON_UNESCAPED_SLASHES)."', date_modified='".date('Y-m-d H:i:s')."' WHERE id='".$results[0]['id']."'";
                }
                $connection->query($query);
            }
            switch ($transactionProcess) {
                case 'STATE_SUCCESS':
                    $this->_processSale(array("ORDERID"=>$orderIdMagento));
                    $order_mail = new Mage_Sales_Model_Order();
                    $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
                    $order_mail->loadByIncrementId($incrementId);
                    $i = Mage::getVersion();
                    $updatedVersion=false;
                    if(strpos($i,"1.9")===0){
                        $updatedVersion=true;
                    }
                    if(!$updatedVersion){   // latest from 1.9.0 version, not support sendNewOrderEmail() fumction 
                        try{
                            $order_mail->sendNewOrderEmail(); 
                        } catch (Exception $ex) {  }
                    }
                    break;
                case 'STATE_PENDING_PAYMENT':
                    $this->_processCancel(array("ORDERID"=>$orderIdMagento, "RESPMSG"=>$request['RESPMSG'], "STATUS"=>$request['STATUS']));
                    break;
                case 'STATE_CANCELED':
                    $this->_processCancel(array("ORDERID"=>$orderIdMagento, "RESPMSG"=>$request['RESPMSG']));
                    break;
                case 'CURL_NOT_ENABLE':
                    $this->_processFail(array("ORDERID"=>$orderIdMagento, "failCase"=>"CURL_NOT_ENABLE"));
                    break;
                case 'STATUS_FRAUD':
                    $this->_processFail(array("ORDERID"=>$orderIdMagento, "failCase"=>"STATUS_FRAUD"));
                    break;
                
                default:
                    # code...
                    break;
            }
        } catch (Mage_Core_Exception $e) {
            $this->getResponse()->setBody(
                $this->getLayout()
                    ->createBlock($this->_failureBlockType)
                    ->setOrder($this->_order)
                    ->toHtml()
            );
            $this->_processFail(array("ORDERID"=>$orderIdMagento));
        }
    }

    protected function _getPendingPaymentStatus() {
        return Mage::helper('paytm')->getPendingPaymentStatus();
    }

    // Checking POST variables.
    protected function _checkReturnedPost() {
        // check request type
        if (!$this->getRequest()->isPost())
            Mage::throwException('Wrong request type.'); 

        // get request variables
        $request = $this->getRequest()->getPost();
        if (empty($request))
            Mage::throwException('Request doesn\'t contain POST elements.');
        
        Mage::log($request); 
        // check order id
        if (empty($request['ORDERID']) )
            Mage::throwException('Missing or invalid order ID');

        $orderIdArr=explode('_', $request['ORDERID']);
        $orderIdMagento=$orderIdArr[0]; 

        // load order for further validation
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderIdMagento);
        if (!$this->_order->getId())
            Mage::throwException('Order not found');

        return $request;
    }

    //if success process sale
    protected function _processSale($request) {
        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($request['ORDERID']);
        
        Mage::log("This transaction success by Paytm plugin",null,'paytmDebugLogFile.log',true);
        if(Mage::getStoreConfig('payment/paytm_cc/auto_invoice')){
            //save transaction information
            $invoice = $order->prepareInvoice();
            $invoice->register()->capture();
            Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();
        }

        $order->addStatusToHistory(Mage::getStoreConfig('payment/paytm_cc/success_transaction_status'),Mage::helper('paytm')->__('Payment successful through Paytm PG'));
        $order->save();
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_successBlockType)
                ->setOrder($this->_order)
                ->toHtml()
        );
    }

    //cancel order if failure
    protected function _processFail($request) {
        $mass="Checksum Mismatch.";
        $isPending=false;
        if(isset($request['STATUS'])){
            if($request['STATUS']=="PENDING"){
                $isPending=true;
            }
        }
        if($request['failCase']=='CURL_NOT_ENABLE'){
            $mass="It seems some issue in server to server communication. Kindly connect with administrator.";
        }else if($isPending){
            $mass=$request['RESPMSG'];
        }
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($request['ORDERID']);
        if ($this->_order->canCancel()) {
            $this->_order->cancel();
            if($isPending){
                $this->_order->addStatusToHistory($this->_getPendingPaymentStatus(), Mage::helper('paytm')->__('Payment was failed, due to reason: '.$mass));
            }else{
                $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATUS_FRAUD, Mage::helper('paytm')->__('Payment was failed, due to reason: '.$mass));
            }
            $this->_order->save();
        }
        $session = $this->_getCheckout();
        if(isset($request['failCase'])){
            $session->addError(Mage::helper('paytm')->__('Reason: '.$mass));
        }
        $this->_redirect('checkout/cart');
    }
    
    //cancel order if failure
    protected function _processCancel($request) {
        // cancel order
        $mass="Not Defined!";
        if(isset($request['RESPMSG'])){
            $mass=$request['RESPMSG'];
        }
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($request['ORDERID']);
        if ($this->_order->canCancel()) {
            $this->_order->cancel();
            $this->_order->addStatusToHistory(Mage::getStoreConfig('payment/paytm_cc/cancel_transaction_status'), Mage::helper('paytm')->__('Payment was canceled, due to Reason: '.$mass));
            $this->_order->save();
        }
        $session = $this->_getCheckout();
        $session->addError(Mage::helper('paytm')->__('Reason: '.$mass));
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_cancelBlockType)
                ->setOrder($this->_order)
                ->toHtml()
        );
    }

    public function curltestAction() {
        $debug = array();
        if(!function_exists("curl_init")){
            $debug[0]["info"][] = "cURL extension is either not available or disabled. Check phpinfo for more info.";

        }else{ 
            // this site homepage URL
            $testing_urls=array();

            if(!empty($_GET)){
                foreach ($_GET as $key => $value) {
                    $testing_urls[]=$value;
                }
            }else{
                $testing_urls = array(
                    Mage::getBaseUrl(),
                    "https://www.gstatic.com/generate_204",
                    Mage::helper('paytm')::TRANSACTION_STATUS_URL_PRODUCTION,
                    Mage::helper('paytm')::TRANSACTION_STATUS_URL_STAGING
                );
            }
            
            // loop over all URLs, maintain debug log for each response received
            foreach($testing_urls as $key=>$url){

                $debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $res = curl_exec($ch);

                if (!curl_errno($ch)) {
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $debug[$key]["info"][] = "cURL executed succcessfully.";
                    $debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";

                    // $debug[$key]["content"] = $res;

                } else {
                    $debug[$key]["info"][] = "Connection Failed !!";
                    $debug[$key]["info"][] = "Error Code: <b>" . curl_errno($ch) . "</b>";
                    $debug[$key]["info"][] = "Error: <b>" . curl_error($ch) . "</b>";
                    break;
                }

                if((in_array($url, array(Mage::helper('paytm')::TRANSACTION_STATUS_URL_STAGING , Mage::helper('paytm')::TRANSACTION_STATUS_URL_PRODUCTION)))){
                    $debug[$key]["info"][] = "Response: <br/><!----- Response Below ----->" . $res;
                }
                curl_close($ch);
            }
        }
        foreach($debug as $k=>$v){
            echo "<ul>";
            foreach($v["info"] as $info){
                echo "<li>".$info."</li>";
            }
            echo "</ul>";
            echo "<hr/>";
        }
        die;
    }

    //runs on success of payment
    public function successAction() {
        try {
            $session = $this->_getCheckout();
            $session->unspaytmRealOrderId();
            $session->setQuoteId($session->getpaytmQuoteId(true));
            // $session->setLastSuccessQuoteId($session->getpaytmSuccessQuoteId(true));
            $session->setLastSuccessQuoteId($session->getLastQuoteId());
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {
            $this->_debug('paytm error: ' . $e->getMessage());
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }
    
    public function failAction() {
        // set quote to active
        $session = $this->_getCheckout();       
        if ($quoteId = $session->getpaytmQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        Mage::log("failed");
        $session->addError(Mage::helper('paytm')->__('The order has failed.'));
        $this->_redirect('checkout/cart');
    }
    
    //runs on cancel action
    public function cancelAction() {
        // set quote to active
        $session = $this->_getCheckout(); 
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        if (!$order->getId()) {
            Mage::throwException('No order for processing found');
        }
        
        if ($quoteId = $session->getpaytmQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        $this->_redirect('checkout/cart');
    }

    public function statsucheckAction(){
        $json = array("response" => "false", "tableBody" => "");
        $tableBody='';
        if(isset($_POST['paytmResponseId'])){
            if(trim($_POST['paytmResponseId'])!=''){
                $tableName = Mage::getSingleton('core/resource')->getTableName('paytm_order_data');
                $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_write');
                $query="SELECT * FROM ".$tableName." WHERE id='".$_POST['paytmResponseId']."' ORDER BY ID DESC";
                $results = $connectionRead->fetchAll($query);
                if(isset($results[0]['id'])){
                    $merchantID = Mage::getStoreConfig('payment/paytm_cc/inst_id');
                    $merchantKey = Mage::getStoreConfig('payment/paytm_cc/inst_key');
                    $transactionEnvironment = Mage::getStoreConfig('payment/paytm_cc/transaction_environment');
                    $const = (string)Mage::getConfig()->getNode('global/crypt/key');
                    $mid= Mage::helper('paytm')->decrypt($merchantID,$const);
                    $mKey= Mage::helper('paytm')->decrypt($merchantKey,$const);
                    $check_status_url=Mage::helper('paytm')->getTransactionStatusURL($transactionEnvironment);
                    $requestParamList = array("MID" => $mid , "ORDERID" => $results[0]['paytm_order_id']);
                    $StatusCheckSum = Mage::helper('paytm')->generateSignature($requestParamList, $mKey);
                    $requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
                    $responseParamList=array();
                    $retry = Mage::helper('paytm')::MAX_RETRY_COUNT;
                    do{
                        $responseParamList = Mage::helper('paytm')->executecUrl($check_status_url, $requestParamList);
                        $retry++;
                    } while(empty($responseParamList) && $retry < $maxRetryCount);
                    if(!empty($responseParamList)){
                        if(isset($results[0]['id'])){
                            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
                            if($responseParamList['STATUS']=="TXN_SUCCESS"){
                                $query = "UPDATE ".$tableName." SET paytm_response='".json_encode($responseParamList, JSON_UNESCAPED_SLASHES)."', status='1', date_modified='".date('Y-m-d H:i:s')."' WHERE id='".$results[0]['id']."'";
                            }else{
                                $query = "UPDATE ".$tableName." SET paytm_response='".json_encode($responseParamList, JSON_UNESCAPED_SLASHES)."', date_modified='".date('Y-m-d H:i:s')."' WHERE id='".$results[0]['id']."'";
                            }
                            $connection->query($query);
                        }
                        $jsonResponse = $responseParamList;
                        if(is_array($jsonResponse)){
                            ksort($jsonResponse);
                            $successResponse=false;
                            foreach($jsonResponse as $key=>$value){
                                if($key=="STATUS"){
                                    $order = Mage::getModel('sales/order');
                                    $order->loadByIncrementId($results[0]['order_id']);
                                    if($value=="TXN_SUCCESS"){
                                        //save transaction information
                                        $invoice = $order->prepareInvoice();
                                        $invoice->register()->capture();
                                        Mage::getModel('core/resource_transaction')
                                                    ->addObject($invoice)
                                                    ->addObject($invoice->getOrder())
                                                    ->save();

                                        $order->addStatusToHistory(Mage::getStoreConfig('payment/paytm_cc/success_transaction_status'),Mage::helper('paytm')->__('Payment successful through Paytm PG in fetchStatus API.'));
                                        $order->save();

                                        $successResponse=true;
                                    }else if($value=="TXN_FAILURE"){
                                        $order->cancel();
                                        $order->addStatusToHistory(Mage::getStoreConfig('payment/paytm_cc/cancel_transaction_status'), Mage::helper('paytm')->__('Payment was canceled, due to Reason: '.$jsonResponse['RESPMSG']));
                                        $order->save();
                                    }
                                    $tableBody.='<tr class="even pointer"> <td class=" ">'.$key.'</td> <td class="a-right a-right "><span style="font-weight: 900;color:red;">'.$value.'</span> </td> </tr>';
                                }else{
                                    $tableBody.='<tr class="even pointer"> <td class=" ">'.$key.'</td> <td class="a-right a-right ">'.$value.' </td> </tr>';
                                }
                            }
                            if($successResponse){
                                $json = array("response" => "success", "tableBody" => $tableBody);
                            }else{
                                $json = array("response" => "false", "tableBody" => $tableBody);
                            }
                        }
                    }
                }
            }
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($json));
    }

    public function versioncheckAction(){
        $json = array("version" => Mage::getVersion(), "lastupdate" => date("d-F-Y", strtotime(Mage::helper('paytm')::LAST_UPDATED)), "pluginVersion" => Mage::helper('paytm')::PLUGIN_VERSION, "phpCurlVersion" => Mage::helper('paytm')->getcURLversion());
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($json));
    }
}
