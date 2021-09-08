<?php
	namespace One97\Paytm\Block\Form;

	class Paytm extends \Magento\Payment\Block\Form {
	   // public $_template = 'One97_Paytm::form/paytm.phtml';
	    public $objectPaytmCont='';
	    public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            array $data = []
        ) {
        	parent::__construct($context, $data);
        }
	}
?>
