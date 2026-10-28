<?php
/**
 * Paytm Payment Gateway for Magento 2.4.x
 *
 * @author    PPSL
 * @copyright Copyright (c) 2026-10-26 PPSL. All rights reserved.
 * @category  PPSL
 * @package   PPSL_Paytm
 */
    namespace PPSL\Paytm\Controller\Standard;
    use Magento\Framework\App\CsrfAwareActionInterface;
    use Magento\Framework\App\Request\InvalidRequestException;
    use Magento\Framework\App\RequestInterface;
    use Magento\Framework\Controller\ResultFactory;

    class Curltest extends \PPSL\Paytm\Controller\Paytm  implements CsrfAwareActionInterface{

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
                    $fetchStatusURL = $this->_url->getBaseUrl();

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
                }
            }
            $html = '';
            foreach ($debug as $v) {
                $html .= '<ul>';
                foreach ($v['info'] as $info) {
                    $html .= '<li>' . $info . '</li>';
                }
                $html .= '</ul><hr/>';
            }

            /** @var \Magento\Framework\Controller\Result\Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHeader('Content-Type', 'text/html; charset=UTF-8');
            $result->setContents($html);
            return $result;
        }
    }
?>