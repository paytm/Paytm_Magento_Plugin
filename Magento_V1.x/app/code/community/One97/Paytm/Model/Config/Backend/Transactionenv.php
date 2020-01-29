<?php


class One97_paytm_Model_Config_Backend_Transactionenv extends Mage_Core_Model_Config_Data
{
	public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>'Production'),
            array('value' => 0, 'label'=>'Staging'),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('adminhtml')->__('No'),
            1 => Mage::helper('adminhtml')->__('Yes'),
        );
    }
	
	
}
