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

    class WebhookAction implements \Magento\Framework\Data\OptionSourceInterface {
        public function toOptionArray() {
            return [['value' => '0', 'label' => __('No')], ['value' => '1', 'label' => __('Yes')]];
        }

        public function toArray() {
            return [0 => __('No'), 1 => __('Yes')];
        }
    }
?>