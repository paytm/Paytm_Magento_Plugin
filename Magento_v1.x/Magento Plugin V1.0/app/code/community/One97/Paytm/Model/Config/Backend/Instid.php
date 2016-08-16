<?php


class One97_paytm_Model_Config_Backend_Instid extends Mage_Core_Model_Config_Data
{
	//decrypt value when loading
   protected function _afterLoad()
    {
        $value = (string)$this->getValue();
        if (!empty($value) && ($decrypted = Mage::helper('paytm')->decrypt_e($value, $const = (string)Mage::getConfig()->getNode('global/crypt/key')))) {
            $this->setValue($decrypted);
        }
    }

    
    //Encrypt value before saving
     
    protected function _beforeSave()
    {
        $value = (string)$this->getValue();
        if (!empty($value) && ($encrypted = Mage::helper('paytm')->encrypt_e($value, $const = (string)Mage::getConfig()->getNode('global/crypt/key')))) {
            $this->setValue($encrypted);
        }
    }
	
	
}
