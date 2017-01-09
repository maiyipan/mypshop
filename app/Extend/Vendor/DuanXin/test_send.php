<?php
class test_send{
	

	var $posturl='http://sms3.mobset.com:8080/Sms_Send.asp?WSDL';
	function sendSMS($phone,$corpid,$content){//手机号码，短信内容
				
		$sms = new SoapClient($this->posturl);
		$data=M('sms')->where('corpid='.$corpid)->find();
		$TimeStamp=date('mdHis',time());
		$Timer=date('y-m-d H:i:s',time());
		//exit();
		//解析单个手机号和多个手机号
		$phonenew= explode(";",$phone);
		$count=count($phonenew);
		for ($i=0;$i<$count;$i++){
			$MobileListGroup[$i] = array('Mobile'=>$phonenew[$i], 'MsgID'=>'');
		}		
// 		$MobileListGroup[0] = array('Mobile'=>'13570902699', 'MsgID'=>'');
		if($content==null){
				$content=$data['content'];	
		}else {
			$content=$content;
		}
		
		$param= array(
				
				//测试
				'CorpID'=>$data['corpid'],//企业id
				'LoginName'=>$data['loginname'],//登陆账号
				'Password'=>md5($data['corpid'].$data['password'].$TimeStamp),//密码
				'TimeStamp'=>$TimeStamp,//时间戳
				'LongSms'=>'0',//0普通短信，1长短信
				'AddNum'=>'',
				'MobileList'=>array(
						'MobileListGroup'=>$MobileListGroup),
				'Content'=>$content,	
// 				'Mobile'=>is_array($phone)?implode(',', $phone):$phone,//当个手机号码和多个手机号码			
				);
// 		dump($data);
		$data = object_to_array ( $sms->Sms_Send( $param ) );
		Log::write('object_to_array' . $data );
// 		dump($data);//有两个数据1.Count 2.ErrMsg
// 		exit();
		return $data;
		
	}
	

}
