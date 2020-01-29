<?php
    namespace One97\Paytm\Controller\Standard;

    class Redirect extends \One97\Paytm\Controller\Paytm {
        
        /* this funtion redirect to Paytm with proper form post */
        public function execute() {
            $promo='';
            if(isset($_GET['promo'])){
                if(trim($_GET['promo'])!=''){
                    $promo=$_GET['promo'];
                }
            }
            $order = $this->getOrder();
            if ($order->getBillingAddress()) {
                $order->setState("pending_payment")->setStatus("pending_payment");
                $order->addStatusToHistory($order->getStatus(), "Customer was redirected to paytm.");
                $order->save();
                if($promo!=''){
                    $order->paytmPromoCode=$promo;
                }
                $data['inputForm']=$this->_paytmModel->buildPaytmRequest($order);
                $data['actionURL']=$this->_paytmModel->getRedirectUrl();
                $resultPage = $this->resultPageFactory->create();
                $resultPage->getLayout()->initMessages();
                $resultPage->getLayout()->getBlock('paytm_standard_redirect')->setName($data);
                return $resultPage;
            } else {
                $this->_cancelPayment();
                $this->_paytmSession->restoreQuote();
                $this->getResponse()->setRedirect(
                    $this->getPaytmHelper()->getUrl('checkout')
                );
            }
        }
    }
?>