<?php


class One97_paytm_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paytm/info.phtml');
    }

    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }

    public function toPdf()
    {
        $this->setTemplate('paytm/pdf/info.phtml');
        return $this->toHtml();
    }
}