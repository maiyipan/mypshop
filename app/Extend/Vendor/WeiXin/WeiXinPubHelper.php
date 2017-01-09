<?php
/**
 * wechat base api
 * */

include_once ("WeiXin.pub.config.php");
include_once ("commonUtil.php");

class GetOpenid extends Common_util_pub {
	var $code; //code码，用以获取openid
	var $openid; //用户的openid
	var $curl_timeout; //curl超时时间
	

	var $appid;
	var $appsecret;
	
	var $access_token;
	var $lang = 'zh_CN';
	function __construct() {
	
	}
	
	/**
	 * 作用：生成可以获得code的url
	 * 默认授权
	 */
	function createOauthUrlForCode($redirectUrl) {
		$urlObj ["appid"] = $this->appid; //WxPayConf_pub::APPID;
		$urlObj ["redirect_uri"] = urlencode("$redirectUrl");
		$urlObj ["response_type"] = "code";
		$urlObj ["scope"] = "snsapi_base";
		$urlObj ["state"] = "STATE" . "#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap ( $urlObj, false );
		return WeiXinConf_pub::CODE_URL . $bizString;
	
		//return 'https://open.weixin.qq.com/connect/oauth2/authorize?' . $bizString;
	}
	
	
	/**
	 * 作用：生成可以获得code的url
	 * 默认授权
	 */
	function createOauthUrlUserInfoForCode($redirectUrl) {
		$urlObj ["appid"] = $this->appid; //WxPayConf_pub::APPID;
		$urlObj ["redirect_uri"] = urlencode("$redirectUrl");
		$urlObj ["response_type"] = "code";
		$urlObj ["scope"] = "snsapi_userinfo";
		$urlObj ["state"] = "STATE" . "#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap ( $urlObj, false );
		return WeiXinConf_pub::CODE_URL . $bizString;
	
		//return 'https://open.weixin.qq.com/connect/oauth2/authorize?' . $bizString;
	}
	
	
	/**
	 * 作用：生成可以获得openid的url
	 */
	function createOauthUrlForOpenid() {
		$urlObj ["appid"] = $this->appid; //WxPayConf_pub::APPID;
		$urlObj ["secret"] = $this->appsecret; //WxPayConf_pub::APPSECRET;
		$urlObj ["code"] = $this->code;
		$urlObj ["grant_type"] = "authorization_code";
		$bizString = $this->formatBizQueryParaMap ( $urlObj, false );
		return WeiXinConf_pub::OPENID_URL . $bizString;
	}
	
	/**
	 * 作用：通过curl向微信提交code，以获取openid
	 */
	function getOpenid() {
		$url = $this->createOauthUrlForOpenid ();
		//初始化curl
		$ch = curl_init ();
		//设置超时
		curl_setopt ( $ch, CURLOP_TIMEOUT, $this->curl_timeout );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		//运行curl，结果以jason形式返回
		$res = curl_exec ( $ch );
		curl_close ( $ch );
		//取出openid
		$data = json_decode ( $res, true );
		//$this->openid = $data ['openid'];
		return $data;
	}
	
	/**
	 * 作用：通过access_token 和 openid 获取用户基础信息
	 */
	function getUserInfo() {
		$url = $this->createOauthUrlForInfo ();
		//echo  $url;
		$ch = curl_init ();
		//设置超时
		curl_setopt ( $ch, CURLOP_TIMEOUT, $this->curl_timeout );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		//运行curl，结果以jason形式返回
		$res = curl_exec ( $ch );
		curl_close ( $ch );
		//取出openid
		$data = json_decode ( $res, true );
		//$this->openid = $data ['openid'];
		return $data;
	}
	
	/**
	 * 作用：创建获取用户信息的url  https://api.weixin.qq.com/sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
	 */
	function createOauthUrlForInfo() {
		$urlObj ["access_token"] = $this->access_token; //WxPayConf_pub::APPID;
		$urlObj ["openid"] = $this->openid; //WxPayConf_pub::APPSECRET;
		$urlObj ["lang"] = $this->lang;
		$bizString = $this->formatBizQueryParaMap ( $urlObj, false );
		return WeiXinConf_pub::USERINFO_URL . $bizString;
	}
	
	/**
	 * 作用：设置code
	 */
	function setCode($code_) {
		$this->code = $code_;
	}
	
	/**
	 * 
	 * set appid
	 * */
	function setAppid($appid) {
		$this->appid = $appid;
	}
	
	/**
	 * set appsecret
	 * */
	function setAppsecret($appsecret) {
		$this->appsecret = $appsecret;
	}
	
	/**
	 * set openid
	 * */
	function setOpenid($openid) {
		$this->openid = $openid;
	}
	
	/**
	 * set appsecret
	 * */
	function setAccess_token($access_token) {
		$this->access_token = $access_token;
	}

}


class WeixinApi extends Common_util_pub {
	
	function __construct($uniAccount) {
		 parent::__construct($uniAccount);
	}
	
	/**
	 * 
	 * 推送文本消息
	 * */
	public function sendText($data, $access_token){
		$dat = $this->decodeUnicode ( json_encode ( $data ) );
		$dat = trim ( $dat, '[]' );
		$token = $access_token;//$this->get_access_token ();
		$url = WeiXinConf_pub::SEND_CUSTOM_MESSAGE. $token;
		$result = $this->http_post_result ( $url, $dat );
		$result['token'] = $token;
		return $result;		
	}
	
	/**
	 * 
	 * 推送模板消息
	 * */
	public function sendTemplate($data, $access_token){
		$dat = $this->decodeUnicode ( json_encode ( $data ) );
		$dat = trim ( $dat, '[]' );
		$token = $access_token;//$this->get_access_token ();
		$url = WeiXinConf_pub::SEND_TEMPLATE. $token;
		$result = $this->http_post_result ( $url, $dat );
		$result['token'] = $token;
		return $result;
	}
	
	
	public function baseUserInfo($accessToken, $openid){
		
		$urlObj ["access_token"] = $accessToken; //WxPayConf_pub::APPID;
		$urlObj ["openid"] = $openid; //WxPayConf_pub::APPSECRET;
		$urlObj ["lang"] = 'zh_CN';
		$bizString = $this->formatBizQueryParaMap ( $urlObj, false );
		$url =  WeiXinConf_pub::BASE_USERINO . $bizString;
		$ch = curl_init ();
		//设置超时
		curl_setopt ( $ch, CURLOP_TIMEOUT, 30 );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		//运行curl，结果以jason形式返回
		$res = curl_exec ( $ch );
		curl_close ( $ch );
		//取出openid
		$data = json_decode ( $res, true );
		//$this->openid = $data ['openid'];
		return $data;
		
	}
	
	
	public function publicMenu($data, $access_token){
		//$dat = $this->decodeUnicode ( json_encode ( $data ) );
		$dat = trim ( $data, '[]' );
		$token = $access_token;//$this->get_access_token ();
		$url = WeiXinConf_pub::PUBLIC_MENU. $token;
		$result = $this->http_post_result ( $url, $dat );
		$result['token'] = $token;
		return $result;
	}
	
	
}