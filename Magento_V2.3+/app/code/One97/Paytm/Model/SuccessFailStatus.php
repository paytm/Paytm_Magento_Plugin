<?php
    namespace One97\Paytm\Model;
    use \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

    class SuccessFailStatus implements \Magento\Framework\Option\ArrayInterface {
        protected $statusCollectionFactory;

        public function __construct(
            \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory
        ) {
            $this->statusCollectionFactory = $statusCollectionFactory;      
        }

        public function toOptionArray() {
            $options = $this->statusCollectionFactory->create()->toOptionArray();        
            return $options;
        }
    }
?>