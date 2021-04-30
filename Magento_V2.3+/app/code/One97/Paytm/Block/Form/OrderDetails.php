<?php
	namespace One97\Paytm\Block\Form;

	class OrderDetails extends \Magento\Sales\Block\Order\Totals {
	    public $_template = 'One97_Paytm::success.phtml';
	    public $objectPaytmCont='';
	    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
    }


    public function getViewUrl($orderId)
{
    return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
}

    public function getOrder()
    {
        return  $this->_order = $this->_orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId());
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }
	}
?>