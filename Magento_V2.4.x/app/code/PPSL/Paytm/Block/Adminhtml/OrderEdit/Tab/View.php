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
            $magentoOrderId=$this->getOrderIncrementId();
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('paytm_order_data');
            if($connection->isTableExists($tableName)){
                $select = $connection->select()
                    ->from($tableName)
                    ->where('order_id = ?', (string) $magentoOrderId)
                    ->order('id DESC');
                $result = $connection->fetchAll($select);
                if(!empty($result)){
                    if(isset($result[0]['paytm_response']) || trim($result[0]['paytm_response'])==''){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }

        public function isHidden() {
            return false;
        }

        public function getPaytmResponseHtml($viewButton=false) {
            $response='<tr> <th colspan="2" style="text-align: center;">No Record</th> </tr>';
            $magentoOrderId=$this->getOrderIncrementId();

            $fetchStatusURL = $this->getUrl('paytm/standard/fetchstatus');

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
                $fetchStatusURL.='?fetchId='.$result[0]['id'];
                if(trim((string) $result[0]['paytm_response'])!=''){
                    $result[0]['paytm_response'] = preg_replace('/"({.*?})"/', '$1', $result[0]['paytm_response']);
                    $resArr=$this->jsonHelper->jsonDecode($result[0]['paytm_response']);
                    $response='';
                    ksort($resArr);
                    foreach ($resArr as $k => $val) {
                        switch ($k) {
                            case 'CHECKSUMHASH':
                                break;
                            case 'STATUS':
                                $response.=' <tr style="font-weight:900;"> <th>'.$k.'</th> <td>'.$val.'</td> </tr> ';
                                $val!='TXN_SUCCESS'?$button='<input type="button" value="Fetch Status" class="fetchStatusBtn" moveURL="'.$fetchStatusURL.'" style="vertical-align: 0; margin-left: 10%; background-color: #82c96b; color: white; border-radius: 5px; border-color: transparent;">':'';
                                break;
                            
                            default:
                                $val= is_array($val)?json_encode($val):$val;
                                $response.=' <tr> <th>'.$k.'</th> <td>'.$val.'</td> </tr> ';
                                break;
                        }
                    }
                }else{
                    $button='<input type="button" value="Fetch Status" class="fetchStatusBtn" moveURL="'.$fetchStatusURL.'" style="vertical-align: 0; margin-left: 10%; background-color: #82c96b; color: white; border-radius: 5px; border-color: transparent;">';
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