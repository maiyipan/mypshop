<?php
function addslashes_deep($value) {
	$value = is_array ( $value ) ? array_map ( 'addslashes_deep', $value ) : addslashes ( $value );
	return $value;
}
function stripslashes_deep($value) {
	if (is_array ( $value )) {
		$value = array_map ( 'stripslashes_deep', $value );
	} elseif (is_object ( $value )) {
		$vars = get_object_vars ( $value );
		foreach ( $vars as $key => $data ) {
			$value->{$key} = stripslashes_deep ( $data );
		}
	} else {
		$value = stripslashes ( $value );
	}
	
	return $value;
}
function todaytime() {
	return mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ), date ( 'Y' ) );
}

function random($len=6,$type='mix'){
	$len = intval($len);
	if($len >32) $len = 32;
	$str  = '';
	$attr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','g','m','p','t','z','c');
	shuffle($attr);
	$int_attr = array('0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9','5','9');
	shuffle($int_attr);
	$attr     = implode($attr);
	$int_attr = implode($int_attr);
	switch($type)
	{
		case 'int': $str= $int_attr;break;
		case 'char': $str = $attr;break;
		default: $str = md5(uniqid(mt_rand(), true)); break;
	}
	return substr($str,0,$len);
}

/**
 * 友好时间
 */
function fdate($time) {
	if (! $time)
		return false;
	$fdate = '';
	$d = time () - intval ( $time );
	$ld = $time - mktime ( 0, 0, 0, 0, 0, date ( 'Y' ) ); // 年
	$md = $time - mktime ( 0, 0, 0, date ( 'm' ), 0, date ( 'Y' ) ); // 月
	$byd = $time - mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ) - 2, date ( 'Y' ) ); // 前天
	$yd = $time - mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ) - 1, date ( 'Y' ) ); // 昨天
	$dd = $time - mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ), date ( 'Y' ) ); // 今天
	$td = $time - mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ) + 1, date ( 'Y' ) ); // 明天
	$atd = $time - mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ) + 2, date ( 'Y' ) ); // 后天
	if ($d == 0) {
		$fdate = '刚刚';
	} else {
		switch ($d) {
			case $d < $atd :
				$fdate = date ( 'Y年m月d日', $time );
				break;
			case $d < $td :
				$fdate = '后天' . date ( 'H:i', $time );
				break;
			case $d < 0 :
				$fdate = '明天' . date ( 'H:i', $time );
				break;
			case $d < 60 :
				$fdate = $d . '秒前';
				break;
			case $d < 3600 :
				$fdate = floor ( $d / 60 ) . '分钟前';
				break;
			case $d < $dd :
				$fdate = floor ( $d / 3600 ) . '小时前';
				break;
			case $d < $yd :
				$fdate = '昨天' . date ( 'H:i', $time );
				break;
			case $d < $byd :
				$fdate = '前天' . date ( 'H:i', $time );
				break;
			case $d < $md :
				$fdate = date ( 'm月d H:i', $time );
				break;
			case $d < $ld :
				$fdate = date ( 'm月d', $time );
				break;
			default :
				$fdate = date ( 'Y年m月d日', $time );
				break;
		}
	}
	return $fdate;
}

/**
 * 获取用户头像
 */
function avatar($uid, $size) {
	$avatar_size = explode ( ',', C ( 'pin_avatar_size' ) );
	$size = in_array ( $size, $avatar_size ) ? $size : '100';
	$avatar_dir = avatar_dir ( $uid );
	$avatar_file = $avatar_dir . md5 ( $uid ) . "_{$size}.jpg";
	if (! is_file ( C ( 'pin_attach_path' ) . 'avatar/' . $avatar_file )) {
		$avatar_file = "default_{$size}.jpg";
	}
	return __ROOT__ . '/' . C ( 'pin_attach_path' ) . 'avatar/' . $avatar_file;
}
function avatar_dir($uid) {
	$uid = abs ( intval ( $uid ) );
	$suid = sprintf ( "%09d", $uid );
	$dir1 = substr ( $suid, 0, 3 );
	$dir2 = substr ( $suid, 3, 2 );
	$dir3 = substr ( $suid, 5, 2 );
	return $dir1 . '/' . $dir2 . '/' . $dir3 . '/';
}
function attach($attach, $type) {
	if (false === strpos ( $attach, 'http://' )) {
		
		// 本地附件
		return __ROOT__ . '/' . C ( 'pin_attach_path' ) . $type . '/' . $attach;
		// 远程附件
		// todo...
	} else {
		// URL链接
		return $attach;
	}
}
function attach2($attach, $type) {
	if (false === strpos ( $attach, 'http://' )) {
		$attach = str_replace('_thumb.','.', $attach);
		return __ROOT__ . '/' . C ( 'pin_attach_path' ) . $type . '/' . $attach;
	} else {
		// URL链接
		return $attach;
	}
}

/*
 * 获取缩略图
 */
function get_thumb($img, $suffix = '_thumb') {
	if (false === strpos ( $img, 'http://' )) {
		$ext = array_pop ( explode ( '.', $img ) );
		$thumb = str_replace ( '.' . $ext, $suffix . '.' . $ext, $img );
	} else {
		if (false !== strpos ( $img, 'taobaocdn.com' ) || false !== strpos ( $img, 'taobao.com' )) {
			// 淘宝图片 _s _m _b
			switch ($suffix) {
				case '_s' :
					$thumb = $img . '_100x100.jpg';
					break;
				case '_m' :
					$thumb = $img . '_210x1000.jpg';
					break;
				case '_b' :
					$thumb = $img . '_480x480.jpg';
					break;
			}
		}
	}
	return $thumb;
}

/**
 * 对象转换成数组
 */
function object_to_array($obj) {
	$_arr = is_object ( $obj ) ? get_object_vars ( $obj ) : $obj;
	foreach ( $_arr as $key => $val ) {
		$val = (is_array ( $val ) || is_object ( $val )) ? object_to_array ( $val ) : $val;
		$arr [$key] = $val;
	}
	return $arr;
}
function arrayToXml($arr) {
	$xml = "<xml>";
	foreach ( $arr as $key => $val ) {
		// exit($key . $val);
		if (is_numeric ( $val )) {
			$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
		} else {
			$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
		}
	}
	$xml .= "</xml>";
	// exit("---- ".$xml." ----");
	return $xml;
}
function xmlToArray($xml) {
	// 将XML转为array
	$array_data = json_decode ( json_encode ( simplexml_load_string ( $xml, 'SimpleXMLElement', LIBXML_NOCDATA ) ), true );
	return $array_data;
}
function sendMsg($accountid, $data) {
	Vendor ( 'WeiXin.WeiXinPubHelper' );
	$appid = C ( 'pin_appid' ); //'';
	$appsecret = C('pin_appsecret');//'';
	
	$uniAccount ['AppId'] = $appid;
	$uniAccount ['AppSecret'] = $appsecret;
	$weixinApi = new \WeixinApi ( $uniAccount );
	$access_token = findAccess_token ( $accountid );
	return $weixinApi->sendText ( $data, $access_token );
}

function publicMenu($accountid, $data) {
	Vendor ( 'WeiXin.WeiXinPubHelper' );
	$appid = C ( 'spconf_appid_'. $accountid); //''; 
	$appsecret =  C ( 'spconf_appsecret_'. $accountid); //'';
	$uniAccount ['AppId'] = $appid;
	$uniAccount ['AppSecret'] = $appsecret;
	$weixinApi = new \WeixinApi ( $uniAccount );
	//需要修改
	$access_token = findAccess_token ( $accountid );
	return $weixinApi->publicMenu ( $data, $access_token );
}

function addAccess_token($data) {
	$access_token = M ( 'access_token' );
	$access_token->data ( $data )->add ();
}
function updateAccess_token($data) {
	$access_token = M ( 'access_token' );
	$access_token->data ( $data )->where ( "accountid = '" . $data ['accountid'] . "'" )->save ();
}
function findAccess_token($accountid) {
	Log::write('accountid--' . $accountid);
	Vendor ( 'WeiXin.WeiXinPubHelper' );
	$access_token = M ( 'access_token' );
	$data = $access_token->where ( "accountid = '" . $accountid . "'" )->find ();
	dump($data ['expires_in'] > time ());
	if ($data) {
		if (! empty ( $data ['access_token'] ) && ! empty ( $data ['expires_in'] ) && $data ['expires_in'] > time ()) {
			return $data ['access_token'];
		} else {
			$appid = C ( 'spconf_appid_'. $accountid); //''; 
			$appsecret =  C ( 'spconf_appsecret_'. $accountid); //'';
			$uniAccount ['AppId'] = $appid;
			$uniAccount ['AppSecret'] = $appsecret;
			$weixinApi = new \WeixinApi ( $uniAccount );
			dump($uniAccount);
			$result = $weixinApi->get_access_token ( $uniAccount );
			dump($result);
			$result ['accountid'] = $accountid;
			$data ['access_token'] = $result ['access_token'];
			//dump($data);
			updateAccess_token ( $result );
		}
	} else {
		//修改过
		$appid = C ( 'spconf_appid_'. $accountid); //''; 
		$appsecret =  C ( 'spconf_appsecret_'. $accountid); //'';
		/* $array=M('shop')->where(array('uid'=>$uid))->find();
		$appid=$array['appid'];
		$appsecret=$array['appsecret']; */
		
		$uniAccount ['AppId'] = $appid;
		$uniAccount ['AppSecret'] = $appsecret;
		$weixinApi = new \WeixinApi ( $uniAccount );
		$result = $weixinApi->get_access_token ( $uniAccount );
		$result ['accountid'] = $accountid;
		
		$data ['access_token'] = $result ['access_token'];
		addAccess_token ( $result );
		
	}
	return $data ['access_token'];
}
function sendTemplate($accountid, $data) {
	Vendor ( 'WeiXin.WeiXinPubHelper' );
	$appid =  C ( 'pin_appid' ); //''; //
	$appsecret = C('pin_appsecret'); //''; // 
	
	$uniAccount ['AppId'] = $appid;
	$uniAccount ['AppSecret'] = $appsecret;
	$weixinApi = new \WeixinApi ( $uniAccount );
	$access_token = findAccess_token ( $accountid );
	return $weixinApi->sendTemplate ( $data, $access_token );
}

/**
 * 获取code
 */
function getCode($url) {
	Vendor ( 'WeiXin.WxPayPubHelper' );
	$jsApi = new \JsApi_pub ();
	
	$appid = C ( 'pin_appid' );
	$appsecret = C('pin_appsecret');
	$jsApi->setAppid($appid);
	$jsApi->setAppsecret($appsecret);
	//$url = $jsApi->createOauthUrlForCode ( \WxPayConf_pub::JS_API_CALL_URL . '?body='.$body .'&out_trade_no='.$out_trade_no . '&total_fee='. $total_fee);
	Header ( "Location: $url" );
	exit;
}

function getOpenidAccessToken($code){
	Vendor ( 'WeiXin.WxPayPubHelper' );
	$jsApi = new \JsApi_pub ();
	$appid = C ( 'pin_appid' );
	$appsecret = C ( 'pin_appsecret' );
	$jsApi->setAppid ( $appid );
	$jsApi->setAppsecret ( $appsecret );
	
	// 获取code码，以获取openid
	$code = $_GET ['code'];
	$jsApi->setCode ( $code );
	$data = $jsApi->getOpenidAccessToken ();
}



/**
 * 获取用户信息(这里添加了$uid)
 * */
function userInfo($openid,$uid){
	

	Log::write('openid--' . $openid);
	Vendor ( 'WeiXin.WeiXinPubHelper' );
	$array=M('shop')->where(array('uid'=>$uid))->find();
	$appid=$array['appid'];
	$appsecret=$array['appsecret'];
	$accountid=$uid;
// 	$accountid = C('ping_uid');
// 	$appid = C ( 'ping_appid' );//''; // 
// 	$appsecret = C('ping_appsecret'); //''; //
	
	Log::write('appid--' . $appid);
	Log::write('appsecret--' . $appsecret);
	
	$uniAccount ['AppId'] = $appid;
	$uniAccount ['AppSecret'] = $appsecret;
	
	Log::write('uniAccount--' . $uniAccount);
// 	echo $uniAccount;
	
	$weixinApi = new \WeixinApi ( $uniAccount );
	
	Log::write('weixinApi--' . $weixinApi);
	//需要修改
// 	$accountid = 'test';
	$access_token = findAccess_token ( $accountid );
	
	Log::write('access_token--' . $access_token);
	return $weixinApi->baseUserInfo ( $access_token, $openid);
	
}

/**
 * 短信发送
 * */
function sendDuanXin($phone,$code,$ext=array('stime'=>'','rrid'=>'') ){
	Vendor ( 'DuanXin.duanxin' );
	$duanxin =  new \duanxin();
	$duanxin->sendSMSCode($phone, $code);
	
}
function smsSend($phone,$corpid,$content){
// 	dump("--接口--");
	
	vendor('DuanXin.test_send');
	$test_send = new \test_send();
	$data=$test_send->sendSMS($phone,$corpid,$content);
	Log::write('data--' . $data );
// 	dump('data>>'.$data);
	return  $data;
}



/**
 * 获取getJsApiTicket
 * */
	// jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
 function getJsApiTicket($accountid) {
	//$data = json_decode(file_get_contents("jsapi_ticket.json"));
	
	$data = M('jsapiticket')->where ( "accountid = '" . $accountid . "'" )->find ();
	if ($data) {
		if ( $data['expires_in'] < time()) {
			$accessToken = findAccess_token($accountid);
			// 如果是企业号用以下 URL 获取 ticket
			// $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
			$ticket = getticketFroWei($accessToken);
			if ($ticket) {
				$data['expires_in'] = time() + 7000;
				$data['ticket'] = $ticket;
				M('jsapiticket')->data($data)->save();
			}
		} else {
			$ticket = $data['ticket'];
		}
	} else {
		$accessToken = findAccess_token($accountid);
		
		//dump($accessToken);
		$ticket = getticketFroWei($accessToken);
		if ($ticket) {
			$data['expires_in'] = time() + 7000;
			$data['ticket'] = $ticket;
			//$fp = fopen("jsapi_ticket.json", "w");
			//fwrite($fp, json_encode($data));
			//fclose($fp);
			$data['accountid'] = $accountid;
			M('jsapiticket')->data($data)->add();
		}
	}
	
	return $ticket;
}

function getticketFroWei($accessToken) {
	// 如果是企业号用以下 URL 获取 ticket
	// $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
	$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
	$res = json_decode(httpGet($url));
	return  $res->ticket;
}

function httpGet($url) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 500);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_URL, $url);

	$res = curl_exec($curl);
	curl_close($curl);

	return $res;
}