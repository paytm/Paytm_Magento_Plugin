<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
    namespace PPSL\Paytm\Plugin\Sales\Order\Email\Container;

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