<?php
class One97_Paytm_Block_Adminhtml_Order_View_Tab_Contents extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface {
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('paytm/order/view/tab/contents.phtml');
    }

    public function getTabLabel() {
        return $this->__('Paytm Response');
    }

    public function getTabTitle() {
        // return $this->__($test);
        return $this->__('Paytm Response');
    }

    public function canShowTab() {
        $order=$this->getOrder();
        $orderId=$order->getIncrementId();
        $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tableName = Mage::getSingleton('core/resource')->getTableName('paytm_order_data');
        $query="SELECT id FROM ".$tableName." WHERE order_id='".$orderId."' ORDER BY ID DESC";
        $results = $connectionRead->fetchAll($query);
        if(isset($results[0]['id'])){
            return true;
        }else{
            return false;
        }
    }

    public function isHidden() {
        return false;
    }

    public function getOrder(){
        return Mage::registry('current_order');
    }

    public function paytmResponse($fetchButton=false){
        $order=$this->getOrder();
        $orderId=$order->getIncrementId();
        $connectionRead = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tableName = Mage::getSingleton('core/resource')->getTableName('paytm_order_data');
        $query="SELECT * FROM ".$tableName." WHERE order_id='".$orderId."' ORDER BY ID DESC";
        $results = $connectionRead->fetchAll($query);
        if($fetchButton){
            if(isset($results[0]['paytm_response'])){
                $tableBody='<button title="Update Status" type="button" id="fetchStatusBtn" class="scalable" onclick="fetchStatus()" style="float:right;"><span><span><span>Fetch Status</span></span></span></button> <input type="hidden" class="paytmResponseId" value="'.$results[0]['id'].'">';
            }
        }else{
            $tableBody='<tr class="even pointer"> <td colspan="2" style="text-align: center;">No Data</td> </tr>';
            if(isset($results[0]['paytm_response'])){
                if(trim($results[0]['paytm_response'])!=''){
                    $jsonResponse = json_decode($results[0]['paytm_response'], true);
                    if(is_array($jsonResponse)){
                        $tableBody='';
                        ksort($jsonResponse);
                        foreach($jsonResponse as $key=>$value){
                            if($key=="STATUS"){
                                $tableBody.='<tr class="even pointer"> <td class=" ">'.$key.'</td> <td class="a-right a-right "><span style="    font-weight: 900;">'.$value.'</span> </td> </tr>';
                            }else{
                                $tableBody.='<tr class="even pointer"> <td class=" ">'.$key.'</td> <td class="a-right a-right ">'.$value.' </td> </tr>';
                            }
                        }
                    }
                }
            }
        }
        return $tableBody;
    }
}