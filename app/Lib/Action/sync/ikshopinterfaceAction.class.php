<?php
class ikshopinterfaceAction extends baseAction {
	
	/**
	 * 微信授权
	 * */
	public function weixinAuthorize() {
		
		
		Vendor ( 'WeiXin.WeiXinPubHelper' );
		$getOpenid = new \GetOpenid (); 
		$uid = '01233a79c297477d554051b1bb3650';
		$appid = C ( 'pin_appid_'. $uid );
		$appsecret = C ( 'pin_appsecret_' . $uid );
		
		dump($appid);
		$getOpenid->setAppid ( $appid );
		$getOpenid->setAppsecret ( $appsecret );
		
		if (!isset ( $_GET ['code'] )) {
			
			//获取回调url
			$returnUrl = $_GET['returnUrl'];
			//授权方式
			$scope = $_GET['scope'];
			$sessionid  = session_id();
			
			dump($returnUrl);
			dump($sessionid);
			
			S("$sessionid", $returnUrl, 3600);
			//触发微信返回code码
			$callback_url = "http://jx.i-lz.cn/index.php/sync/ikshopinterface/weixinAuthorize/sessionid/" . $sessionid;
			$url = $getOpenid->createOauthUrlUserInfoForCode ( $callback_url );
			//Log::write('oauth url:' .  $url, 'DEBUG');
			Header("Location: $url");
			exit();
		} else {
			//获取code码，以获取openid
			$code = $_GET ['code'];
			$sessionid  = $_GET['sessionid'];
			$returnUrl = S("$sessionid");
			
			dump($returnUrl);
			
			$getOpenid->setCode ( $code );
			$data = $getOpenid->getOpenId ();
			$openid = $data ['openid'];
			//登录时缓存 网页授权的accesstoken
			$_SESSION['web_access_token'] = $data;
			$access_token = $data['access_token'];
			
			//获取用户信息
			$getOpenid->setOpenid($openid);
			$getOpenid->setAccess_token($access_token);
			$userInfo = $getOpenid->getUserInfo();
			dump($userInfo);
			
			
			$openid = $userInfo['openid'];
			$nickname = $userInfo['nickname'];
			$sex = $userInfo['sex'];
			$province = $userInfo['province'];
			$city = $userInfo['city'];
			$country = $userInfo['country'];
			$headimgurl = $userInfo['headimgurl'];
			
			$digest =  md5('ikshopapi'.$openid.date('Y-m-d'));
			
			$returnUrl  = $returnUrl .'?code=ok'. '&openid='. $openid . '&nickname=' . $nickname . '&sex=' . $sex . '&province='. $province 
				. '&city='.$city . '&country='.$country . '&headimgurl=' . $headimgurl . '&digest=' . $digest ;
			
			//Log::write($returnUrl);
			dump($returnUrl);
			//exit();
			Header("Location: $returnUrl");
			
		}
	}
	
	public function testAuth() {
		//http://127.0.0.1/index.php/sync/ikshopinterface/weixinAuthorize/returnUrl/http%3A%2F%2F127.0.0.1%2Findex.php%2Fsync%2Fikshopinterface%2FtestAuth/scope/snsapi_userinfo
		//http://127.0.0.1/index.php/sync/ikshopinterface/testAuth
		if (!isset($_GET['openid'])) {
			
			$returnUrl = urlencode('http://jx.i-lz.cn/index.php/sync/ikshopinterface/testAuth');
			$scope =  'snsapi_userinfo';
			$url = 'http://jx.i-lz.cn/index.php/sync/ikshopinterface/weixinAuthorize?returnUrl='. $returnUrl . '&scope='. $scope;
			//$url = 'http://jx.i-lz.cn/index.php/sync/ikshopinterface/weixinAuthorize/returnUrl/'. $returnUrl . '/scope/'. $scope;
			Header("Location: $url");
			eixt();
		} else {
			dump("success");
			dump($_GET['openid']);			
		}
	}
	
}