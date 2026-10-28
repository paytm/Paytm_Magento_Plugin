<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
	namespace PPSL\Paytm\Block\Form;

	class Paytm extends \Magento\Payment\Block\Form {
	   // public $_template = 'PPSL_Paytm::form/paytm.phtml';
	    public $objectPaytmCont='';
	    public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            array $data = []
        ) {
        	parent::__construct($context, $data);
        }
	}
?>
