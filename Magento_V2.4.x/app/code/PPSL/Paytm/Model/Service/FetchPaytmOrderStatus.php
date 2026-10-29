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

namespace PPSL\Paytm\Model\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\OrderFactory;
use PPSL\Paytm\Helper\Data as PaytmHelper;
use PPSL\Paytm\Model\Paytm as PaytmModel;

/**
 * Shared logic for admin + storefront fetch-status JSON (responseRows for safe DOM build in JS).
 */
class FetchPaytmOrderStatus
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var PaytmModel
     */
    private $paytmModel;

    /**
     * @var PaytmHelper
     */
    private $paytmHelper;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    public function __construct(
        ResourceConnection $resourceConnection,
        DateTime $dateTime,
        PaytmModel $paytmModel,
        PaytmHelper $paytmHelper,
        JsonHelper $jsonHelper,
        OrderFactory $orderFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dateTime = $dateTime;
        $this->paytmModel = $paytmModel;
        $this->paytmHelper = $paytmHelper;
        $this->jsonHelper = $jsonHelper;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @return array{response: bool, responseRows: array<int, array{key: string, value: string, emphasis: bool}>}
     */
    public function execute(int $fetchId): array
    {
        $responseRows = [];
        $response = false;

        if ($fetchId <= 0 || !PaytmHelper::SAVE_PAYTM_RESPONSE) {
            return ['response' => $response, 'responseRows' => $responseRows];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('paytm_order_data');
        $select = $connection->select()
            ->from($tableName)
            ->where('id = ?', $fetchId);
        $result = $connection->fetchAll($select);
        if (empty($result)) {
            return ['response' => $response, 'responseRows' => $responseRows];
        }

        $requestParamList = [
            'MID' => $this->paytmModel->getMID(),
            'ORDERID' => $result[0]['paytm_order_id'],
        ];
        $statusCheckSum = $this->paytmModel->generateStatusChecksum($requestParamList);
        $requestParamList['CHECKSUMHASH'] = $statusCheckSum;
        $checkStatusUrl = $this->paytmModel->getNewStatusQueryUrl();
        $responseParamList = $this->paytmHelper->executecUrl($checkStatusUrl, $requestParamList);

        if (empty($responseParamList)) {
            return ['response' => $response, 'responseRows' => $responseRows];
        }

        $date = $this->dateTime->gmtDate();
        $paytmRes = $this->jsonHelper->jsonEncode($responseParamList);
        $connection->update(
            $tableName,
            [
                'transaction_id' => (string) ($responseParamList['TXNID'] ?? ''),
                'paytm_response' => $paytmRes,
                'status' => 1,
                'date_modified' => $date,
            ],
            ['id = ?' => $fetchId]
        );

        ksort($responseParamList);
        foreach ($responseParamList as $k => $val) {
            if ($k === 'CHECKSUMHASH') {
                continue;
            }
            $cellValue = is_array($val) ? $this->jsonHelper->jsonEncode($val) : (string) $val;
            $responseRows[] = [
                'key' => (string) $k,
                'value' => $cellValue,
                'emphasis' => ($k === 'STATUS'),
            ];
        }

        $order = $this->orderFactory->create()->loadByIncrementId($result[0]['order_id']);
        if (!$order->getId()) {
            return ['response' => $response, 'responseRows' => $responseRows];
        }

        if (($responseParamList['STATUS'] ?? '') === 'TXN_SUCCESS') {
            $autoInvoice = $this->paytmModel->autoInvoiceGen();
            if ($autoInvoice === 'authorize_capture') {
                $payment = $order->getPayment();
                if ($payment) {
                    $payment->setTransactionId($responseParamList['TXNID'])
                        ->setPreparedMessage(__('Paytm transaction has been successful.'))
                        ->setShouldCloseParentTransaction(true)
                        ->setIsTransactionClosed(0)
                        ->setAdditionalInformation(['PPSL', 'paytm'])
                        ->registerCaptureNotification(
                            $responseParamList['TXNAMOUNT'],
                            true
                        );
                    $payment->getCreatedInvoice();
                }
            }
            $comment = 'Transaction has marked as successfull by checkStatus API.';
            $order->setState('complete');
            $order->setStatus($this->paytmModel->getSuccessOrderStatus());
            $order->setExtOrderId($result[0]['paytm_order_id']);
            $order->addStatusToHistory($order->getStatus(), $comment);
            $order->save();
            $response = true;
        } elseif (($responseParamList['STATUS'] ?? '') === 'TXN_FAILURE') {
            $comment = (string) ($responseParamList['RESPMSG'] ?? '');
            $order->setState('canceled')->setStatus($this->paytmModel->getFailOrderStatus());
            $order->addStatusToHistory($order->getStatus(), $comment);
            $order->save();
        }

        return ['response' => $response, 'responseRows' => $responseRows];
    }
}
