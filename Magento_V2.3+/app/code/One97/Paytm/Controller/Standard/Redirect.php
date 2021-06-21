<?php
    namespace One97\Paytm\Controller\Standard;
    use Magento\Framework\App\CsrfAwareActionInterface;
    use Magento\Framework\App\Request\InvalidRequestException;
    use Magento\Framework\App\RequestInterface;
    use Magento\Framework\Controller\ResultFactory;


    class Redirect extends \One97\Paytm\Controller\Paytm  implements CsrfAwareActionInterface{

        public function createCsrfValidationException(
            RequestInterface $request
        ): ?InvalidRequestException {
            return null;
        }

        public function validateForCsrf(RequestInterface $request): ?bool {
            return true;
        }
        
        /* this funtion redirect to Paytm with proper form post */
        public function execute() {
        


            $order = $this->getOrder();
            if ($order->getBillingAddress()) {
                $this->restoreOrder();
                $order->setState("pending_payment")->setStatus("pending_payment");
                $order->addStatusToHistory($order->getStatus(), "Customer was redirected to paytm.");
                $order->save();
                $dataRaw=$this->_paytmModel->buildPaytmRequest($order);
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($dataRaw);
                return $resultJson;
            } else {
                $this->_cancelPayment();
                $this->restoreOrder();
                $this->getResponse()->setRedirect(
                    $this->getPaytmHelper()->getUrl('checkout')
                );
            }
        }



    function restoreOrder(){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
        $_quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
    
        $order = $_checkoutSession->getLastRealOrder();
        $quote = $_quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
        if ($quote->getId()) {
        $quote->setIsActive(1)->setReservedOrderId(null)->save();
        $_checkoutSession->replaceQuote($quote);
        }



        }






    }
?>