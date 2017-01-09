<?php
/**
 * Created by PhpStorm.
 * User: jim
 * Date: 16-5-24
 * Time: 上午9:35
 */

class HttpService {
    var $appid;
    var $secret;
    var $url;
    var $cmdUrl;
    var $cmdid;

    var $param;

    function setCmdid($cmdid_) {
        $this->cmdid = $cmdid_;
    }

    function setParam($param) {
        $this->param = $param;
    }

     var  $util;
    public function __construct() {

        $this->util = new  Common_util_pub_mem();
        //dump()
    }

    public function getToken() {
        $url  = 'http://219.135.137.83:8083/datahub/access_token?appid=test&secret=abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';

        Log::write('getToken>>>');

        $data = $this->util->get($url);  //json_decode
        $respObject = json_decode($data ,true);
        $token = $respObject['access_token'];
        Log::write('getToken>>>end>>>' . $token);
        return $token;
    }
    public function getInfo() {
        $token = $this->getToken();
        $cmd = 'http://219.135.137.83:8083/datahub/cmd/'.$this->cmdid.'?appid=test&ACCESS_TOKEN=' . $token;
        $time =time();
        $sinparm = array('pamtest' ,"$time");
        $sign = $this->util->getsign($sinparm);

        $params = array (
            'timestamp' =>"$time",
            'params' => $this->param,
            'sign' => $sign
        );
        $headers = array ('Content-Type' => 'application/x-www-form-urlencoded' );
        $params = json_encode($params);
        Log::write('getInfo>>begin>>' .$params .'>>' );
        $res = $this->util->http_request ( $cmd, $params );
         //var_dump($res);
        Log::write('getInfo>>end>');
        $res = json_decode($res);
        //dump($res->appId);

        $resArr = get_object_vars($res);
        //dump($resArr['data']);

        if ($resArr['code'] == 0) {
            //dump($resArr['data']);
            $retVal = get_object_vars ($resArr['data']->retVal);
            //dump($retVal['result']);
            if ($retVal['result'] == -1) {
                $outVal = get_object_vars($resArr['data']->outVal);
                //dump($outVal['_param2']);
                if ($this->cmdid == '1010' || $this->cmdid == '1000' ) {
                    return $outVal['_param2'];
                } elseif ($this->cmdid == '1011' ){
                    return $outVal['_param4'];
                } else {
                    return -1;
                }

            } else {
                return -1;
            }
        } else {
            return -1;
        }
    }
}

class Common_util_pub_mem {
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