<?php

include_once ("KuaiDi100.pub.config.php");
class KuaiDi100PubHelper {
	
	var  $typeCom;//快递公司
	var  $typeNu;  //快递单号
	
	
	public function kuaidiInfo() {
		

		//echo $typeCom.'<br/>' ;
		//echo $typeNu ;
		
		function __construct() {
			
		}
		
		$url = $this->createUrl();
		
		//优先使用curl模式发送数据
		if (function_exists ( 'curl_init' ) == 1) {
			$curl = curl_init ();
			curl_setopt ( $curl, CURLOPT_URL, $url );
			curl_setopt ( $curl, CURLOPT_HEADER, 0 );
			curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt ( $curl, CURLOPT_USERAGENT, $_SERVER ['HTTP_USER_AGENT'] );
			curl_setopt ( $curl, CURLOPT_TIMEOUT, 5 );
			$get_content = curl_exec ( $curl );
			curl_close ( $curl );
		} else {
			include ("snoopy.php");
			$snoopy = new snoopy ();
			$snoopy->referer = 'http://www.google.com/'; //伪装来源
			$snoopy->fetch ( $url );
			$get_content = $snoopy->results;
		}
		/*print_r ( $get_content . '<br/>' . $powered );
		exit ();*/
		return $get_content;
	}
	
	public function createUrl(){
		///const url = 'http://api.kuaidi100.com/api?id=' . $AppKey . '&com=' . $typeCom . '&nu=' . $typeNu . '&show=2&muti=1&order=asc';
		$urlObj ["id"] =KuaiDi100Conf_pub::APPKEY;// $this->appid; //WxPayConf_pub::APPID;
		$urlObj ["com"] = $this->typeCom;
		$urlObj ["typeNu"] = $this->typeNu;
		$urlObj ["show"] = KuaiDi100Conf_pub::SHOW;
		$urlObj["muti"] = KuaiDi100Conf_pub::MUTI;
		$urlObj["order"] = KuaiDi100Conf_pub::ORDER;
		$bizString = $this->formatBizQueryParaMap ( $urlObj, false );
		return KuaiDi100Conf_pub::URL . $bizString;
	}
	
	public function setTypeCom($typeCom){
		$this->typeCom = $typeCom;
	}
	
	public function setTypeNu($typeNu){
		$this->typeNu = $typeNu;
	}

}
