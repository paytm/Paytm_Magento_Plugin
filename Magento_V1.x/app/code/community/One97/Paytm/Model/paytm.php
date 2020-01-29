<?php
 
class One97_paytm_Model_paytm extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('paytm/paytm');
    }
}