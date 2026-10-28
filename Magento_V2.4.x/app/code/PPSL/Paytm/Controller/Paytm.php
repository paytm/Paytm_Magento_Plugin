<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
    namespace PPSL\Paytm\Controller;
    use Magento\Framework\App\CsrfAwareActionInterface;
    use Magento\Framework\App\Request\InvalidRequestException;
    use Magento\Framework\App\RequestInterface;

    abstract class Paytm implements \Magento\Framework\App\ActionInterface, CsrfAwareActionInterface {
        protected $_checkoutSession;
        protected $_orderFactory;
        protected $_customerSession;
        protected $_logger;
        protected $_quote = false;
        protected $_paytmModel;
        protected $_paytmHelper;
    	protected $_orderHistoryFactory;
        protected $resultJsonFactory;
        protected $jsonHelper;
        protected $resultPageFactory;
        protected $messageManager;
        protected $_request;
        protected $_response;
        protected $resultFactory;
        protected $_url;
        
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
            \Magento\Framework\Message\ManagerInterface $messageManager
        ) {
            $this->_customerSession = $customerSession;
            $this->_checkoutSession = $checkoutSession;
            $this->_orderFactory = $orderFactory;
            $this->_logger = $logger;
    		$this->_orderHistoryFactory = $orderHistoryFactory;
            $this->_paytmModel = $paytmModel;
            $this->_paytmHelper = $paytmHelper;
            $this->resultJsonFactory = $resultJsonFactory;	
            $this->jsonHelper = $jsonHelper;
            $this->resultPageFactory = $resultPageFactory;	
            $this->messageManager = $messageManager;
            $this->_request = $context->getRequest();
            $this->_response = $context->getResponse();
            $this->resultFactory = $context->getResultFactory();
            $this->_url = $context->getUrl();
        }

        public function getRequest()
        {
            return $this->_request;
        }

        public function getResponse()
        {
            return $this->_response;
        }

        public function createCsrfValidationException(
            RequestInterface $request
        ): ?InvalidRequestException {
            return null;
        }

        public function validateForCsrf(RequestInterface $request): ?bool {
            return true;
        }

        protected function _cancelPayment($errorMsg = '') {
            $gotoSection = false;
            $this->_paytmHelper->cancelCurrentOrder($errorMsg);
            if ($this->_checkoutSession->restoreQuote()) {
                $gotoSection = 'paymentMethod';
            }
            return $gotoSection;
        }

        protected function getOrderById($order_id) {
            return $this->_orderFactory->create()->loadByIncrementId($order_id);
        }

        protected function getOrder() {
            return $this->_orderFactory->create()->loadByIncrementId(
                $this->_checkoutSession->getLastRealOrderId()
            );
        }

    	protected function addOrderHistory($order,$comment){
    		$history = $this->_orderHistoryFactory->create()
    			->setComment($comment)
                ->setEntityName('order')
                ->setOrder($order);
    			$history->save();
    		return true;
    	}
    	
        protected function getQuote() {
            if (!$this->_quote) {
                $this->_quote = $this->_getCheckoutSession()->getQuote();
            }
            return $this->_quote;
        }

        protected function getCheckoutSession() {
            return $this->_checkoutSession;
        }

        protected function getCustomerSession() {
            return $this->_customerSession;
        }

        protected function getPaytmModel() {
            return $this->_paytmModel;
        }

        protected function getPaytmHelper() {
            return $this->_paytmHelper;
        }
    }
?>