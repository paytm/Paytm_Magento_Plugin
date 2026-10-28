<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
	namespace PPSL\Paytm\Block\Form;

	class OrderDetails extends \Magento\Framework\View\Element\Template {
	    public $_template = 'PPSL_Paytm::success.phtml';
	    public $objectPaytmCont='';
	    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
