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
		$resMessage = $_POST['RESPMSG'];
        $orderId = $this->getRequest()->getParam('ORDERID');
        $order = $this->getOrderById($orderId);
        $orderTotal = round($order->getGrandTotal(), 2);
        $orderStatus = $this->getRequest()->getParam('STATUS');
		$resCode = $this->getRequest()->getParam('RESPCODE');
        $orderTxnAmount = $this->getRequest()->getParam('TXNAMOUNT');
        //print_r($request);
        if($this->getPaytmModel()->validateResponse($request, $orderId))
        {
			if($orderStatus == "TXN_SUCCESS" && $orderTotal == $orderTxnAmount){
				$successFlag = true;
				$comment .=  "Success ";
				$order->setStatus($order::STATE_PROCESSING);
				$order->setExtOrderId($orderId);
				$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/success');
			}else{
				if($resCode == "141" || $resCode == "8102" || $resCode == "8103" || $resCode == "14112"){
					$errorMsg = 'Paytm Transaction Failed ! Transaction was cancelled.';
					$comment .=  "Payment cancelled by user";
					$order->setStatus($order::STATE_CANCELED);
					$this->_cancelPayment("Payment cancelled by user");
					//$order->save();
					$returnUrl = $this->getPaytmHelper()->getUrl('checkout/cart');
				}else{
					$errorMsg = 'Paytm Transaction Failed ! '.$resMessage;
					$comment .=  "Failed";
					
					$order->setStatus($order::STATE_PAYMENT_REVIEW);
					$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/failure');
				}
			}            
        }
        else
        {
			$errorMsg = 'Paytm Transaction Failed ! Fraud has been detected';
			$comment .=  "Fraud Detucted";
            $order->setStatus($order::STATUS_FRAUD);
            $returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/failure');
        }
		$this->addOrderHistory($order,$comment);
        $order->save();
		if($successFlag){
			$this->messageManager->addSuccess( __('Paytm transaction has been successful.') );
		}else{
			$this->messageManager->addError( __($errorMsg) );
		}
        $this->getResponse()->setRedirect($returnUrl);
    }

}
