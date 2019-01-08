<?php
namespace One97\Paytm\Controller\Standard;
class Response extends \One97\Paytm\Controller\Paytm
{
    public function execute()
    {
		$comment = "";
        $request = $_POST;
		if(!empty($_POST)){
			foreach($_POST as $key => $val){
				if($key != "CHECKSUMHASH"){
					$comment .= $key  . "=" . $val . ", \n <br />";
				}
			}
		}
		$errorMsg = '';
		$successFlag = false;
		$resMessage=$this->getRequest()->getParam('RESPMSG');
		$globalErrMass="Your payment has been failed!";
		if(trim($resMessage)!=''){
			$globalErrMass.=" Reason: ".$resMessage;
		}
		$returnUrl = $this->getPaytmHelper()->getUrl('/');
	    $orderMID = $this->getRequest()->getParam('MID');
        $orderId = $this->getRequest()->getParam('ORDERID');
        $orderTXNID = $this->getRequest()->getParam('TXNID');
        if(trim($orderId)!='' && trim($orderMID)!=''){
	        $order = $this->getOrderById($orderId);
	        $orderTotal = round($order->getGrandTotal(), 2);
	        
	        $orderStatus = $this->getRequest()->getParam('STATUS');
			$resCode = $this->getRequest()->getParam('RESPCODE');
	        $orderTxnAmount = $this->getRequest()->getParam('TXNAMOUNT');

		    if($this->getPaytmModel()->validateResponse($request, $orderId)) {
				if($orderStatus == "TXN_SUCCESS" && $orderTotal == $orderTxnAmount){				
					// Create an array having all required parameters for status query.				
					$requestParamList = array("MID" => $orderMID , "ORDERID" => $orderId);
					$StatusCheckSum  =  $this->getPaytmModel()->generateStatusChecksum($requestParamList);
					$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
					
					// Call the PG's getTxnStatus() function for verifying the transaction status.
					$check_status_url = $this->getPaytmModel()->getNewStatusQueryUrl(); 				
					$responseParamList = $this->getPaytmHelper()->callNewAPI($check_status_url, $requestParamList);
					if($responseParamList['STATUS'] == "PENDING"){
						$errorMsg = 'Paytm Transaction Pending!';
						if(trim($resMessage)==''){
							$errorMsg.=" Reason: ".$resMessage;
						}
						$comment .=  "Pending";
						$order->setState("pending_payment")->setStatus("pending_payment");
						// $this->_cancelPayment($errorMsg);
						$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/failure');
					}else{
						if($responseParamList['STATUS']=='TXN_SUCCESS' && $responseParamList['TXNAMOUNT']==$orderTxnAmount)
						{
							$autoInvoice =  $this->getPaytmModel()->autoInvoiceGen();
							if($autoInvoice=='authorize_capture'){
								$payment = $order->getPayment();
								$payment->setTransactionId($orderTXNID)       
								->setPreparedMessage(__('Paytm transaction has been successful.'))
								->setShouldCloseParentTransaction(true)
								->setIsTransactionClosed(0)
								->setAdditionalInformation(['One97','paytm'])		
								->registerCaptureNotification(
									$responseParamList['TXNAMOUNT'],
									true 
								);
								$invoice = $payment->getCreatedInvoice();
							}
							
							$successFlag = true;
							$comment .=  "Success ";
							$order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
							$order->setStatus($order::STATE_PROCESSING);
							$order->setExtOrderId($orderId);
							$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/success');
						} else{
							$errorMsg = 'It seems some issue in server to server communication. Kindly connect with administrator.';
							$comment .=  "Fraud Detucted";
							$order->setStatus($order::STATUS_FRAUD);
							// $this->_cancelPayment($errorMsg);
							$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/failure');
						}
					}
				}else{
					if($orderStatus == "PENDING"){
						$errorMsg = 'Paytm Transaction Pending!';
						if(trim($resMessage)==''){
							$errorMsg.=" Reason: ".$resMessage;
						}
						$comment .=  "Pending";
						// $this->_cancelPayment($errorMsg);
						$order->setState("pending_payment")->setStatus("pending_payment");
					}else{
						$errorMsg = $globalErrMass;
						$comment .=  $globalErrMass;
						$order->setStatus($order::STATE_CANCELED);
						// $this->_cancelPayment($globalErrMass);
					}
					$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/failure');
				}            
	        } else {
				$errorMsg = $globalErrMass." Reason: Checksum Mismatch.";
				$comment .=  "Fraud Detucted";
	            $order->setStatus($order::STATUS_FRAUD);
				// $this->_cancelPayment("Fraud Detucted");
	            $returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/failure');
	        }
			// $this->addOrderHistory($order,$comment);
	        $order->addStatusToHistory($order->getStatus(), $comment);
	        $order->save();
			if($successFlag){
				$this->messageManager->addSuccess( __('Paytm transaction has been successful.') );
			}else{
				$this->messageManager->addError( __($errorMsg) );
			}
        }
		$this->getResponse()->setRedirect($returnUrl);        
	}
}
