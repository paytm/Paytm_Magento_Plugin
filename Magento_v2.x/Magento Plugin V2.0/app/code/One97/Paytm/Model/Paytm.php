<?php

namespace One97\Paytm\Model;

use One97\Paytm\Helper\Data as DataHelper;

class Paytm extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'paytm';
    protected $_code = self::CODE;
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_isOffline = true;
    protected $helper;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = array('INR');
    protected $_formBlockType = 'One97\Paytm\Block\Form\Paytm';
    protected $_infoBlockType = 'One97\Paytm\Block\Info\Paytm';
    protected $urlBuilder;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \One97\Paytm\Helper\Data $helper
    ) {
        $this->helper = $helper;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

        $this->_minAmount = "0.50";
        $this->_maxAmount = "1000000";
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Instantiate state and set it to state object.
     *
     * @param string                        $paymentAction
     * @param \Magento\Framework\DataObject $stateObject
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);      
    
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && (
                $quote->getBaseGrandTotal() < $this->_minAmount
                || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    public function buildPaytmRequest($order)
    {
        $callBackUrl=$this->urlBuilder->getUrl('paytm/Standard/Response', ['_secure' => true]);
        if($this->getConfigData("custom_callbackurl")=='1'){
            $callBackUrl=$this->getConfigData("callback_url")!=''?$this->getConfigData("callback_url"):$callBackUrl;
        }
        $params = array('MID' => $this->getConfigData("MID"),               
                        'TXN_AMOUNT' => round($order->getGrandTotal(), 2),
                        'CHANNEL_ID' => $this->getConfigData("Channel_Id"),
                        'INDUSTRY_TYPE_ID' => $this->getConfigData("Industry_id"),
                        'WEBSITE' => $this->getConfigData("Website"),
                        'CUST_ID' => $order->getCustomerEmail(),
                        'ORDER_ID' => $order->getRealOrderId(),                     
                        'EMAIL' => $order->getCustomerEmail(),
                        'CALLBACK_URL' => $callBackUrl);    
        if(isset($order->paytmPromoCode)){
            $params['PROMO_CAMP_ID']=$order->paytmPromoCode;
        }
        
        $checksum = $this->helper->getChecksumFromArray($params, $this->getConfigData("merchant_key"));
        
        $params['CHECKSUMHASH'] = str_replace("+","%2b",$checksum);
        
        $url = $this->getConfigData('transaction_url')."?";
        $urlparam = "";
        foreach($params as $key => $val){
            $urlparam = $urlparam.$key."=".$val."&";
        }
        $url = $url . $urlparam;
        return $url;
    }

    public function validateResponse($res,$order_id)
    {
        //print_r($res);
        $checksum = $res["CHECKSUMHASH"];
        if ($this->helper->verifychecksum_e($res,$this->getConfigData("merchant_key"),$checksum)) {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }
    
    public function generateStatusChecksum($requestParamList)
    {
        $result = $this->helper->getChecksumFromArray($requestParamList,$this->getConfigData("merchant_key"));            
        return $result;
    }

    public function autoInvoiceGen()
    {
        $result = $this->getConfigData("payment_action");            
        return $result;
    }

    public function getRedirectUrl()
    {
        $url = $this->getConfigData('transaction_url');
        return $url;
    }
    
    public function getStatusQueryUrl()
    {
        $url = $this->getConfigData('transaction_status_url');
        return $url;
    }
    
    public function getNewStatusQueryUrl()
    {
        $url = $this->getConfigData('transaction_status_url');
        return $url;
    }

    public function getReturnUrl()
    {
        
    }

    public function getCancelUrl()
    {
        
    }
}
