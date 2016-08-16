<?php
//contains utility functions for encryption decrytion

class One97_paytm_Helper_Data2 extends Mage_Payment_Helper_Data
{
	public $PAYTM_PAYMENT_URL_PROD = "https://secure.paytm.in/oltp-web/processTransaction";
	public $STATUS_QUERY_URL_PROD = "https://secure.paytm.in/oltp/HANDLER_INTERNAL/TXNSTATUS";

	public $PAYTM_PAYMENT_URL_TEST = "https://pguat.paytm.com/oltp-web/processTransaction";
	public $STATUS_QUERY_URL_TEST = "https://pguat.paytm.com/oltp/HANDLER_INTERNAL/TXNSTATUS";
}
