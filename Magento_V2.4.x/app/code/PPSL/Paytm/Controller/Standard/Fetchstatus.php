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
    use Magento\Framework\App\ResourceConnection;
    use Magento\Framework\Stdlib\DateTime\DateTime;

    class Fetchstatus extends \PPSL\Paytm\Controller\Paytm  implements CsrfAwareActionInterface{

        /**
         * @var ResourceConnection
         */
        private $resourceConnection;

        /**
         * @var DateTime
         */
        private $dateTime;

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
            ResourceConnection $resourceConnection,
            DateTime $dateTime
        ) {
            $this->resourceConnection = $resourceConnection;
            $this->dateTime = $dateTime;
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

        /* 
            this function return current transaction status in admin order view page by curl(if status is pending). 
            update the response in DB (in paytm_order_data)
        */
        public function execute() {
            $reqData=$this->getRequest()->getParams();
            $responseTableBody='';
            $response=false;
            // echo "test";
            if(isset($reqData['fetchId'])){
                $fetchId = (int) $reqData['fetchId'];
                if($fetchId > 0 && $this->getPaytmHelper()::SAVE_PAYTM_RESPONSE){
                    $connection = $this->resourceConnection->getConnection();
                    $tableName = $this->resourceConnection->getTableName('paytm_order_data');
                    $select = $connection->select()
                        ->from($tableName)
                        ->where('id = ?', $fetchId);
                    $result = $connection->fetchAll($select);
                    if (empty($result)) {
                        $resultJson = $this->resultJsonFactory->create();
                        return $resultJson->setData(['response' => $response,'responseTableBody' => $responseTableBody]);
                    }
                    $requestParamList = array("MID" => $this->getPaytmModel()->getMID() , "ORDERID" => $result[0]['paytm_order_id']);
                    $StatusCheckSum  =  $this->getPaytmModel()->generateStatusChecksum($requestParamList);
                    $requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
                    $check_status_url = $this->getPaytmModel()->getNewStatusQueryUrl();                 
                    $responseParamList = $this->getPaytmHelper()->executecUrl($check_status_url, $requestParamList);
                    if(!empty($responseParamList)){
                        $date = $this->dateTime->gmtDate();

                        $paytmRes=$this->jsonHelper->jsonEncode($responseParamList);
                        $connection->update(
                            $tableName,
                            [
                                'transaction_id' => (string) ($responseParamList['TXNID'] ?? ''),
                                'paytm_response' => $paytmRes,
                                'status' => 1,
                                'date_modified' => $date
                            ],
                            ['id = ?' => $fetchId]
                        );
                        ksort($responseParamList);
                        foreach ($responseParamList as $k => $val) {
                            switch ($k) {
                                case 'CHECKSUMHASH':
                                    break;
                                case 'STATUS':
                                    $responseTableBody.=' <tr style="font-weight:900;"> <th>'.$k.'</th> <td>'.$val.'</td> </tr> ';
                                    break;
                                
                                default:
                                    $responseTableBody.=' <tr> <th>'.$k.'</th> <td>'.$val.'</td> </tr> ';
                                    break;
                            }
                        }
                        $order = $this->getOrderById($result[0]['order_id']);
                        if($responseParamList['STATUS']=='TXN_SUCCESS'){
                            $autoInvoice =  $this->getPaytmModel()->autoInvoiceGen();
                            if($autoInvoice=='authorize_capture'){
                                $payment = $order->getPayment();
                                $payment->setTransactionId($responseParamList['TXNID'])       
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
                            $comment =  "Transaction has marked as successfull by checkStatus API.";
                            $order->setState("complete");
                            $order->setStatus($this->_paytmModel->getSuccessOrderStatus());
                            $order->setExtOrderId($result[0]['paytm_order_id']);
                            $order->addStatusToHistory($order->getStatus(), $comment);
                            $order->save();
                            $response=true;
                        }else if($responseParamList['STATUS']=="TXN_FAILURE"){
                            $comment =  $responseParamList['RESPMSG'];
                            $order->setState("canceled")->setStatus($this->_paytmModel->getFailOrderStatus());
                            $order->addStatusToHistory($order->getStatus(), $comment);
                            $order->save();
                        }
                    }
                }
            }

            $resultJson = $this->resultJsonFactory->create();
            return $resultJson->setData(['response' => $response,'responseTableBody' => $responseTableBody]);
        }
    }
?>