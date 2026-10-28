<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
	namespace PPSL\Paytm\Controller\Standard;
	use Magento\Framework\App\CsrfAwareActionInterface;
	use Magento\Framework\App\Request\InvalidRequestException;
	use Magento\Framework\App\RequestInterface;
	use Magento\Quote\Api\CartRepositoryInterface;
	use Magento\Framework\Controller\ResultFactory;
	use Magento\Framework\App\ResourceConnection;

	class Response extends \PPSL\Paytm\Controller\Paytm  implements CsrfAwareActionInterface{

		/**
		 * @var CartRepositoryInterface
		 */
		private $cartRepository;

		/**
		 * @var ResourceConnection
		 */
		private $resourceConnection;

		public function __construct(
			\Magento\Framework\App\Action\Context $context,
			\Magento\Customer\Model\Session $customerSession,
			\Magento\Checkout\Model\Session $checkoutSession,
			\Magento\Sales\Model\OrderFactory $orderFactory,
			\Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
			\PPSL\Paytm\Model\Paytm $paytmModel,
			\PPSL\Paytm\Helper\Data $paytmHelper,
			\Psr\Log\LoggerInterface $logger,
			\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
			\Magento\Framework\Json\Helper\Data $jsonHelper,
			\Magento\Framework\View\Result\PageFactory $resultPageFactory,
			\Magento\Framework\Message\ManagerInterface $messageManager,
			CartRepositoryInterface $cartRepository,
			ResourceConnection $resourceConnection
		) {
			$this->cartRepository = $cartRepository;
			$this->resourceConnection = $resourceConnection;
			parent::__construct(
				$context,
				$customerSession,
				$checkoutSession,
				$orderFactory,
				$orderHistoryFactory,
				$paytmModel,
				$paytmHelper,
				$logger,
				$resultJsonFactory,
				$jsonHelper,
				$resultPageFactory,
				$messageManager
			);
		}

		public function createCsrfValidationException(
		    RequestInterface $request
		): ?InvalidRequestException {
		    return null;
		}

		public function validateForCsrf(RequestInterface $request): ?bool {
		    return true;
		}
		/* this funtion handle Paytm response */
	    public function execute() { 
	    	$comment = "";
	    	$postData = (array) $this->getRequest()->getPostValue();
			if ($postData !== []) {
				foreach ($postData as $key => $val) {
					if ($key != "CHECKSUMHASH") {
						$comment .= $key  . "=" . $val . ", \n <br />";
					}
				}
			}
			
			$errorMsg = '';
			$successFlag = false;
			$resMessage=$this->getRequest()->getParam('RESPMSG');
			$globalErrMass="Your payment has been failed!";
			if(trim((string) $resMessage)!=''){
				$globalErrMass.=" Reason: ".$resMessage;
			}
			$returnUrl = $this->getPaytmHelper()->getUrl('/');
		    $orderMID = $this->getRequest()->getParam('MID');        
	        $paytmOrderId = $this->getRequest()->getParam('ORDERID');
	        $magentoOrderIdArr=explode('_', $paytmOrderId ?? '');
	        $magentoOrderId=$magentoOrderIdArr[0];
	        $orderTXNID = $this->getRequest()->getParam('TXNID');
	        $paytmResponse=$this->getRequest()->getParams();
	        $paytmJsonResponse=json_encode($paytmResponse);
	        
	        if(trim((string) $paytmOrderId)!='' && trim((string) $orderMID)!=''){
		        $order = $this->getOrderById($magentoOrderId);
		        $orderTotal = round((float) $order->getGrandTotal(), 2);
				$orderStatus = $this->getRequest()->getParam('STATUS'); 
				$resCode = $this->getRequest()->getParam('RESPCODE');
		        $orderTxnAmount = $this->getRequest()->getParam('TXNAMOUNT');
				$transactionResponse="";
				$paytmJsonResponseOnPending='';
			  	$success_order_status = $this->getPaytmModel()->getSuccessOrderStatus(); 
			  	$failure_order_status = $this->getPaytmModel()->getFailOrderStatus(); 
			 
			if($success_order_status != $order->getStatus() && $failure_order_status != $order->getStatus()){
			    if($this->getPaytmModel()->validateResponse($postData, $magentoOrderId)) { 
			        if($this->getPaytmHelper()::SAVE_PAYTM_RESPONSE){
				        $date = $this->getPaytmModel()->getDate(); 
				        $tableName = $this->getPaytmModel()->paytmTable();
				        $this->updateTable(
				            $tableName,
				            [
				                'transaction_id' => (string) $orderTXNID,
				                'paytm_response' => (string) $paytmJsonResponse,
				                'date_modified' => $date
				            ],
				            [
				                'order_id = ?' => (string) $magentoOrderId,
				                'paytm_order_id = ?' => (string) $paytmOrderId
				            ]
				        );
				    } 
					if($orderStatus == "TXN_SUCCESS"){ 
						$requestParamList = array("MID" => $orderMID , "ORDERID" => $paytmOrderId);
						$responseParamList=$this->checkStatusCall($requestParamList);  
						if(isset($responseParamList['STATUS'])){
							if($responseParamList['STATUS'] == "PENDING"){
								$transactionResponse="PENDING";
							}else{
								if($responseParamList['STATUS']=='TXN_SUCCESS' && $responseParamList['TXNAMOUNT']==$orderTxnAmount) {
									$transactionResponse="PROCESSING";
								} else{
									$transactionResponse="CANCELED";
								}
							}
						}else{
							$transactionResponse="FRAUD";
						}
					}else{
						if($orderStatus == "PENDING"){
							$requestParamList = array("MID" => $orderMID , "ORDERID" => $paytmOrderId);
							$responseParamList=$this->checkStatusCall($requestParamList);
							if(isset($responseParamList['STATUS'])){
								if($responseParamList['STATUS'] == "PENDING"){
									$transactionResponse="PENDING";
								}else{
									if($responseParamList['STATUS']=='TXN_SUCCESS' && $responseParamList['TXNAMOUNT']==$orderTxnAmount) {
										$transactionResponse="PROCESSING";
										$paytmJsonResponseOnPending=json_encode($responseParamList);
									} else{
										$transactionResponse="CANCELED";
									}
								}
							}else{
								$transactionResponse="FRAUD";
							}
						}else{
							$transactionResponse="CANCELED";
						}
					}            
		        } else { 
					$transactionResponse="FRAUD_CHECKSUM_MISMATCH";
		        } 
		        switch ($transactionResponse) {
		        	case 'FRAUD':
		        		$errorMsg = 'It seems some issue in server to server communication. Kindly connect with administrator.';
		        		$comment .=  "Fraud Detected";
		        		$order->setStatus($order::STATUS_FRAUD);
		        		$returnUrl = $this->getPaytmHelper()->getUrl('checkout/cart');
		        		break;
		        	case "FRAUD_CHECKSUM_MISMATCH":
    					$errorMsg = $globalErrMass." Reason: Checksum Mismatch.";
    					$comment .=  "Fraud Detected";
    		            $order->setState("canceled")->setStatus($order::STATUS_FRAUD);
    					$returnUrl = $this->getPaytmHelper()->getUrl('checkout/cart');
		        		break;
		        	case "CANCELED":
		        		if($orderStatus == "PENDING"){
		        			$errorMsg = 'Paytm Transaction Pending!';
		        			if(trim((string) $resMessage)==''){
		        				$errorMsg.=" Reason: ".$resMessage;
		        			}
		        			$comment .=  "Pending";
		        			$order->setState("pending_payment")->setStatus("pending_payment");
		        		}else{ 
							$order->cancel()->save();
		        			$errorMsg = $globalErrMass;
		        			$comment .=  $globalErrMass;
		        			$order->setState("canceled")->setStatus($this->_paytmModel->getFailOrderStatus());
		        		}
		        		$returnUrl = $this->getPaytmHelper()->getUrl('checkout/cart');
		        		break;
		        	case "PROCESSING":
		        		$tableName = $this->getPaytmModel()->paytmTable();
		        		$bind = $paytmJsonResponseOnPending === ''
		        		    ? ['status' => 1]
		        		    : ['status' => 1, 'paytm_response' => (string) $paytmJsonResponseOnPending];
		        		if($this->getPaytmHelper()::SAVE_PAYTM_RESPONSE){
		        			$this->updateTable(
		        			    $tableName,
		        			    $bind,
		        			    [
		        			        'order_id = ?' => (string) $magentoOrderId,
		        			        'paytm_order_id = ?' => (string) $paytmOrderId
		        			    ]
		        			);
		        		}
	        			$autoInvoice =  $this->getPaytmModel()->autoInvoiceGen();
		        		if($autoInvoice=='authorize_capture'){
		        			$payment = $order->getPayment();
		        			$payment->setTransactionId($orderTXNID)       
		        			->setPreparedMessage(__('Paytm transaction has been successful.'))
		        			->setShouldCloseParentTransaction(true)
		        			->setIsTransactionClosed(0)
		        			->setAdditionalInformation(['PPSL','paytm'])		
		        			->registerCaptureNotification(
		        				$responseParamList['TXNAMOUNT'],
		        				true 
		        			);
		        			$invoice = $payment->getCreatedInvoice();
		        		}
		        		$successFlag = true;
		        		$comment .=  "Success ";
		        		$order->setState('processing');
		        		$order->setStatus($this->_paytmModel->getSuccessOrderStatus());
		        		$order->setExtOrderId($paytmOrderId);
		        		$this->clearCart();  
		        		$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/success');
		        		break;
		        	case "PENDING":
		        		$errorMsg = 'Paytm Transaction Pending!';
		        		if(trim((string) $resMessage)==''){
		        			$errorMsg.=" Reason: ".$resMessage;
		        		}
		        		$comment .=  "Pending";
		        		$order->setState("pending_payment")->setStatus("pending_payment");
		        		$this->clearCart();
		        		$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/failure');
		        		break;

		        	default:
		        		$errorMsg = 'Paytm Transaction Pending!';
		        		if(trim((string) $resMessage)==''){
		        			$errorMsg.=" Reason: ".$resMessage;
		        		}
		        		$comment .=  "Pending" ;
		        		$order->setState("pending_payment")->setStatus("pending_payment");
		        		$returnUrl = $this->getPaytmHelper()->getUrl('checkout/cart');
		        		break;
		        }
				$order->addStatusToHistory($order->getStatus(), $comment);
		        $order->save();
				if($successFlag){
					$this->messageManager->addSuccess( __('Paytm transaction has been successful.') );
				}else{
					$this->messageManager->addError( __($errorMsg) );
				}
				if ($this->getRequest()->getParam('webhook') === 'yes') {
					return $this->createWebhookAckResponse();
				}
				$this->getResponse()->setRedirect($returnUrl);
				
	        }else{ 
				if ($this->getRequest()->getParam('webhook') === 'yes') {
					return $this->createWebhookAckResponse();
				}
				if($success_order_status == $order->getStatus()){
					 
					$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/success');
					 
				}elseif($failure_order_status == $order->getStatus()){
					 
					$returnUrl = $this->getPaytmHelper()->getUrl('checkout/onepage/failure');
					 
				}else{
					$returnUrl = $this->getPaytmHelper()->getUrl('checkout/cart');
				 
				}
				$this->getResponse()->setRedirect($returnUrl);
			  
		 }  
		}
	}

		/* Paytm transaction status S2S call */
		public function checkStatusCall($requestParamList){
			$response=array();
			if(!empty($requestParamList)){
				$StatusCheckSum  =  $this->getPaytmModel()->generateStatusChecksum($requestParamList);
				$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
				$check_status_url = $this->getPaytmModel()->getNewStatusQueryUrl(); 
				$responseParamList=array();
				$retry = 0;
				$maxretry = $this->getPaytmHelper()::MAX_RETRY_COUNT;
				do{
					$response = $this->getPaytmHelper()->executecUrl($check_status_url, $requestParamList);
					$retry++;
				} while(empty($response) && $retry < $maxretry);
			}
			return $response;
		}

		public function updateTable($tableName, array $bind, array $where){
			$updateDone=false;
			if($tableName !== '' && $bind !== [] && $where !== []){
				$connection = $this->resourceConnection->getConnection();
				$connection->update($tableName, $bind, $where);
				$updateDone=true;
			}
			return $updateDone;
		}

	public function getClearCartUrl(){
        $this->clearCart();
    }

	/**
	 * Empty HTTP 200 response for Paytm server-to-server webhook (no browser redirect).
	 *
	 * @return \Magento\Framework\Controller\Result\Raw
	 */
	private function createWebhookAckResponse()
	{
		/** @var \Magento\Framework\Controller\Result\Raw $result */
		$result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
		$result->setHttpResponseCode(200);
		$result->setContents('');
		return $result;
	}

	public function clearCart()
	{
		$checkoutSession = $this->_checkoutSession;
		$quote = $checkoutSession->getQuote();
		if (!$quote->getId()) {
			return;
		}
		$quote->setIsActive(false);
		$this->cartRepository->save($quote);
		$checkoutSession->clearQuote();
	}
	}
?>