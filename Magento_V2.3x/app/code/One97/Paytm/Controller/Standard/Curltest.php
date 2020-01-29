<?php
    namespace One97\Paytm\Controller\Standard;
    use Magento\Framework\App\CsrfAwareActionInterface;
    use Magento\Framework\App\Request\InvalidRequestException;
    use Magento\Framework\App\RequestInterface;

    class Curltest extends \One97\Paytm\Controller\Paytm  implements CsrfAwareActionInterface{

        public function createCsrfValidationException(
            RequestInterface $request
        ): ?InvalidRequestException {
            return null;
        }

        public function validateForCsrf(RequestInterface $request): ?bool {
            return true;
        }
        
        /* this function for testing curl */
        public function execute() {
            $debug = array();
            if(!function_exists("curl_init")){
                $debug[0]["info"][] = "cURL extension is either not available or disabled. Check phpinfo for more info.";
            }else{ 
                $testing_urls=array();
                $getData=$this->getRequest()->getParams();
                if(!empty($getData)){
                    foreach ($getData as $key => $value) {
                        $testing_urls[]=$value;
                    }
                }else{
                    $currentPath = $_SERVER['PHP_SELF'];
                    $pathInfo = pathinfo($currentPath); 
                    $hostName = $_SERVER['HTTP_HOST']; 

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
                    $fetchStatusURL=$storeManager->getStore()->getBaseUrl();

                    $testing_urls = array(
                        $fetchStatusURL,
                        "https://www.gstatic.com/generate_204",
                        $this->getPaytmHelper()::TRANSACTION_STATUS_URL_PRODUCTION,
                        $this->getPaytmHelper()::TRANSACTION_STATUS_URL_STAGING
                    );
                }
                foreach($testing_urls as $key=>$url){
                    $debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $res = curl_exec($ch);
                    if (!curl_errno($ch)) {
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $debug[$key]["info"][] = "cURL executed succcessfully.";
                        $debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";
                    } else {
                        $debug[$key]["info"][] = "Connection Failed !!";
                        $debug[$key]["info"][] = "Error Code: <b>" . curl_errno($ch) . "</b>";
                        $debug[$key]["info"][] = "Error: <b>" . curl_error($ch) . "</b>";
                    }
                    if((in_array($url, array($this->getPaytmHelper()::TRANSACTION_STATUS_URL_PRODUCTION , $this->getPaytmHelper()::TRANSACTION_STATUS_URL_STAGING)))){
                        $debug[$key]["info"][] = "Response: <br/><!----- Response Below ----->" . $res;
                    }
                    curl_close($ch);
                }
            }
            foreach($debug as $k=>$v){
                echo "<ul>";
                foreach($v["info"] as $info){
                    echo "<li>".$info."</li>";
                }
                echo "</ul>";
                echo "<hr/>";
            }
            die;
        }
    }
?>