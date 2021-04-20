<?php

  
    namespace One97\Paytm\Controller\Standard;
    
    
    use Magento\Sales\Model\Order;
    
    class Redirect extends \Magento\Framework\App\Action\Action
    {
        protected $_pageFactory;
        protected $_resultJsonFactory;
        protected $_checkoutSession;
        protected $orderRepository;
        protected $customerSession;
        protected $_paytmModel;
        protected $_paytmHelper;
    
        public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
            \Magento\Framework\View\Result\PageFactory $pageFactory,
            \Magento\Checkout\Model\Session $checkoutSession,
            \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
            \Magento\Customer\Model\Session $customerSession,
            \One97\Paytm\Model\Paytm $paytmModel,
            \One97\Paytm\Helper\Data $paytmHelper
        )
        {
            $this->_checkoutSession = $checkoutSession;
            $this->_resultJsonFactory = $resultJsonFactory;
            $this->_pageFactory = $pageFactory;
            $this->orderRepository = $orderRepository;
            $this->customerSession = $customerSession;
            $this->_paytmModel = $paytmModel;
            $this->_paytmHelper = $paytmHelper;
            return parent::__construct($context);
        }
    
        public function execute()
        {
    
    
    
            $customerId = $this->customerSession->getCustomer()->getId();
    
            $result = $this->_resultJsonFactory->create();
    
            $order = $this->_checkoutSession->getLastRealOrder();
            //$orderId=$order->getEntityId();
            $order->getIncrementId();
    
            $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
            // $connection= $this->_resources->getConnection();
            // $themeTable = $this->_resources->getTableName('z_payu_tx');
            // $sql = "INSERT INTO ". $themeTable . 
            //         " (orderid, date, state_pol,customer_number) 
            //         VALUES 
            //         ('".$order->getIncrementId()."', '".date("Y-m-d H:i:s")."','NEW','".$customerId."')";
    
            try{
    
                //$connection->query($sql);
    
                $orderId = $order->getIncrementId();
    
            }catch(\Exception $e){
    
                $orderId = $order->getIncrementId();
            }
           
            $resultdata = $this->_paytmModel->buildPaytmRequest($order);
            //echo json_encode(array('response'=>$resultdata));
            return $result->setData($resultdata);
    
    
        }
    
    }
 
?>


  
