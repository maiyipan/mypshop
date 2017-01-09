<?php

/**
 * 微信接口
 * */
class WeixinInterface {
	
	
	private $uid;
	private $appid;
	private $appsecret;
	
	function __construct($uid) {
		//dd360e4b42b8beb8e72069adb2fcde
		$this->uid = $uid;
 		$this->appid =C ( 'spconf_appid_'. $this->uid);
		$this->appsecret = C('spconf_appsecret_'.$this->uid);
	
	}
	
	
	public function  test () {
		dump($this->appid);
		
	}
	
	/**
	 * 发送消息接口
	 * @access public
     * @param string $data  发送的文本
     * @return mixed
	 * */
	public function sendMsg($data) {
		Vendor ( 'WeiXin.WeiXinPubHelper' );
		$uniAccount ['AppId'] = $this->appid;
		$uniAccount ['AppSecret'] = $this->appsecret;
		$weixinApi = new \WeixinApi ( $uniAccount );
		$access_token = $this->findAccess_token ();
		return $weixinApi->sendText ( $data, $access_token );
	}
	
	
	/**
	 * 发布菜单
	 * 
	 * */
	function publicMenu($data) {
		Vendor ( 'WeiXin.WeiXinPubHelper' );
		$uniAccount ['AppId'] = $this->appid;
		$uniAccount ['AppSecret'] = $this->appsecret;
		$weixinApi = new \WeixinApi ( $uniAccount );
		$access_token = findAccess_token ( $accountid );
		return $weixinApi->publicMenu ( $data, $access_token );
	}
	
	/**
	 * 模板推送
	 * */
	function sendTemplate($accountid, $data) {
		Vendor ( 'WeiXin.WeiXinPubHelper' );
	
		$uniAccount ['AppId'] = $this->appid;
		$uniAccount ['AppSecret'] = $this->appsecret;
		$weixinApi = new \WeixinApi ( $uniAccount );
		$access_token = $this->findAccess_token ();
		return $weixinApi->sendTemplate ( $data, $access_token );
	}
	function addAccess_token($data) {
		$access_token = M ( 'access_token' );
		$access_token->data ( $data )->add ();
	}
	function updateAccess_token($data) {
		$access_token = M ( 'access_token' );
		$access_token->data ( $data )->where ( "accountid = '" . $data ['accountid'] . "'" )->save ();
	}
	/**
	 * 查找access_token
	 * 先从数据库查询，如果过期则重新获取之后更新，如果第一次获取则从微信获取之后保存
	 * */
	function findAccess_token() {
		Log::write('findAccess--'.$this->uid);
		$access_token = D( 'access_token' );
// 		$data = $access_token->queryAccess_token($this->uid);//原来
		$data = $access_token->where("accountid='".$this->uid."'")->find() ;

		if ($data) {
			Log::write('findAccess--token--if');
			if (! empty ( $data ['access_token'] ) && ! empty ( $data ['expires_in'] ) && $data ['expires_in'] > time ()) {
				return $data ['access_token'];
			} else { //过期
				$result = $this->getAccess_token();
				$data ['access_token'] = $result ['access_token'];
				//更新
// 				$access_token->updateAccess_token ( $result );
				updateAccess_token($result);
			}
		} else {
			Log::write('findAccess--token--else');//查询无果添加
			
			$result = $this->getAccess_token();
			$result ['accountid'] = $accountid;
			$data ['access_token'] = $result ['access_token'];
			$data['accountid'] = $this->uid;
// 			$access_token->addAccess_token ( $result );
			addAccess_token($result);
		}
		return $data ['access_token'];
	}
	
	
	/**
	 * 从微信获取access_token
	 * */
	public function getAccess_token() {
		Vendor ( 'WeiXin.WeiXinPubHelper' );
		$uniAccount ['AppId'] = $this->appid;
		$uniAccount ['AppSecret'] = $this->appsecret;
		Log::write('appid--'.$this->appid);
		Log::write('appsecret--'.$this->appsecret);
		$weixinApi = new \WeixinApi ( $uniAccount );
		Log::write('weixinApi--'.$weixinApi);
		$result = $weixinApi->get_access_token ( $uniAccount );
		//$result ['accountid'] = $accountid;
		return $result;
	}
	
	
	/**
	 * 根据openid 从微信服务获取用户基本信息 
	 * @param $openid 用户openid
	 * */
	function userInfo($openid, $uid){
		Log::write('userInfo--'.$this->appid);
		Vendor ( 'WeiXin.WeiXinPubHelper' );
		$uniAccount ['AppId'] =  $this->appid;//; C ( 'spconf_appid_'. $uid);
		$uniAccount ['AppSecret'] = $this->appsecret;//; C ( 'spconf_appsecret_'. $uid);
		$weixinApi = new \WeixinApi ( $uniAccount );
		$access_token = $this->findAccess_token ( $uid );
		return $weixinApi->baseUserInfo ( $access_token, $openid);
	}
	
	
	/**
	 * 获取getJsApiTicket
	 * */
	function getJsApiTicket($accountid) {
		$jsapiticket = D('jsapiticket');
		$data =$jsapiticket->queryJsapiticket($accountid);
		if ($data) {
			if ( $data['expires_in'] < time()) {   //过期
				// 如果是企业号用以下 URL 获取 ticket
				// $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
				$ticket = $this->getticketFroWei();
				if ($ticket) {
					$data['expires_in'] = time() + 7000;
					$data['ticket'] = $ticket;
					$jsapiticket->updateAccess_token($data);
				}
			} else {
				$ticket = $data['ticket'];
			}
		} else {
			$ticket = $this->getticketFroWei($accessToken);
			if ($ticket) {
				$data['expires_in'] = time() + 7000;
				$data['ticket'] = $ticket;
				$data['accountid'] = $accountid;
				$jsapiticket->addjsapiticket($data);
			}
		}
		return $ticket;
	}
	
	/**
	 * 微信获取ticket
	 * */
	function getticketFroWei($accountid) {
		$accessToken = $this->findAccess_token($accountid);
		Vendor ( 'WeiXin.jssdk' );
		$ticket;
		$jssdk = new JSSDK($this->appid, $this->appsecret, $ticket, $accessToken);
		$ticket = $jssdk->getTicket();
		return $ticket;
	}
	
}