<?php
    namespace One97\Paytm\Plugin\Sales\Order\Email\Container;

    class OrderIdentityPlugin {
        protected $checkoutSession;

        public function __construct(
            \Magento\Checkout\Model\Session $checkoutSession
        ) {
            $this->checkoutSession = $checkoutSession;
        }

        public function aroundIsEnabled(\Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject, callable $proceed) {
            $returnValue = $proceed();
            $forceOrderMailSentOnSuccess = $this->checkoutSession->getForceOrderMailSentOnSuccess();
            if(isset($forceOrderMailSentOnSuccess) && $forceOrderMailSentOnSuccess) {
                if($returnValue)
                    $returnValue = false;
                else
                    $returnValue = true;
                $this->checkoutSession->unsForceOrderMailSentOnSuccess();
            }
            return $returnValue;
        }
    }
?>