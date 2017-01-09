<?php
class duanxin {
	
	
	/**
	 * 短信发送验证码
	 * 例如  单个手机号发送 sendSMSCode('15578432595',1234);
	 * 多个手机号发送 sendSMSCode(array(15578432595,18888888),1234)
	 * 多个手机 号也可以 sendSMSCode('15578432595,18888888',1234)
	 * @date: 2015-6-25
	 * @author: wintrue<328945440@qq.com>
	 * @param 手机号 $phone 单个 手机号直接填，多个手机号请用数组
	 * @param 验证码 $code
	 * @param 扩展信息 $ext
	 * @return multitype:boolean mixed 返回数组array('result'=>boll,'code'=>string)
	 */
	function sendSMSCode($phone,$code,$ext=array('stime'=>'','rrid'=>'')){
		header("Content-Type: text/html; charset=UTF-8");
		$messge=iconv( "UTF-8", "gb2312//IGNORE" ,$code.'[联众互动]');//内容要求是gb2312
		$flag = 0;
		$params='';
		//要post的数据
		$argv = array(
				'sn'=>'SDK-666-010-03636',
				'pwd'=>strtoupper(md5('SDK-666-010-03636'.'529020')),
				'mobile'=>is_array($phone)?implode(',', $phone):$phone,
				'content'=>$messge,
				'ext'=>'',
				'stime'=>'',//定时时间 格式为2011-6-29 11:09:21
				'msgfmt'=>'',
				'rrid'=>''//如果填写，成功后将返回该值
		);
		$argv=array_merge($argv,$ext);
		//构造要post的字符串
		//http_build_query
		foreach ($argv as $key=>$value) {
			if ($flag!=0) {
				$params .= "&";
				$flag = 1;
			}
			$params.= $key."=";
			$params.= urlencode($value);// urlencode($value);
			$flag = 1;
		}
		$length = strlen($params);
		//创建socket连接
		//$fp = fsockopen("sdk.entinfo.cn",8061,$errno,$errstr,10) or exit($errstr."--->".$errno);
		$cnt=0;
		while($cnt<3 && ($fp = @fsockopen("sdk.entinfo.cn",8061,$errno,$errstr,10))===FALSE) {
			$cnt++;
		}
		if(!$fp){
			//切换备用地址
			$fp = fsockopen("sdk2.entinfo.cn",8061,$errno,$errstr,10);
			if(!$fp){
				return array('result'=>false,'code'=>$errstr."--->".$errno);exit;
			}
	
		}
		//构造post请求的头
		$header = "POST /webservice.asmx/mdsmssend HTTP/1.1\r\n";
		$header .= "Host:sdk.entinfo.cn\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: ".$length."\r\n";
		$header .= "Connection: Close\r\n\r\n";
		//添加post的字符串
		$header .= $params."\r\n";
		//发送post的数据
		fputs($fp,$header);
		$inheader = 1;
		while (!feof($fp)) {
			$line = fgets($fp,1024); //去除请求包的头只显示页面的返回数据
			if ($inheader && ($line == "\n" || $line == "\r\n")) {
				$inheader = 0;
			}
			if ($inheader == 0) {
				// echo $line;
			}
		}
		$line=str_replace("<string xmlns=\"http://tempuri.org/\">","",$line);
		$line=str_replace("</string>","",$line);
		$result=explode("-",$line);
		if(count($result)>1)
			return array('result'=>false,'code'=>$line);
		else
			return array('result'=>true,'code'=>$line);
	
	}
	
}