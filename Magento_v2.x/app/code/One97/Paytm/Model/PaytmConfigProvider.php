<?php
    namespace One97\Paytm\Model;
    use Magento\Checkout\Model\ConfigProviderInterface;
    use Magento\Payment\Helper\Data as PaymentHelper;
    use Magento\Framework\UrlInterface as UrlInterface;
    use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

    class PaytmConfigProvider implements ConfigProviderInterface {
        protected $methodCode = "paytm";
        protected $method;
        protected $urlBuilder;

        public function __construct(PaymentHelper $paymentHelper, UrlInterface $urlBuilder, ScopeConfig $scopeConfig ) {
            $this->method = $paymentHelper->getMethodInstance($this->methodCode);
            $this->urlBuilder = $urlBuilder;
            $this->scopeConfig = $scopeConfig;
        }

        public function getConfig() {
            return $this->method->isAvailable() ? [
                'payment' => [
                    'paytm' => [
                        'mid' => $this->method->getMID(),
                        'checkout_url' => $this->method->getcheckoutjsurl(),
                       // 'order' => $this->method->getOrder(),
                        'redirecturl' => $this->urlBuilder->getUrl('paytm/Standard/Redirect', ['_secure' => true])
                    ]
                ]
            ] : [];
        }
    }
?>
