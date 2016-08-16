<?php

namespace One97\Paytm\Controller\Standard;

class Cancel extends \One97\Paytm\Controller\Paytm
{

    public function execute()
    {
        $this->_cancelPayment();
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect(
            $this->getPaytmHelper()->getUrl('checkout')
        );
    }

}
