<?php
    namespace One97\Paytm\Model;

    class WebhookAction implements \Magento\Framework\Option\ArrayInterface {
        public function toOptionArray() {
            return [['value' => '0', 'label' => __('No')], ['value' => '1', 'label' => __('Yes')]];
        }

        public function toArray() {
            return [0 => __('No'), 1 => __('Yes')];
        }
    }
?>