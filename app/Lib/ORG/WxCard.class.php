<?php
class WxCard{
	private $uid;
	private $appid;
	private $appsecret;
	public function __construct(){
		$this->uid = $uid;
		$this->appid =C ( 'spconf_appid_'. $this->uid);
		$this->appsecret = C('spconf_appsecret_'.$this->uid);
		$this->wxAccessToken=new WeixinInterface();
	}
	/*******************************************************
	 *      微信jsApi整合方法 - 通过调用此方法获得jsapi数据
	 *******************************************************/
	public function wxJsapiPackage(){
		$jsapi_ticket = $this->wxVerifyJsApiTicket();
		 
		// 注意 URL 一定要动态获取，不能 hardcode.
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		$url = $protocol.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		 
		$timestamp = time();
		$nonceStr = $this->wxNonceStr();
		 
		$signPackage = array(
				"jsapi_ticket" => $jsapi_ticket,
				"nonceStr"  => $nonceStr,
				"timestamp" => $timestamp,
				"url"       => $url
		);
		 
		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$rawString = "jsapi_ticket=$jsapi_ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
		 
		//$rawString = $this->wxFormatArray($signPackage);
		$signature = $this->wxSha1Sign($rawString);
		 
		$signPackage['signature'] = $signature;
		$signPackage['rawString'] = $rawString;
		$signPackage['appId'] = self::appId;
		 
		return $signPackage;
	}
	
	 
	/*******************************************************
	 *      微信卡券：JSAPI 卡券Package - 基础参数没有附带任何值 - 再生产环境中需要根据实际情况进行修改
	 *******************************************************/
	public function wxCardPackage($cardId , $timestamp = ''){
		$api_ticket = $this->wxVerifyJsApiTicket();
		if(!empty($timestamp)){
			$timestamp = $timestamp;
		}
		else{
			$timestamp = time();
		}
		 
		$arrays = array(self::appSecret,$timestamp,$cardId);
		sort($arrays , SORT_STRING);
		//print_r($arrays);
		//echo implode("",$arrays)."<br />";
		$string = sha1(implode($arrays));
		//echo $string;
		$resultArray['cardId'] = $cardId;
		$resultArray['cardExt'] = array();
		$resultArray['cardExt']['code'] = '';
		$resultArray['cardExt']['openid'] = '';
		$resultArray['cardExt']['timestamp'] = $timestamp;
		$resultArray['cardExt']['signature'] = $string;
		//print_r($resultArray);
		return $resultArray;
	}
	 
	/*******************************************************
	 *      微信卡券：JSAPI 卡券全部卡券 Package
	 *******************************************************/
	public function wxCardAllPackage($cardIdArray = array(),$timestamp = ''){
		$reArrays = array();
		if(!empty($cardIdArray) && (is_array($cardIdArray) || is_object($cardIdArray))){
			//print_r($cardIdArray);
			foreach($cardIdArray as  $value){
				//print_r($this->wxCardPackage($value,$openid));
				$reArrays[] = $this->wxCardPackage($value,$timestamp);
			}
			//print_r($reArrays);
		}
		else{
			$reArrays[] = $this->wxCardPackage($cardIdArray,$timestamp);
		}
		return strval(json_encode($reArrays));
	}
	/****************************************************
	 *  微信提交API方法，返回微信指定JSON
	 ****************************************************/
	
	public function wxHttpsRequest($url,$data = null){
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
	/****************************************************
	 *  微信获取AccessToken 返回指定微信公众号的at信息
	 ****************************************************/
	
	public function wxAccessToken($appId = NULL , $appSecret = NULL){
// 		dump("token");
		$appId          = 'wxb993f857b2fbef0c';//$this->appid;//is_null($appId) ? self::appId : $appId;
		$appSecret      = '0509837a89651b8929e933843173029b';//$this->appsecret;//is_null($appSecret) ? self::appSecret : $appSecret;
		 
		$url            = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appSecret;
		$result         = $this->wxHttpsRequest($url);
		//print_r($result);
		$jsoninfo       = json_decode($result, true);
		$access_token   = $jsoninfo["access_token"];
// 		dump($access_token); 
		return $access_token;
	}
		/*******************************************************
         *      微信卡券：上传LOGO - 需要改写动态功能
         *******************************************************/
        public function wxCardUpdateImg() {
            $wxAccessToken  = $this->wxAccessToken();
//             dump($wxAccessToken);
            //$data['access_token'] =  $wxAccessToken;
            $data['buffer']     =  '@E:\ikshop\data\upload\sys\logo.jpg';
            $url            = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=".$wxAccessToken;
//             $url            = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=
//             					XazvJeJy8Q--0G5_7boVLrO8jwgLGK8k0ADLlugcMcxUCERea8cbJV_grV-r0jP14QOuPSxrjDSLLM0OrjeqTH-hIb_B7Eb7GCFM92rUyZk";
            $result         = $this->wxHttpsRequest($url,$data);
//             dump($result);
            $jsoninfo       = json_decode($result, true);
//             dump($jsoninfo);
            return $jsoninfo;
            //array(1) { ["url"]=> string(121) "http://mmbiz.qpic.cn/mmbiz/ibuYxPHqeXePNTW4ATKyias1Cf3zTKiars9PFPzF1k5icvXD7xW0kXUAxHDzkEPd9micCMCN0dcTJfW6Tnm93MiaAfRQ/0" } 
        }
        /*******************************************************
         *      微信卡券：获取颜色
         *******************************************************/
        public function wxCardColor(){
        	$wxAccessToken  = $this->wxAccessToken();
        	$url                = "https://api.weixin.qq.com/card/getcolors?access_token=".$wxAccessToken;
        	$result         = $this->wxHttpsRequest($url);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
         
        /*******************************************************
         *      微信卡券：拉取门店列表
         *******************************************************/
        public function wxBatchGet($offset = 0, $count = 0){
        	$jsonData = json_encode(array('offset' => intval($offset) , 'count' => intval($count)));
        	$wxAccessToken  = $this->wxAccessToken();
        	$url            = "https://api.weixin.qq.com/card/location/batchget?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
         
        /*******************************************************
         *      微信卡券：创建卡券
         *******************************************************/
        public function wxCardCreated($jsonData) {
//         	$wxAccessToken = $this->wxAccessToken->findAccess_token();
        	$wxAccessToken  = $this->wxAccessToken();
// 			$wxAccessToken = 'XazvJeJy8Q--0G5_7boVLrO8jwgLGK8k0ADLlugcMcxUCERea8cbJV_grV-r0jP14QOuPSxrjDSLLM0OrjeqTH-hIb_B7Eb7GCFM92rUyZk';
        	$url            = "https://api.weixin.qq.com/card/create?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
         
        /*******************************************************
         *      微信卡券：查询卡券详情
         *******************************************************/
        public function wxCardGetInfo($jsonData) {
        	$wxAccessToken  = $this->wxAccessToken();
        	$url            = "https://api.weixin.qq.com/card/get?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
        
        /*******************************************************
         *      微信卡券：设置白名单
         *******************************************************/
        public function wxCardWhiteList($jsonData){
        	$wxAccessToken  = $this->wxAccessToken();
        	$url            = "https://api.weixin.qq.com/card/testwhitelist/set?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
        
        
        /*******************************************************
         *      微信卡券：消耗卡券
         *******************************************************/
        public function wxCardConsume($jsonData){
        	$wxAccessToken  = $this->wxAccessToken();
        	$url            = "https://api.weixin.qq.com/card/code/consume?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
        
        /*******************************************************
         *      微信卡券：删除卡券
         *******************************************************/
        public function wxCardDelete($jsonData){
        	$wxAccessToken  = $this->wxAccessToken();
        	$url            = "https://api.weixin.qq.com/card/delete?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
         
        /*******************************************************
         *      微信卡券：选择卡券 - 解析CODE
         *******************************************************/
        public function wxCardDecryptCode($jsonData){
        	$wxAccessToken  = $this->wxAccessToken();
        	$url            = "https://api.weixin.qq.com/card/code/decrypt?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
         
        /*******************************************************
         *      微信卡券：更改库存
         *******************************************************/
        public function wxCardModifyStock($cardId , $increase_stock_value = 0 , $reduce_stock_value = 0){
        	if(intval($increase_stock_value) == 0 && intval($reduce_stock_value) == 0){
        		return false;
        	}
        	 
        	$jsonData = json_encode(array("card_id" => $cardId , 'increase_stock_value' => intval($increase_stock_value) , 'reduce_stock_value' => intval($reduce_stock_value)));
        	 
        	$wxAccessToken  = $this->wxAccessToken();
        	$url            = "https://api.weixin.qq.com/card/modifystock?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
        
        /*******************************************************
         *      微信卡券：查询用户CODE
         *******************************************************/
        public function wxCardQueryCode($code , $cardId = ''){
        	 
        	$jsonData = json_encode(array("code" => $code , 'card_id' => $cardId ));
        	 
        	$wxAccessToken  = $this->wxAccessToken();
        	$url            = "https://api.weixin.qq.com/card/code/get?access_token=" . $wxAccessToken;
        	$result         = $this->wxHttpsRequest($url,$jsonData);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
        
        /****************************************************
         *  微信设置OAUTH跳转URL，返回字符串信息 - SCOPE = snsapi_base //验证时不返回确认页面，只能获取OPENID
         ****************************************************/
        
        public function wxOauthBase($redirectUrl,$state = "",$appId = NULL){
        	$appId          = is_null($appId) ? self::appId : $appId;
        	$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appId."&redirect_uri=".$redirectUrl."&response_type=code&scope=snsapi_base&state=".$state."#wechat_redirect";
        	return $url;
        }
        
        /****************************************************
         *  微信设置OAUTH跳转URL，返回字符串信息 - SCOPE = snsapi_userinfo //获取用户完整信息
         ****************************************************/
        
        public function wxOauthUserinfo($redirectUrl,$state = "",$appId = NULL){
        	$appId          = is_null($appId) ? self::appId : $appId;
        	$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appId."&redirect_uri=".$redirectUrl."&response_type=code&scope=snsapi_userinfo&state=".$state."#wechat_redirect";
        	return $url;
        }
        
        /****************************************************
         *  微信OAUTH跳转指定URL
         ****************************************************/
        
        public function wxHeader($url){
        	header("location:".$url);
        }
        
        /****************************************************
         *  微信通过OAUTH返回页面中获取AT信息
         ****************************************************/
        
        public function wxOauthAccessToken($code,$appId = NULL , $appSecret = NULL){
        	$appId          = is_null($appId) ? self::appId : $appId;
        	$appSecret      = is_null($appSecret) ? self::appSecret : $appSecret;
        	$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appId."&secret=".$appSecret."&code=".$code."&grant_type=authorization_code";
        	$result         = $this->wxHttpsRequest($url);
        	//print_r($result);
        	$jsoninfo       = json_decode($result, true);
        	//$access_token     = $jsoninfo["access_token"];
        	return $jsoninfo;
        }
        
        /****************************************************
         *  微信通过OAUTH的Access_Token的信息获取当前用户信息 // 只执行在snsapi_userinfo模式运行
         ****************************************************/
        
        public function wxOauthUser($OauthAT,$openId){
        	$url            = "https://api.weixin.qq.com/sns/userinfo?access_token=".$OauthAT."&openid=".$openId."&lang=zh_CN";
        	$result         = $this->wxHttpsRequest($url);
        	$jsoninfo       = json_decode($result, true);
        	return $jsoninfo;
        }
        
}