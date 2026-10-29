<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
declare(strict_types=1);

namespace PPSL\Paytm\Controller\Adminhtml\Standard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use PPSL\Paytm\Model\Service\FetchPaytmOrderStatus;

/**
 * Admin order tab AJAX: paytm/standard/fetchstatus under admin front name.
 */
class Fetchstatus extends Action implements HttpGetActionInterface, CsrfAwareActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Sales::actions_view';

    /**
     * @var FetchPaytmOrderStatus
     */
    private $fetchPaytmOrderStatus;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        Context $context,
        FetchPaytmOrderStatus $fetchPaytmOrderStatus,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->fetchPaytmOrderStatus = $fetchPaytmOrderStatus;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $fetchId = (int) $this->getRequest()->getParam('fetchId');
        $data = $this->fetchPaytmOrderStatus->execute($fetchId);
        return $this->resultJsonFactory->create()->setData($data);
    }
}
