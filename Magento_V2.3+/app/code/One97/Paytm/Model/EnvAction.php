<?php
    namespace One97\Paytm\Model;

    class EnvAction implements \Magento\Framework\Option\ArrayInterface {
        public function toOptionArray() {
            return [['value' => '0', 'label' => __('Staging')], ['value' => '1', 'label' => __('Production')]];
        }

        public function toArray() {
            return [0 => __('No'), 1 => __('Yes')];
        }
    }
?>