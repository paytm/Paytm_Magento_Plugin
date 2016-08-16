<?php

class One97_paytm_Block_Success extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $successUrl = Mage::getUrl('*/*/success', array('_nosid' => true));

        $html	= '<html>'
        		. '<meta http-equiv="refresh" content="0; URL='.$successUrl.'">'
        		. '<body>'
        		. '<p>' . $this->__('Your payment has been successfully processed by our shop system.') . '</p>'
        		. '<p>' . $this->__('Please click <a href="%s">here</a> if you are not redirected automatically.', $successUrl) . '</p>'
        		. '</body></html>';

        return $html;
    }
}