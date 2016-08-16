<?php

namespace One97\Paytm\Controller\Standard;

class Redirect extends \One97\Paytm\Controller\Paytm
{
    public function execute()
    {
        $order = $this->getOrder();
        if ($order->getBillingAddress())
        {
            $this->getResponse()->setRedirect(
                $this->getPaytmModel()->buildPaytmRequest($order)
            );
        }
        else
        {
            $this->_cancelPayment();
            $this->_paytmSession->restoreQuote();
            $this->getResponse()->setRedirect(
                $this->getPaytmHelper()->getUrl('checkout')
            );
        }
    }
}