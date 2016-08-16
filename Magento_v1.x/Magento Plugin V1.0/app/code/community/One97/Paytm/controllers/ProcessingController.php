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
			if(Mage::getStoreConfig('payment/paytm_cc/mode')==1)
				$_testurl = Mage::helper('paytm/Data2')->STATUS_QUERY_URL_PROD;
			else
				$_testurl = Mage::helper('paytm/Data2')->STATUS_QUERY_URL_TEST;

			if($txnstatus && $isValidChecksum){
			
				
				$authStatus = true;
				
				if($authStatus == false)					
					{
						$this->_processCancel($request);
						
					}
				else
					{
						$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING,true)->save();
						$this->_processSale($request);
						$order_mail = new Mage_Sales_Model_Order();
						$incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
						$order_mail->loadByIncrementId($incrementId);
						try{
							 $order_mail->sendNewOrderEmail();
						   }    
                        catch (Exception $ex) {  }
					}
            }
			else
				$this->_processCancel($request);
				
			
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
            $session->setLastSuccessQuoteId($session->getpaytmSuccessQuoteId(true));
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

    
  
}