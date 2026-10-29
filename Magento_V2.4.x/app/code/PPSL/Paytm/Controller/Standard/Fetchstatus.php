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
    use PPSL\Paytm\Model\Service\FetchPaytmOrderStatus;

    class Fetchstatus extends \PPSL\Paytm\Controller\Paytm  implements CsrfAwareActionInterface{

        /**
         * @var FetchPaytmOrderStatus
         */
        private $fetchPaytmOrderStatus;

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
            FetchPaytmOrderStatus $fetchPaytmOrderStatus
        ) {
            $this->fetchPaytmOrderStatus = $fetchPaytmOrderStatus;
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
            $fetchId = (int) $this->getRequest()->getParam('fetchId');
            $data = $this->fetchPaytmOrderStatus->execute($fetchId);
            return $this->resultJsonFactory->create()->setData($data);
        }
    }
?>