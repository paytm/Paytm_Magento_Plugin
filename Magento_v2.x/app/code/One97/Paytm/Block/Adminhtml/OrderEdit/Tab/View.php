<?php
    namespace One97\Paytm\Block\Adminhtml\OrderEdit\Tab;

    class View extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface {
        protected $_template = 'tab/view/my_order_info.phtml';
        protected $jsonHelper;

        public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\Registry $registry,
            \Magento\Framework\Json\Helper\Data $jsonHelper,
            array $data = []
        ) {
            $this->_coreRegistry = $registry;
            $this->jsonHelper = $jsonHelper;
            parent::__construct($context, $data);
        }

        public function getOrder() {
            return $this->_coreRegistry->registry('current_order');
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
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('paytm_order_data');
            if($connection->isTableExists($tableName)){
                $sql = "Select * FROM ".$tableName." WHERE order_id=".$magentoOrderId." ORDER BY id DESC";
                $result = $connection->fetchAll($sql);
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
            
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $fetchStatusURL=$storeManager->getStore()->getBaseUrl().'paytm/Standard/Fetchstatus/';
            
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('paytm_order_data');
            $sql = "Select * FROM ".$tableName." WHERE order_id=".$magentoOrderId." ORDER BY id DESC"; //take always letest response
            $result = $connection->fetchAll($sql);
            $resArr=array();
            $button='';
            if(!empty($result)){
                $fetchStatusURL.='?fetchId='.$result[0]['id'];
                if(trim($result[0]['paytm_response'])!=''){
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