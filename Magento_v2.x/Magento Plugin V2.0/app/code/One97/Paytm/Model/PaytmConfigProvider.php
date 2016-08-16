<?php

namespace One97\Paytm\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\UrlInterface as UrlInterface;

class PaytmConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = "paytm";

    protected $method;
    
    protected $urlBuilder;

    public function __construct(PaymentHelper $paymentHelper, UrlInterface $urlBuilder) {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'paytm' => [
                    'redirectUrl' => $this->urlBuilder->getUrl('paytm/Standard/Redirect', ['_secure' => true])
                ]
            ]
        ] : [];
    }

    protected function getRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('paypal/ipn/');
    }
    
    protected function getFormData()
    {
        return $this->method->getRedirectUrl();
    }
}
