<?php


class One97_paytm_ProcessingController extends Mage_Core_Controller_Front_Action
{
    protected $_successBlockType = 'paytm/success';
    protected $_failureBlockType = 'paytm/failure';
    protected $_cancelBlockType = 'paytm/cancel';

    protected $_order = NULL;
    protected $_paymentInst = NULL;
    public $isvalid;
    
     //Get singleton of Checkout Session Model
     
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    
     //when customer selects paytm payment method
     
    public function redirectAction()
    {
        try {
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
    public function responseAction()
    {
            $session = $this->_getCheckout();
            $order = Mage::getModel('sales/order');
            
            $request = $this->_checkReturnedPost();
        try {
            
            Mage::log($request);
            $parameters = array();
            foreach($request as $key=>$value)
            {
            $parameters[$key] = $request[$key];
            }
            $session = $this->_getCheckout();
            $isValidChecksum = false;
            $txnstatus = false;
            $authStatus = false;
            $mer_encrypted = Mage::getStoreConfig('payment/paytm_cc/inst_key');
            $const = (string)Mage::getConfig()->getNode('global/crypt/key');
            $mer_decrypted= Mage::helper('paytm')->decrypt_e($mer_encrypted,$const);
            
            $merid_encrypted = Mage::getStoreConfig('payment/paytm_cc/inst_id');
            $const = (string)Mage::getConfig()->getNode('global/crypt/key');
            $merid_decrypted= Mage::helper('paytm')->decrypt_e($merid_encrypted,$const);
            
            //setting order status
            $order = Mage::getModel('sales/order');
            
            $order->loadByIncrementId($request['ORDERID']);
            if (!$order->getId()) {
                Mage::log('No order for processing found');
            }
            
            
            //check returned checksum
            if(isset($request['CHECKSUMHASH']))
            {
                $return = Mage::helper('paytm')->verifychecksum_e($parameters, $mer_decrypted, $request['CHECKSUMHASH']);
                if($return == "TRUE")
                $isValidChecksum = true;
                
            }
            
            if($request['STATUS'] == "TXN_SUCCESS"){
                $txnstatus = true;
            }
            
            $_testurl = NULL;
            $transaction_status_url = Mage::getStoreConfig('payment/paytm_cc/transaction_status_url');
            $const = (string)Mage::getConfig()->getNode('global/crypt/key');
            $_testurl= Mage::helper('paytm')->decrypt_e($transaction_status_url,$const);

            if($txnstatus && $isValidChecksum){
                // Create an array having all required parameters for status query.
                $requestParamList = array("MID" => $merid_decrypted , "ORDERID" => $request['ORDERID']);
                
                $StatusCheckSum = Mage::helper('paytm')->getChecksumFromArray($requestParamList, $mer_decrypted);
                            
                $requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
                
                // Call the PG's getTxnStatus() function for verifying the transaction status.
                $transaction_status_url = Mage::getStoreConfig('payment/paytm_cc/transaction_status_url');
                $const = (string)Mage::getConfig()->getNode('global/crypt/key');
                $check_status_url= Mage::helper('paytm')->decrypt_e($transaction_status_url,$const);
                $responseParamList = Mage::helper('paytm')->callNewAPI($check_status_url, $requestParamList);
                $authStatus = true;
                if($authStatus == false) {
                    $this->_processCancel($request); 
                } else {
                    if($responseParamList['STATUS']=='TXN_SUCCESS' && $responseParamList['TXNAMOUNT']==$_POST['TXNAMOUNT']) {
                        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING,true)->save();
                        $this->_processSale($request);
                        $order_mail = new Mage_Sales_Model_Order();
                        $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
                        $order_mail->loadByIncrementId($incrementId);
                        $i = Mage::getVersion();
                        $updatedVersion=false;
                        if(strpos($i,"1.9")===0){
                            $updatedVersion=true;
                        }
                        if(!$updatedVersion){   // above 1.9.0 version not support sendNewOrderEmail() fumction 
                            try{
                                $order_mail->sendNewOrderEmail(); 
                            } catch (Exception $ex) {  }
                        }
                    } else {
                        $this->_processFail($request);
                    }
                } 
            } else{
                $this->_processCancel($request);
            }
                
            
        } catch (Mage_Core_Exception $e) {
            $this->getResponse()->setBody(
                $this->getLayout()
                    ->createBlock($this->_failureBlockType)
                    ->setOrder($this->_order)
                    ->toHtml()
            );
            
            $this->_processFail($request);
            
        }
    }

    //runs on success of payment
    public function successAction()
    {
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
    
    public function failAction()
    {
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
    public function cancelAction()
    {
        // set quote to active
        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        if (!$order->getId()) {
        Mage::throwException('No order for processing found');
        }
        $order->setState(
        Mage_Sales_Model_Order::STATE_CANCELED,true)->save();
        
        if ($quoteId = $session->getpaytmQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        Mage::log("cancel");
        $session->addError(Mage::helper('paytm')->__('The order has been canceled.'));
        $this->_redirect('checkout/cart');
        
    }

    // Checking POST variables.
    protected function _checkReturnedPost()
    {
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

            // load order for further validation
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($request['ORDERID']);
        if (!$this->_order->getId())
            Mage::throwException('Order not found');


        return $request;
    }

    //if success process sale
    protected function _processSale($request)
    {
        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($request['ORDERID']);
        //save transaction information
        $invoice = $order->prepareInvoice();
        $invoice->register()->capture();
        Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
        
        
        $order->addStatusToHistory(Mage::getStoreConfig('payment/paytm_cc/order_status'),Mage::helper('paytm')->__('Payment successful through Paytm PG'));
        $order->save();
        
        
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_successBlockType)
                ->setOrder($this->_order)
                ->toHtml()
        );
    }

    //cancel order if failure
    protected function _processFail($request)
    {
        // cancel order
        $request = $this->getRequest()->getPost();
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($request['ORDERID']);
        if ($this->_order->canCancel()) {
            $this->_order->cancel();
            $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATUS_FRAUD, Mage::helper('paytm')->__('Payment failed'));
            $this->_order->save();
        }
        $session = $this->_getCheckout();
        $session->addError(Mage::helper('paytm')->__('It seems some issue in server to server communication. Kindly connect with administrator.'));
        $this->_redirect('checkout/cart');

    }
    
    //cancel order if failure
    protected function _processCancel($request)
    {
        // cancel order
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($request['ORDERID']);
        if ($this->_order->canCancel()) {
            $this->_order->cancel();
            $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, Mage::helper('paytm')->__('Payment was canceled'));
            $this->_order->save();
        }

        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock($this->_cancelBlockType)
                ->setOrder($this->_order)
                ->toHtml()
        );
    }

    protected function _getPendingPaymentStatus()
    {
        return Mage::helper('paytm')->getPendingPaymentStatus();
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
                $transaction_status_url = Mage::getStoreConfig('payment/paytm_cc/transaction_status_url');
                $const = (string)Mage::getConfig()->getNode('global/crypt/key');
                $check_status_url= Mage::helper('paytm')->decrypt_e($transaction_status_url,$const);
                $testing_urls = array(
                    Mage::getBaseUrl(),
                    "www.google.co.in",
                    $check_status_url
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

    public function ajaxAction() {

        $json = array();

        if(isset($_POST["promo_code"]) && trim($_POST["promo_code"]) != "") {

            // if promo code local validation enabled
            if(Mage::getStoreConfig('payment/paytm_cc/promo_code_local_validation')=='1') {

                $promocode=Mage::getStoreConfig('payment/paytm_cc/promo_codes');
                $promo_codes = explode(",", $promocode);

                $promo_code_found = false;

                foreach($promo_codes as $key=>$val){
                    // entered promo code should matched
                    if(trim($val) == trim($_POST["promo_code"])) {
                        $promo_code_found = true;
                        break;
                    }
                }

            } else {
                $promo_code_found = true;
            }

            if($promo_code_found){
                $json = array("success" => true, "message" => "Applied Successfully");
                Mage::getSingleton('core/session')->setPROMO_CAMP_ID($_POST["promo_code"]);
            } else {
                $json = array("success" => false, "message" => "Incorrect Promo Code");
            }
        } else {

            // unset promo code from session if ajax request made to remove
            if(Mage::getSingleton('core/session')->unsPROMO_CAMP_ID($_POST["promo_code"])){
                Mage::getSingleton('core/session')->unsPROMO_CAMP_ID($_POST["promo_code"]);
                $json = array("success" => true, "message" => "Removed Successfully");
            }
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($json));
    }
  
}
