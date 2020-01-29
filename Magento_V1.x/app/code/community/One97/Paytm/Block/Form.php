<?php


class One97_paytm_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paytm/form.phtml');
    }

    protected function _getConfig()
    {
        return Mage::getSingleton('paytm/config');
    }
}