<?php
 
class One97_paytm_Model_Mysql4_paytm extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {   
        $this->_init('paytm/paytm', 'paytm_id');
    }
