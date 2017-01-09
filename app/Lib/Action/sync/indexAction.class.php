<?php
class indexAction extends Action {
	public function index() {
		// 1 获取token
		$url  = 'http://121.201.8.142:8083/datahub/access_token?appid=test&secret=abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';
		$util = new  Common_util_pub_test();
		Log::write('getToken>>>');
		$data = $util->get($url);  //json_decode
		$respObject = json_decode($data ,true);
		$token = $respObject['access_token'];
		Log::write('getToken>>>end>>>' . $token);
		
		$cmd = 'http://121.201.8.142:8083/datahub/cmd/1010?appid=test&ACCESS_TOKEN=' . $token;
		
		$time =time() ;
		$sinparm = array('pamtest' ,"$time");
		$sign = $util->getsign($sinparm);
		$param = array(
				'_param0' =>3,
				'_param1' => '9904088'
		);
		$params = array (
				'timestamp' =>"$time",
				'params' => $param,
				'sign' => $sign
		);
		$headers = array ('Content-Type' => 'application/x-www-form-urlencoded' );
		$params = json_encode($params);
		Log::write('getInfo>>begin>>' .$params .'>>' );
		$res = $util->http_request ( $cmd, $params );
		Log::write('getInfo>>end>');
		var_dump($res);
		
		// 请求接口
		/*Vendor ( 'FrdifService.CustomService' );
		$client = new \CustomService ();
		
		$client -> setPosturl(C('MemberSystemUrl'));
		$client -> setUserid(C('MemberSystemUserid'));
		$client -> setPassword(C('MemberSystemPassword'));
		$client->  setCmdid ( '1000' );
		$queryType = 2;
		$queryParam = 18613022123;
		Log::write ( 'par>>' . $queryType . ',' . $queryParam );
		$client->  setQueryParam ( $queryType . ',' . $queryParam );
		Log::write ( 'begin get data form webservice....', 'info' );
		$result = $client->processdata ();
		Log::write ( 'the result:' . $result, 'info' );
		var_dump($result);*/
		
	}

	public function pay() {
			// 1 获取token
			$url  = 'http://121.201.8.142:8083/datahub/access_token?appid=test&secret=abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';
			$util = new  Common_util_pub_test();
			Log::write('getToken>>>');
			$data = $util->get($url);  //json_decode
			$respObject = json_decode($data ,true);
			$token = $respObject['access_token'];
			Log::write('getToken>>>end>>>' . $token);

			$cmd = 'http://121.201.8.142:8083/datahub/cmd/1011?appid=test&ACCESS_TOKEN=' . $token;

			$time =time() ;
			$sinparm = array('pamtest' ,"$time");
			$sign = $util->getsign($sinparm);


			$dingdanhao = date ( "Y-m-dH-i-s" );
			$dingdanhao = str_replace ( "-", "", $dingdanhao );
			$dingdanhao .= rand ( 1000, 2000 );


			/*$param = array(
                    '_param0' =>2,
                    '_param1' => '18613022123'
            );*/
			$card_num = '9904088';
			$public_store = '001';
			$actual = '-50';
			$param = array(
				'_param0' =>$dingdanhao,
				'_param1' => '9904088',
				'_param2' => '0011',
				'_param3' => '-100.04'
			);
			$params = array (
				'timestamp' =>"$time",
				'params' => $param,
				'sign' => $sign
			);
			$headers = array ('Content-Type' => 'application/x-www-form-urlencoded' );
			$params = json_encode($params);
			Log::write('getInfo>>begin>>' .$params .'>>' );
			$res = $util->http_request ( $cmd, $params );
			Log::write('getInfo>>end>');
			var_dump($res);
	}
	public function testOracle() {
		
		// $User = M('test','','DB_CONFIG1');
		// 执行具体的数据操作
		// $User->getInfo();
		$User = M ( 'list', 'USER_', 'DB_CONFIG1' );
		$result = $User->select ();
		foreach ( $result as $info ) {
			echo $info ['USERID'] . '--' . $info ['USERNAME'];
			echo '</br>';
		}
		// echo $result;
	}
	public function syncUser() {
		ini_set ( "soap.wsdl_cache_enabled", "0" ); // soap缓存
		$soap = new SoapClient ( 'http://121.201.8.142:7001/frdif/n_frdif.asmx?WSDL' );
		
		/*
		 * echo ("SOAP服务器提供的开放函数:");
		 * echo ('<pre>');
		 * var_dump ( $soap->__getFunctions () );//获取服务器上提供的方法
		 * echo ('</pre>');
		 * echo ("SOAP服务器提供的Type:");
		 * echo ('<pre>');
		 * var_dump ( $soap->__getTypes () );//获取服务器上数据类型
		 * echo ('</pre>');
		 */
		echo ("执行GetGUIDNode的结果:");
		echo '</br>';
		try {
			// $header = new SoapHeader('NAMESPACE' ,'MySoapHeader', "");
			
			// $soap->__setSoapHeaders(array($header));//添加soapheader
			
			$param2 = array (
					'userid' => 'test',
					'password' => '00B54E5ADD61AC9C',
					'cmdid' => '1010',
					'inputpara' => '2,18613022123',
					'rtn' => '1' 
			);
			$data = object_to_array ( $soap->processdata ( $param2 ) ); // 直接使用方法名调用
			if ($data ['rtn'] != - 1) {
				print_r ( ($data) );
				echo '</br>';
				$info = explode ( '	', $data ['outputpara'] );
				
				foreach ( $info as $color ) {
					echo '---' . $color;
					echo '</br>';
					/*
					 * $test = explode(' ', $color);
					 * foreach($test as $tt) {
					 * echo $tt;
					 * echo '++++</br>';
					 * }
					 * echo '----</br>';
					 */
				}
			} else {
				print_r ( ($data) );
			}
		} catch ( Exception $e ) {
			print_r ( $e );
		}
	}

	/**
	 * 推送订单消息时需要订单号和订单金额；推荐码用于首单推送；抵扣券单号用于首单使用推荐码时推送
	 * @param unknown $dingdanhao  订单号
	 * @param unknown $price	        订单金额
	 * @param unknown $promo_code  推荐码
	 * @param unknown $random	        抵扣券单号
	 */
	function sendPayTemp($dingdanhao, $price  ){

		$openid = 'ou-gAj64Z360XMK3pD9x3i79Y-AE';
		$nickname =  'dajun';
		//session ($uid . 'openid', $openid);
		$template_id = 'jArkSy_kEbAzPN_lnYVLLLUiG9OMye7KyozbKJf6skc';// M('templ')->field('val')-> where(array('uid'=>$this->shopId,'key'=>1))->find();//获取template_id
		$accountid = 'test1';
		$msg = array ();

		$msg ['touser'] = $openid;
// 		$msg ['template_id'] = 'jArkSy_kEbAzPN_lnYVLLLUiG9OMye7KyozbKJf6skc';
		$msg ['template_id'] = trim($template_id['val']);
		$msg ['topcolor'] = '#FF0000';
		$msg ['url'] = C('pin_baseurl').'/order/checkOrder/orderId/'. $dingdanhao. '/sid/'.$this->shopId;

		$data = array ();
		$user = array ();
		$user ['value'] = $nickname . ',您的订单已创建成功!';
		$user ['color'] = '#173177';

		$ordernum = array (
			'value' =>  $dingdanhao,
			'color' => '#173177'
		);

		$priceArr = array (
			'value' =>  $price,
			'color' => '#173177'
		);

		$ordertime = array (
			'value' => date ( "Y/m/d" ) . ' ' . date ( "h:i:sa" ),
			'color' => '#173177'
		);

		$data ['first'] = $user;
		$data ['orderno'] = $ordernum;
		$data ['amount'] = $priceArr;
		//$data ['ordertime'] = $ordertime;
		$data ['remark'] = '如您还有疑问，请留言';
		$msg ['data'] = $data;

// 		dump($msg);

		if (false === $shopconf = F ( 'shop' )) {
			$conf = D ( 'shop' )->shop_cache ();
		}
		C ( F ( 'shop' ) );//获取缓存

		$shopModel = D('shop');
		$sid = '90baa3315883e80ce926c4034fc811';
		//Log::write ( "sid>>" . $sid );
		$pid =  $shopModel->getUidForP($sid);
		$wxInterface = new WeixinInterface ( $pid );
		$data = $wxInterface->sendTemplate ( $pid, $msg );
// 		sendTemplate ( $accountid, $msg );
	}
}
class Common_util_pub_test {
	function getsign($Parameters) {
		asort ( $Parameters );
		$temp = '';
		foreach ( $Parameters as $value ) {
			Log::write('temp>>>' . $temp);
			$temp = $temp . $value;
			Log::write('temp---->>>' . $temp);
		}
		$String = sha1 ( $temp );
		return $String;
	}
	
	/**
	 * 作用：格式化参数，签名过程需要使用
	 */
	function formatBizQueryParaMap($paraMap, $urlencode) {
		$buff = "";
		ksort ( $paraMap );
		foreach ( $paraMap as $k => $v ) {
			if ($urlencode) {
				$v = urlencode ( $v );
			}
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen ( $buff ) > 0) {
			$reqPar = substr ( $buff, 0, strlen ( $buff ) - 1 );
		}
		return $reqPar;
	}
	function object_array($array) {
		if (is_object ( $array )) {
			$array = ( array ) $array;
		}
		if (is_array ( $array )) {
			foreach ( $array as $key => $value ) {
				$array [$key] = object_array ( $value );
			}
		}
		return $array;
	}
	private function toBizContentJson($biz_content) {
		$content = $this->JSON ( $biz_content );
		return $content;
	}
	protected function JSON($array) {
		$this->arrayRecursive ( $array, 'urlencode', true );
		$json = json_encode ( $array );
		return urldecode ( $json );
	}
	
	/**
	 * ************************************************************
	 *
	 * 使用特定function对数组中所有元素做处理
	 *
	 * @param
	 *        	string &$array 要处理的字符串
	 * @param string $function
	 *        	要执行的函数
	 * @return boolean $apply_to_keys_also 是否也应用到key上
	 * @access public
	 *        
	 *         ***********************************************************
	 */
	protected function arrayRecursive(&$array, $function, $apply_to_keys_also = false) {
		foreach ( $array as $key => $value ) {
			if (is_array ( $value )) {
				$this->arrayRecursive ( $array [$key], $function, $apply_to_keys_also );
			} else {
				$array [$key] = $function ( $value );
			}
			
			if ($apply_to_keys_also && is_string ( $key )) {
				$new_key = $function ( $key );
				if ($new_key != $key) {
					$array [$new_key] = $array [$key];
					unset ( $array [$key] );
				}
			}
		}
	}
	
	/**
	 * 作用：以post方式提交xml到对应的接口url
	 */
	public function postXmlCurl($xml, $url, $second = 30) {
		// 初始化curl
		$ch = curl_init ();
		// 设置超时
		curl_setopt ( $ch, CURLOP_TIMEOUT, $second );
		// 这里设置代理，如果有的话
		// curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
		// curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		// 设置header
		curl_setopt ( $ch, CURLOPT_HEADER, FALSE );
		// 要求结果为字符串且输出到屏幕上
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		// post提交方式
		curl_setopt ( $ch, CURLOPT_POST, TRUE );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $xml );
		// 运行curl
		$data = curl_exec ( $ch );
		curl_close ( $ch );
		// 返回结果
		if ($data) {
			curl_close ( $ch );
			return $data;
		} else {
			$error = curl_errno ( $ch );
			echo "curl出错，错误码:$error" . "<br>";
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close ( $ch );
			return false;
		}
	}
	
	/**
	 * POST data
	 */
	function http_request($url, $data, $extra = array(), $timeout = 60) {
		$curl = curl_init ();
		curl_setopt ( $curl, CURLOPT_URL, $url );
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, 1 );
		curl_setopt ( $curl, CURLOPT_USERAGENT, $_SERVER ['HTTP_USER_AGENT'] );
		curl_setopt ( $curl, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $curl, CURLOPT_AUTOREFERER, 1 );
		curl_setopt ( $curl, CURLOPT_POST, 1 );
		curl_setopt ( $curl, CURLOPT_POSTFIELDS, $data );
		curl_setopt ( $curl, CURLOPT_TIMEOUT, 30 );
		curl_setopt ( $curl, CURLOPT_HEADER, 0 );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		$tmpInfo = curl_exec ( $curl );
		if (curl_errno ( $curl )) {
			echo 'Errno' . curl_error ( $curl );
		}
		curl_close ( $curl );
		return $tmpInfo;
	}
	public function get($url) {
		$ch = curl_init ( $url );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true ); // 获取数据返回
		curl_setopt ( $ch, CURLOPT_BINARYTRANSFER, true ); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
		$output = curl_exec ( $ch );
		return $output;
		/* 写入文件 */
		/*
		 * $fh = fopen("out.html", 'w') ;
		 * fwrite($fh, $output) ;
		 * fclose($fh) ;
		 */
	}
}