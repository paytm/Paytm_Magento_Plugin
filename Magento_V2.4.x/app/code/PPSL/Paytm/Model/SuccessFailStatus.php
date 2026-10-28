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
    use \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

    class SuccessFailStatus implements \Magento\Framework\Data\OptionSourceInterface {
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