<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */

	namespace PPSL\Paytm\Block\Info;

	class Paytm extends \Magento\Payment\Block\Info {

		/**
		 * @var \Magento\Store\Model\StoreManagerInterface
		 */
		private $storeManager;

		public function __construct(
			\Magento\Framework\View\Element\Template\Context $context,
			\Magento\Store\Model\StoreManagerInterface $storeManager,
			array $data = []
		) {
			$this->storeManager = $storeManager;
			parent::__construct($context, $data);
		}

		/* this fuction return base url to admin config page. */
		public function getBaseURL() {
			return $this->storeManager->getStore()->getBaseUrl();
		}
	}
?>
