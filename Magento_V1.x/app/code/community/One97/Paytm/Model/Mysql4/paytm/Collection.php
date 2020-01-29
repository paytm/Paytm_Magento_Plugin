<?php
 
class One97_paytm_Model_Mysql4_paytm_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::__construct();
        $this->_init('paytm/paytm');
    }
}