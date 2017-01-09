<?php
/**
 * 所有接口的基类
 */
include_once ("../../MyHttp.php");
class Common_util_pub {
	
	private $account = null;
	public $debug = true;
	public $error;
	
	public  $path; //keypath
	
	public function setPath($path) {
	    $this->path = $path;
	}
	
	function __construct($uniAccount) {
		$this->account = $uniAccount;
		if (empty ( $this->account )) {
			trigger_error ( 'error uniAccount id, can not construct ' . __CLASS__, E_USER_WARNING );
		}
	}
	
	function trimString($value) {
		$ret = null;
		if (null != $value) {
			$ret = $value;
			if (strlen ( $ret ) == 0) {
				$ret = null;
			}
		}
		return $ret;
	}
	
	/**
	 * 作用：产生随机字符串，不长于32位
	 */
	public function createNoncestr($length = 32) {
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str = "";
		for($i = 0; $i < $length; $i ++) {
			$str .= substr ( $chars, mt_rand ( 0, strlen ( $chars ) - 1 ), 1 );
		}
		return $str;
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
			//echo $k . '----'. $v ."<br>" ;
			//$buff .= strtolower($k) . "=" . $v . "&";
			
			//echo '-----' . $buff."<br>" ;
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar;
		if (strlen ( $buff ) > 0) {
			$reqPar = substr ( $buff, 0, strlen ( $buff ) - 1 );
		}
		return $reqPar;
	}
	
	/**
	 * 作用：生成签名
	 */
	public function getSign($Obj, $key) {
		
		foreach ( $Obj as $k => $v ) {
			$Parameters [$k] = $v;
			//echo $k . '--' .$v . '</br>';
		}
		//签名步骤一：按字典序排序参数
		ksort ( $Parameters );
		$String = $this->formatBizQueryParaMap ( $Parameters, false );
		//echo '【string1】'.$String.'</br>';
		//签名步骤二：在string后加入KEY
		$String = $String . "&key=" . $key;//WxPayConf_pub::KEY; 修改为动态获取
		//echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5 ( $String );
		//echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtoupper ( $String );
		//echo "【result】 ".$result_."</br>";
		return $result_;
	}
	
	/**
	 * 作用：生成签名  sha1
	 * */
	public function getSignSha1($Obj) {
	
		foreach ( $Obj as $k => $v ) {
			$Parameters [$k] = $v;
			//echo $k . '--' .$v . '</br>';
		}
		//签名步骤一：按字典序排序参数
		ksort ( $Parameters );
		//echo "【string0】 ". $Parameters;
		$String = $this->formatBizQueryParaMap ( $Parameters, false );
		//签名步骤三：sha1加密
		//echo "【string1】 ". $String;
		$String = sha1 ( $String );
		//echo "【string2】 ". $String;
		//签名步骤四：所有字符转为大写
		return $String;
	}
	
	/**
	 * 作用：array转xml
	 */
	function arrayToXml($arr) {
		
		$xml = "<xml>";
		foreach ( $arr as $key => $val ) {
			//exit($key . $val);
			if (is_numeric ( $val )) {
				$xml .= "<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		
		}
		$xml .= "</xml>";
		//exit("----  ".$xml."   ----");
		return $xml;
	}
	
	/**
	 * 作用：将xml转为array
	 */
	public function xmlToArray($xml) {
		//将XML转为array        
		$array_data = json_decode ( json_encode ( simplexml_load_string ( $xml, 'SimpleXMLElement', LIBXML_NOCDATA ) ), true );
		return $array_data;
	}
	
	/**
	 * 作用：以post方式提交xml到对应的接口url
	 */
	public function postXmlCurl($xml, $url, $second = 30) {
		//初始化curl        
		$ch = curl_init ();
		//设置超时
		curl_setopt ( $ch, CURLOP_TIMEOUT, $second );
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		//设置header
		curl_setopt ( $ch, CURLOPT_HEADER, FALSE );
		//要求结果为字符串且输出到屏幕上
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		//post提交方式
		curl_setopt ( $ch, CURLOPT_POST, TRUE );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $xml );
		//运行curl
		$data = curl_exec ( $ch );
		curl_close ( $ch );
		//返回结果
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
	 * 作用：使用证书，以post方式提交xml到对应的接口url
	 */
	function postXmlSSLCurl($xml, $url, $second = 30) {
// 		dump($xml);
// 		dump($url);
		
		$ch = curl_init ();
		//超时时间
		curl_setopt ( $ch, CURLOPT_TIMEOUT, $second );
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		//设置header
		curl_setopt ( $ch, CURLOPT_HEADER, FALSE );
		//要求结果为字符串且输出到屏幕上
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		//设置证书
		//使用证书：cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		curl_setopt ( $ch, CURLOPT_SSLCERTTYPE, 'PEM' );
		
		//dump(WxPayConf_pub::BATHPATH.  $this->path .WxPayConf_pub::SSLCERT_PATH);
		//dump(WxPayConf_pub::BATHPATH.  $this->path . WxPayConf_pub::SSLKEY_PATH);
		//exit();
		curl_setopt ( $ch, CURLOPT_SSLCERT,WxPayConf_pub::BATHPATH.  $this->path .WxPayConf_pub::SSLCERT_PATH );
		//默认格式为PEM，可以注释
		curl_setopt ( $ch, CURLOPT_SSLKEYTYPE, 'PEM' );
		curl_setopt ( $ch, CURLOPT_SSLKEY, WxPayConf_pub::BATHPATH.  $this->path . WxPayConf_pub::SSLKEY_PATH );
		//post提交方式
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $xml );
		$data = curl_exec ( $ch );
		//返回结果
		if ($data) {
			curl_close ( $ch );
			return $data;
		} else {
			$error = curl_errno ( $ch );
			echo "curl出错02，错误码:$error" . "<br>";
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close ( $ch );
			return false;
		}
	}
	
	/**
	 * 作用：打印数组
	 */
	function printErr($wording = '', $err = '') {
		print_r ( '<pre>' );
		echo $wording . "</br>";
		var_dump ( $err );
		print_r ( '</pre>' );
	}
	
	/**
	 * 获取access_token
	 * retunr string access_token
	 */
	public function get_access_token($arr = FALSE) {
		if (! empty ( $this->account ['access_token'] ) && ! empty ( $this->account ['expires_in'] ) && $this->account ['expires_in'] > time ()) {
			return $this->account ['access_token'];
		} else {
			if (empty ( $this->account ['AppId'] ) || empty ( $this->account ['AppSecret'] )) {
				exit ( '请填写公众号的appid及appsecret！' );
			}
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->account['AppId']}&secret={$this->account['AppSecret']}";
			$token = $this->http_get_result ( $url );
			if (! $token)
				exit ( '获取access_token失败，请查看错误！' . $token );
			$record = array ();
			$record ['access_token'] = $this->account ['access_token'] = $token ['access_token'];
			$record ['expires_in'] = $this->account ['expires_in'] = time () + $token ['expires_in'];
			if ($arr) {
				return $record;
			} else {
				return $record ['access_token'];
			}
		}
	}
	
	function http_get_result($url) {
		$ihttp = new Http;
		$content = $ihttp->http_get ( $url );
		return $this->result ( $content );
	}
	function http_post_result($url, $dat) {
		$ihttp = new Http ();
		$content = $ihttp->http_post ( $url, $dat );
		return $this->result ( $content );
	}
	
	function decodeUnicode($str) {
		return preg_replace_callback ( '/\\\\u([0-9a-f]{4})/i', create_function ( '$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");' ), $str );
	}
	
	
/**
	 * 统一对返回数据处理
	 * @param  $content
	 */
	function result($content) {
		/*exit($content);
		$result = json_decode ( $content ['content'], true );
		exit($result);*/
		return json_decode( $content, true ) ;
		/*if (is_array ( $content )) {
			$result = json_decode ( $content ['content'], true );
			if (empty ( $result ['errcode'] )) {
				exit($content);
				return $result;
			} else {
				exit($content);
				if ($this->debug) {
					$this->error = "微信公众平台返回接口错误. \n错误代码为: {$result['errcode']} \n错误信息为: {$result['errmsg']} \n错误描述为: " . $this->error_code ( $result ['errcode'] );
				}
				return false;
			}
		} else {
			exit($content);
			if ($this->debug) {
				$this->error = $content;
			}
			return false;
		}*/
	
	}
}