<?php
class smsAction extends backendAction{
	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('sms');
	}
	public function index(){
		$m=M('sms');
		$arr=$m->where('corpid=302062')->find();
// 	dump($arr);
		$this->assign('data',$arr);
		$this->display();
	}
	public function smssave(){
		
		if($_POST['Url']!=null){
			$url=$_POST['Url'];
		}else {
			$this->error('请输入服务器地址');
		}
		if($_POST['CorpID']!=null){
			$corpid=$_POST['CorpID'];
		}else {
			$this->error('请输入企业ID号');
		}
		if ($_POST['LoginName']!=null){
			$loginname=$_POST['LoginName'];
		}else {
			$this->error('请输入账号名称');
		}
		if ($_POST['password']!=null){
			$password=$_POST['password'];
		}else {
			$this->error('请输入账号名称');
		}
			
		
		$longsms=$_POST['longsms'];
		$msg=$_POST['msg'];
		$data=M('sms');
		$data->password=$password;
		$data->longsms=$longsms;
		$data->url=$url;
		$data->corpid=$corpid;
		$data->loginname=$loginname;
		$data->content=$msg;
		$count=$data->save();
		
		if ($count>0) {
    		$this->success('修改成功!','index');
    	}else{
    		$this->error('修改失败');

    	}
//     	return $corpid;
	}
	
	public function sms_send(){
		if(IS_POST){
// 			dump($_POST);
			//手机号码用“;”隔开
			$phone=$_POST['phone'];
			$corpid='302062';
			$sms=smsSend($phone,$corpid);
			dump($sms);
			exit();
			//Log::write('sms-send' . $sms );
			if($sms){
				$this->success('短信发送成功','index');
			}
			else {
				$this->error('短信发送失败');
			}
			/* dump('sms>>>'.$sms);
			echo $sms;
			exit; */
		}else {
			$this->display();
		}
		
	}
}