<?php
namespace One97\Paytm\Controller\Standard;

class CurlTest extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
		return parent::__construct($context);
	}

	public function execute()
	{
		// echo "<pre>";print_r($_GET);die;
		$debug = array();
		if(!function_exists("curl_init")){
			$debug[0]["info"][] = "cURL extension is either not available or disabled. Check phpinfo for more info.";

		}else{ 
			// this site homepage URL
			$testing_urls=array();
			if(!empty($_GET)){
				foreach ($_GET as $key => $value) {
					$testing_urls[]=$value;
				}
			}else{
				$currentPath = $_SERVER['PHP_SELF'];
				$pathInfo = pathinfo($currentPath); 
				$hostName = $_SERVER['HTTP_HOST']; 
				$protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https://'?'https://':'http://';

				$testing_urls = array(
					$protocol.$hostName.$pathInfo['dirname']."/",
					"www.google.co.in",
					"https://pguat.paytm.com/oltp/HANDLER_INTERNAL/getTxnStatus"
				);
			}
			/*echo "<pre>";print_r($testing_urls);
			echo "<hr/>";*/
			// loop over all URLs, maintain debug log for each response received
			foreach($testing_urls as $key=>$url){

				$debug[$key]["info"][] = "Connecting to <b>" . $url . "</b> using cURL";

				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$res = curl_exec($ch);

				if (!curl_errno($ch)) {
					$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					$debug[$key]["info"][] = "cURL executed succcessfully.";
					$debug[$key]["info"][] = "HTTP Response Code: <b>". $http_code . "</b>";

					// $debug[$key]["content"] = $res;

				} else {
					$debug[$key]["info"][] = "Connection Failed !!";
					$debug[$key]["info"][] = "Error Code: <b>" . curl_errno($ch) . "</b>";
					$debug[$key]["info"][] = "Error: <b>" . curl_error($ch) . "</b>";
					break;
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

			// echo "<div style='display:none;'>" . $v["content"] . "</div>";
			echo "<hr/>";
		}
		die;
	}
}