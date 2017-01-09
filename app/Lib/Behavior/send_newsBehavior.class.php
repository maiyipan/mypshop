<?php
defined('THINK_PATH') or exit();
class send_newsBehavior extends Behavior{
	public function run(&$_data){
		$this->_sms_send($_data);//发送短信
		$this->_weixin_send($_data);//发送微信消息
	}
	/**
	 * 发送短信
	 */
	private function _sms_send($_data){
		$phone=$_data['phone'];
		$corpid='302062';
		$sms_send=smsSend($phone, $corpid);
		return $sms_send;
	}
	/**
	 * 发送邮件
	 */
	private function _email_send(){
		//没有做接口
	}
	/**
	 * 发送微信
	 */
	private function _weixin_send($_data){
		if (false === $shopconf = F('shop')) {
			$conf = D('shop') ->shop_cache();
		}
		C(F('shop'));//获取缓存		
		$send_data = array(
				"touser"=>$_data['toUsername'],
				"msgtype"=>"text",
				"text"=>array(
						"content"=>"你有一份新订单",
				),
		);
		$uid=$_data['uid'];
		$weixinInter=new WeixinInterface($uid);
		$sendMsg=$weixinInter->sendMsg($send_data);
		dump($send_data);
		
	}
		
}