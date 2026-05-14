<?php
    namespace One97\Paytm\Controller\Standard;

    class Fetchstatus extends \One97\Paytm\Controller\Paytm {

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
                if(trim($reqData['fetchId'])!='' && $this->getPaytmHelper()::SAVE_PAYTM_RESPONSE){
                    $fetchId = filter_var($reqData['fetchId'], FILTER_VALIDATE_INT);
                    if ($fetchId === false || $fetchId < 1) {
                        $resultJson = $this->resultJsonFactory->create();
                        return $resultJson->setData(['response' => $response,'responseTableBody' => $responseTableBody]);
                    }
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                    $connection = $resource->getConnection();
                    $tableName = $resource->getTableName('paytm_order_data');
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
                        $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
                        $date = $objDate->gmtDate();

                        $paytmRes=$this->jsonHelper->jsonEncode($responseParamList);
                        $connection->update(
                            $tableName,
                            [
                                'transaction_id' => isset($responseParamList['TXNID']) ? (string)$responseParamList['TXNID'] : '',
                                'paytm_response' => $paytmRes,
                                'status' => 1,
                                'date_modified' => $date,
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
                                ->setAdditionalInformation(['One97','paytm'])       
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