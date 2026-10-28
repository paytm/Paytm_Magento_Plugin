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
    use Magento\Framework\Controller\ResultFactory;


    class Redirect extends \PPSL\Paytm\Controller\Paytm  implements CsrfAwareActionInterface{

        /**
         * @var \Magento\Quote\Model\QuoteFactory
         */
        private $quoteFactory;

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
            \Magento\Quote\Model\QuoteFactory $quoteFactory
        ) {
            $this->quoteFactory = $quoteFactory;
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

        $order = $this->_checkoutSession->getLastRealOrder();
        $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
        if ($quote->getId()) {
        $quote->setIsActive(1)->setReservedOrderId(null)->save();
        $this->_checkoutSession->replaceQuote($quote);
        }



        }






    }
?>