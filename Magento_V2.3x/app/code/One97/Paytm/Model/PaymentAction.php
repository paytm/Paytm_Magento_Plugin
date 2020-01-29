<?php
    namespace One97\Paytm\Model;

    class PaymentAction implements \Magento\Framework\Option\ArrayInterface {
        public function toOptionArray() {
            return [['value' => 'authorize_capture', 'label' => __('Yes')], ['value' => 'capture', 'label' => __('No')]];
        }

        public function toArray() {
            return [0 => __('No'), 1 => __('Yes')];
        }
    }
?>