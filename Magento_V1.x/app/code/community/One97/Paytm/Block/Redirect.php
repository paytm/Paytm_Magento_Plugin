<?php


class One97_paytm_Block_Redirect extends Mage_Core_Block_Template
{
     //Return checkout session instance
    
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    
     // Return order instance
     
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }


     //Get form data

    public function getFormData()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getFormFields();
    }

    /**
     * Getting gateway ur
     */
    public function getFormAction()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getUrl();
    }
}