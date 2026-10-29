<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
    namespace PPSL\Paytm\Block\Adminhtml\OrderEdit\Tab;

    class View extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface {
        protected $_template = 'tab/view/my_order_info.phtml';
        protected $jsonHelper;
        protected $request;
        protected $orderRepository;
        private $order;
        private $resourceConnection;

        public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\App\RequestInterface $request,
            \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
            \Magento\Framework\Json\Helper\Data $jsonHelper,
            \Magento\Framework\App\ResourceConnection $resourceConnection,
            array $data = []
        ) {
            $this->request = $request;
            $this->orderRepository = $orderRepository;
            $this->jsonHelper = $jsonHelper;
            $this->resourceConnection = $resourceConnection;
            parent::__construct($context, $data);
        }

        public function getOrder() {
            if ($this->order === null) {
                $orderId = (int) $this->request->getParam('order_id');
                $this->order = $this->orderRepository->get($orderId);
            }
            return $this->order;
        }
        
        public function getOrderId() {
            return $this->getOrder()->getEntityId();
        }

        public function getOrderIncrementId() {
            return $this->getOrder()->getIncrementId();
        }
        
        public function getTabLabel() {
            return __('Paytm Response');
        }

        public function getTabTitle() {
            return __('Paytm Response');
        }

        public function canShowTab() {
            $magentoOrderId = $this->getOrderIncrementId();
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('paytm_order_data');
            if (!$connection->isTableExists($tableName)) {
                return false;
            }
            $select = $connection->select()
                ->from($tableName)
                ->where('order_id = ?', (string) $magentoOrderId)
                ->order('id DESC');
            $result = $connection->fetchAll($select);
            return !empty($result);
        }

        public function isHidden() {
            return false;
        }

        public function getPaytmResponseHtml($viewButton=false) {
            $response='<tr> <th colspan="2" style="text-align: center;">'
                .$this->escapeHtml(__('No Record')).'</th> </tr>';
            $magentoOrderId=$this->getOrderIncrementId();

            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('paytm_order_data');
            $select = $connection->select()
                ->from($tableName)
                ->where('order_id = ?', (string) $magentoOrderId)
                ->order('id DESC');
            $result = $connection->fetchAll($select);
            $resArr=array();
            $button='';
            if(!empty($result)){
                $fetchStatusUrlEscaped = $this->escapeUrl($this->getUrl(
                    'paytm/standard/fetchstatus',
                    ['fetchId' => (int) $result[0]['id']]
                ));
                $paytmResponseRaw = (string) ($result[0]['paytm_response'] ?? '');
                if (trim($paytmResponseRaw) !== '') {
                    $paytmResponseRaw = preg_replace('/"({.*?})"/', '$1', $paytmResponseRaw);
                    $resArr = $this->jsonHelper->jsonDecode($paytmResponseRaw);
                    if (!is_array($resArr)) {
                        $resArr = [];
                    }
                    $response = '';
                    ksort($resArr);
                    foreach ($resArr as $k => $val) {
                        switch ($k) {
                            case 'CHECKSUMHASH':
                                break;
                            case 'STATUS':
                                $response .= ' <tr style="font-weight:900;"> <th>'
                                    . $this->escapeHtml((string) $k) . '</th> <td>'
                                    . $this->escapeHtml((string) $val) . '</td> </tr> ';
                                $val != 'TXN_SUCCESS' ? $button = '<input type="button" value="'
                                    . $this->escapeHtmlAttr(__('Fetch Status')) . '" class="fetchStatusBtn" moveURL="'
                                    . $fetchStatusUrlEscaped
                                    . '" style="vertical-align: 0; margin-left: 10%; background-color: #82c96b; color: white; border-radius: 5px; border-color: transparent;">' : '';
                                break;

                            default:
                                $val = is_array($val) ? $this->jsonHelper->jsonEncode($val) : $val;
                                $response .= ' <tr> <th>' . $this->escapeHtml((string) $k) . '</th> <td>'
                                    . $this->escapeHtml((string) $val) . '</td> </tr> ';
                                break;
                        }
                    }
                } else {
                    $button = '<input type="button" value="' . $this->escapeHtmlAttr(__('Fetch Status'))
                        . '" class="fetchStatusBtn" moveURL="' . $fetchStatusUrlEscaped
                        . '" style="vertical-align: 0; margin-left: 10%; background-color: #82c96b; color: white; border-radius: 5px; border-color: transparent;">';
                }
            }
            if($viewButton){
                return $button;
            }else{
                return $response;
            }
        }
    }
?>