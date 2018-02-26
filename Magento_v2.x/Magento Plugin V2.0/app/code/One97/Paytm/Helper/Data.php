<?php

namespace One97\Paytm\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    protected $session;
    /*	19751/17Jan2018	*/
	    /*public $PAYTM_PAYMENT_URL_PROD = "https://secure.paytm.in/oltp-web/processTransaction";
	    public $STATUS_QUERY_URL_PROD = "https://secure.paytm.in/oltp/HANDLER_INTERNAL/TXNSTATUS";
	    public $NEW_STATUS_QUERY_URL_PROD = "https://secure.paytm.in/oltp/HANDLER_INTERNAL/getTxnStatus";
	    public $PAYTM_PAYMENT_URL_TEST = "https://pguat.paytm.com/oltp-web/processTransaction";
	    public $STATUS_QUERY_URL_TEST = "https://pguat.paytm.com/oltp/HANDLER_INTERNAL/TXNSTATUS";
	    public $NEW_STATUS_QUERY_URL_TEST = "https://pguat.paytm.com/oltp/HANDLER_INTERNAL/getTxnStatus";*/

	    /*public $PAYTM_PAYMENT_URL_PROD = "https://securegw.paytm.in/theia/processTransaction";
	    public $STATUS_QUERY_URL_PROD = "https://securegw.paytm.in/merchant-status/getTxnStatus";
	    public $NEW_STATUS_QUERY_URL_PROD = "https://securegw.paytm.in/merchant-status/getTxnStatus";
		
	    public $PAYTM_PAYMENT_URL_TEST = "https://securegw-stage.paytm.in/theia/processTransaction";
	    public $STATUS_QUERY_URL_TEST = "https://securegw-stage.paytm.in/merchant-status/getTxnStatus";
	    public $NEW_STATUS_QUERY_URL_TEST = "https://securegw-stage.paytm.in/merchant-status/getTxnStatus";*/
    /*	19751/17Jan2018 end	*/

    public function __construct(Context $context, \Magento\Checkout\Model\Session $session) {
        $this->session = $session;
        parent::__construct($context);
    }

    public function cancelCurrentOrder($comment) {
        $order = $this->session->getLastRealOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    public function restoreQuote() {
        return $this->session->restoreQuote();
    }

    public function getUrl($route, $params = []) {
        return $this->_getUrl($route, $params);
    }

    public function encrypt_e($input, $ky) {
		$key   = html_entity_decode($ky);
		$iv = "@@@@&&&&####$$$$";
		$data = openssl_encrypt ( $input , "AES-128-CBC" , $key, 0, $iv );
		return $data;
	}

	public function decrypt_e($crypt, $ky) {
		$key   = html_entity_decode($ky);
		$iv = "@@@@&&&&####$$$$";
		$data = openssl_decrypt ( $crypt , "AES-128-CBC" , $key, 0, $iv );
		return $data;
	}

    public function generateSalt_e($length) {
	$random = "";
	srand((double) microtime() * 1000000);
	$data = "AbcDE123IJKLMN67QRSTUVWXYZ";
	$data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
	$data .= "0FGH45OP89";
	for ($i = 0; $i < $length; $i++) {
		$random .= substr($data, (rand() % (strlen($data))), 1);
	}
	return $random;
    }

    public function checkString_e($value) {
	$myvalue = ltrim($value);
	$myvalue = rtrim($myvalue);
	if ($myvalue == 'null')
		$myvalue = '';
	return $myvalue;
    }

    public function getChecksumFromArray($arrayList, $key) {
	ksort($arrayList);
	$str = $this->getArray2Str($arrayList);
	$salt = $this->generateSalt_e(4);
	$finalString = $str . "|" . $salt;
	$hash = hash("sha256", $finalString);
	$hashString = $hash . $salt;
	$checksum = $this->encrypt_e($hashString, $key);
	return $checksum;
    }

    public function verifychecksum_e($arrayList, $key, $checksumvalue) {
	$arrayList = $this->removeCheckSumParam($arrayList);
	ksort($arrayList);
	$str = $this->getArray2StrForVerify($arrayList);
	$paytm_hash = $this->decrypt_e($checksumvalue, $key);
	$salt = substr($paytm_hash, -4);
	$finalString = $str . "|" . $salt;
	$website_hash = hash("sha256", $finalString);
	$website_hash .= $salt;
	$validFlag = FALSE;
	if ($website_hash == $paytm_hash) {
		$validFlag = TRUE;
	} else {
		$validFlag = FALSE;
	}
	return $validFlag;
    }

    public function getArray2StrForVerify($arrayList) {
	$paramStr = "";
	$flag = 1;
	foreach ($arrayList as $key => $value) {
            if ($flag) {
                $paramStr .= $this->checkString_e($value);
                $flag = 0;
            } else {
                $paramStr .= "|" . $this->checkString_e($value);
            }
	}
	return $paramStr;
    }
	
    public function getArray2Str($arrayList) {
	$findme   = 'REFUND';
	$findmepipe = '|';
	$paramStr = "";
	$flag = 1;	
	foreach ($arrayList as $key => $value) {
		$pos = strpos($value, $findme);
		$pospipe = strpos($value, $findmepipe);
		if ($pos !== false || $pospipe !== false) 
		{
			continue;
		}
		if ($flag) {
                	$paramStr .= $this->checkString_e($value);
                	$flag = 0;
		} else {
			$paramStr .= "|" . $this->checkString_e($value);
		}
	}
	return $paramStr;
    }

    public function redirect2PG($paramList, $key) {
	$hashString = $this->getchecksumFromArray($paramList);
	$checksum = $this->encrypt_e($hashString, $key);
    }

    public function removeCheckSumParam($arrayList) {
	if (isset($arrayList["CHECKSUMHASH"])) {
		unset($arrayList["CHECKSUMHASH"]);
	}
	return $arrayList;
    }
    function callAPI($apiURL, $requestParamList)
	{
	    $jsonResponse      = "";
	    $responseParamList = array();
	    $JsonData          = json_encode($requestParamList);
	    $postData          = 'JsonData=' . urlencode($JsonData);
	    $ch                = curl_init($apiURL);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($postData)
	    ));
	    $jsonResponse      = curl_exec($ch);
	    $responseParamList = json_decode($jsonResponse, true);
	    return $responseParamList;
	}
	
    function callNewAPI($apiURL, $requestParamList)
	{
	    $jsonResponse      = "";
	    $responseParamList = array();
	    $JsonData          = json_encode($requestParamList);
	    $postData          = 'JsonData=' . urlencode($JsonData);
	    $ch                = curl_init($apiURL);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($postData)
	    ));
	    $jsonResponse      = curl_exec($ch);
	    $responseParamList = json_decode($jsonResponse, true);
	    return $responseParamList;
	}	
    
}
