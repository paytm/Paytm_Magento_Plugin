<?php
    namespace One97\Paytm\Controller\Standard;
    use Magento\Framework\App\CsrfAwareActionInterface;
    use Magento\Framework\App\Request\InvalidRequestException;
    use Magento\Framework\App\RequestInterface;
  

    class Curlconfig extends \One97\Paytm\Controller\Paytm  implements CsrfAwareActionInterface{


        public function createCsrfValidationException(
            RequestInterface $request
        ): ?InvalidRequestException {
            return null;
        }

        public function validateForCsrf(RequestInterface $request): ?bool {
            return true;
        }
        
        /* 
            1.) this function return last update time and magento version in admin config page.
            2.) this function is check external curl is enable for this domain.
        */
        public function execute() {
            $reqData=$this->getRequest()->getParams();
            $resultJson = $this->resultJsonFactory->create();
            if(isset($reqData['getlastUpdate'])){
                $getLastUpdate=$this->getPaytmModel()->getLastUpdate();
                $getLastUpdateArr=explode('|',$getLastUpdate);
                $callBackUrl = $this->_url->getUrl('paytm/Standard/Response');
                return $resultJson->setData(['version' => $getLastUpdateArr[0], 'lastupdate' => $getLastUpdateArr[1], 'phpCurlVersion' => $this->getPaytmModel()->getcURLversion(), 'paytmPluginVersion' => $this->getPaytmModel()->getpluginversion(),'callBackUrl' => $callBackUrl]);
            }
            else if(isset($reqData['setPaymentNotificationUrl'])){
                if($reqData['environment'] == 0){
                    $url = "https://boss-stage.paytm.in/";
                    $clientId = "54b88dbc-fdd0-4f6f-af09-4ee76e90135a";
            $key = base64_decode("DSqy6pGOhBR9CUhUFQygzlkG1+x66C6zV+M8bnGiwpEH+MvNsWmaJLirjxMVpRH+9c9XjZxse+wSYuNYmkG4uA==");
                }else{
                    $url = "https://boss-ext.paytm.in/";
                    $clientId = "f7484d06-f307-4e10-b661-0191f5efe031";
                    $key = base64_decode("jF5tDDCJ8/bRot8X5DAGVHiC+KwW9SQuckYya12NZ2/EWVBhU7Cj45A4lOSvo797uJ4M3LB5mTjiC0nhDYKZGg==");
                }
                $environment = $reqData['environment'];
                $jwtToken = $this->getPaytmModel()->createJWTToken($key,$clientId,$environment);
                $mid = $reqData['mid'];
                if($reqData['is_webhook']==1){
                    $webhookUrl = $reqData['webhookUrl'];
                }else{
                    $webhookUrl = "https://www.dummyUrl.com"; //set this when unchecked
                }

                $curl = curl_init();

                curl_setopt_array($curl, array(
                CURLOPT_URL => $url.'api/v1/merchant/putMerchantInfo', 
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS =>'{
                        "mid": "'.$mid.'",
                        "queryParam": "notificationUrls",
                        "paymentNotificationUrl": "'.$webhookUrl.'"
                    }',
                CURLOPT_HTTPHEADER => array(
                        'x-client-token: '.$jwtToken.'',
                        'Content-Type: application/json',
                        'x-client-id: '.$clientId.''
                    ),
                ));

                $response = curl_exec($curl);
                $res = json_decode($response);
                return $resultJson->setData(['response'=>$res]);
            }
            else{
                $responseTableBody='';
                $response=false;
                $transactionURL=$this->getPaytmModel()->getNewStatusQueryUrl();
                $ch = curl_init($transactionURL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $res = curl_exec($ch);
                $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $returnResponse="Curl is not enable for external IP. Please enable this by your domain provider.";
                switch ($response) {
                    case '403':
                        if (strpos($res, "Access Denied") == false) { 
                        } else {
                            $returnResponse="Your domain is not whitelisted on Paytm side. Please request to Paytm for whitelisting your public IP.";
                        } 
                        break;
                    case '200':
                        if(json_decode($res)==null){
                        }else{
                            $returnResponse="All is done.";
                        }
                        break;
                    
                    default:
                        # code...
                        break;
                }
                // $this->messageManager->addWarningMessage( __($returnResponse) );
                return $resultJson->setData(['response' => $response,'responseTableBody' => $returnResponse]);
            }
        }
    }
?>