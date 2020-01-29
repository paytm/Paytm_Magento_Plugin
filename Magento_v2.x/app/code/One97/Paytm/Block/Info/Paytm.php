<?php

	namespace One97\Paytm\Block\Info;

	class Paytm extends \Magento\Payment\Block\Info {

		/* this fuction return base url to admin config page. */
		public function getBaseURL() {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
			$fetchStatusURL=$storeManager->getStore()->getBaseUrl();
			return $fetchStatusURL;
		}
	}
?>