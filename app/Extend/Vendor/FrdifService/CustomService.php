<?php
include_once ("soap.pub.config.php");
/**
 *
 * @author Administrator
 *        
 */
class CustomService {
	// 'userid'=>'test','password'=>'00B54E5ADD61AC9C','cmdid'=>'1000','inputpara'=>'2,18613022123','rtn'=>'1'
	var $cmdid;
	var $queryType;
	var $queryParam;
	
	var $posturl;
	var $userid;
	var $password;
	function setCmdid($cmdid_) {
		$this->cmdid = $cmdid_;
	}
	function setQueryType($queryType_) {
		$this->queryType = $queryType_;
	}
	function setQueryParam($queryParam_) {
		$this->queryParam = $queryParam_;
	}
	
	
	function setPosturl($posturl_){
		$this->posturl = $posturl_;
	}
	
	function  setUserid ($userid_){
		$this->userid = $userid_;
	}
	
	function setPassword ($password_) {
		$this->password = $password_;
	}
	function processdata($param) {
		ini_set ( "soap.wsdl_cache_enabled", "1" ); // soap缓存
		ini_set( 'default_socket_timeout', 60 );    // timeout
		$soap = new SoapClient ($this->posturl ); //soapConf_pub::URL 
		try {
			$param = array (
					'userid' => $this->userid ,//soapConf_pub::USERID,
					'password' => $this->password,//soapConf_pub::PASSWORD,
					'cmdid' => $this->cmdid,
					'inputpara' =>$this->queryParam,
					'rtn' => '1' 
			);
			$data = object_to_array ( $soap->processdata ( $param ) );
			/*
			if ($data ['rtn'] != - 1) {
				$info = explode('	', $data['outputpara']);
				return $info;
			} else {
				return -1;
			}
			*/
			
			return $data; 
			
			
			
		} catch ( Exception $e ) {
			return '-1';
		}
	}
}