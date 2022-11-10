<?php
    namespace One97\Paytm\Model;

    class WebsitenameAction implements \Magento\Framework\Option\ArrayInterface {
        public function toOptionArray() {
            return [['value' => 'WEBSTAGING', 'label' => __('WEBSTAGING')], ['value' => 'DEFAULT', 'label' => __('DEFAULT')]];
        }

        public function toArray() {
            return [0 => __('No'), 1 => __('Yes')];
        }
    }
?>