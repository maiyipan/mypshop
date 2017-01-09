<?php

class payAction extends frontendAction {
	public function _initialize() {
		parent::_initialize ();
	}
	
	
	
	public function index() {
		Vendor ( 'Wxpay.WxPayPubHelper' );
		$jsApi = new \JsApi_pub ();
		
		
		//=========步骤1：网页授权获取用户openid============
		//通过code获得openid
		if (! isset ( $_GET ['code'] )) {
			//触发微信返回code码
			$url = $jsApi->createOauthUrlForCode ( \WxPayConf_pub::JS_API_CALL_URL );
			Header ( "Location: $url" );
		} else {
			//获取code码，以获取openid
			$code = $_GET ['code'];
			$jsApi->setCode ( $code );
			$openid = $jsApi->getOpenId ();
		}
		
		
		//=========步骤2：使用统一支付接口，获取prepay_id============
		//使用统一支付接口
		$unifiedOrder = new \UnifiedOrder_pub ();
		$unifiedOrder->setParameter ( "openid", "$openid" ); //商品描述
		$unifiedOrder->setParameter ( "body", "贡献一分钱" ); //商品描述
		//自定义订单号，此处仅作举例
		$timeStamp = time ();
		$out_trade_no = \WxPayConf_pub::APPID . "$timeStamp";
		$unifiedOrder->setParameter ( "out_trade_no", "$out_trade_no" ); //商户订单号 
		$unifiedOrder->setParameter ( "total_fee", "0.01" ); //总金额
		$unifiedOrder->setParameter ( "notify_url", \WxPayConf_pub::NOTIFY_URL ); //通知地址 
		$unifiedOrder->setParameter ( "trade_type", "JSAPI" ); //交易类型
		$prepay_id = $unifiedOrder->getPrepayId ();
		
		
		//=========步骤3：使用jsapi调起支付============
		$jsApi->setPrepayId ( $prepay_id );
		$jsApiParameters = $jsApi->getParameters ();
		//dump($jsApiParameters);
		$this->assign ( "jsApiParameters", $jsApiParameters );
		$this->display ();
	}
}

