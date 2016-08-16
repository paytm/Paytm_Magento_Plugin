<?php

class One97_paytm_Block_Failure extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paytm/failure.phtml');
    }

    /**
     * Get continue shopping url
     */
    public function getContinueShoppingUrl()
    {
        return Mage::getUrl('*/*/fail', array('_nosid' => true));
    }
}