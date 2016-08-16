<?php


class One97_paytm_Model_Config_Source_Instid2
{
	public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Production')),
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Test')),
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
            0 => Mage::helper('adminhtml')->__('Test'),
            1 => Mage::helper('adminhtml')->__('Production'),
        );
    }
	
	
}
