<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
    namespace PPSL\Paytm\Model;
    use Magento\Checkout\Model\ConfigProviderInterface;
    use Magento\Payment\Helper\Data as PaymentHelper;
    use Magento\Framework\UrlInterface as UrlInterface;

    class PaytmConfigProvider implements ConfigProviderInterface {
        protected $methodCode = "paytm";
        protected $method;
        protected $urlBuilder;

        public function __construct(PaymentHelper $paymentHelper, UrlInterface $urlBuilder) {
            $this->method = $paymentHelper->getMethodInstance($this->methodCode);
            $this->urlBuilder = $urlBuilder;
        }

        public function getConfig() {
            return $this->method->isAvailable() ? [
                'payment' => [
                    'paytm' => [
                        'mid' => $this->method->getMID(),
                        'checkout_url' => $this->method->getcheckoutjsurl(),
                       // 'order' => $this->method->getOrder(),
                        'redirecturl' => $this->urlBuilder->getUrl('paytm/Standard/Redirect', ['_secure' => true]),
                        'get_logo' => $this->method->getLogo(),
                    ]
                ]
            ] : [];
        }
    }
?>