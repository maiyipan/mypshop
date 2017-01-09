<?php
/**
 *
 * 微信支付(weixinPay)
 * 微信退款(weixinRefund)
 * */
class WeixinPay{
	/**
	 * $body 商品描述
	 * $out_trade_no 商户订单号  微信支付(weixinPay)
	 * total_fee 金额
	 * */
	public function weixin_Pay($body, $out_trade_no, $total_fee) {
		//Log::write ( "weixinPay" );
		try {
			Vendor ( 'WeiXin.WxPayPubHelper' );
			$jsApi = new \JsApi_pub ();
			$appid = C ( 'pin_appid' );
			$appsecret = C ( 'pin_appsecret' );

			$jsApi->setAppid ( $appid );
			$jsApi->setAppsecret ( $appsecret );

			$uid = $this->visitor->info ['id'];
			$wechat_user = M ('wechat_user' );
			if ($uid) {
				$wechat_users = $wechat_user->field ( 'openid' )->where ( "id='" . $uid . "'" )->find ();
				$openid = $wechat_users ['openid'];
			} else {
				// =========步骤1：网页授权获取用户openid============
				// 通过code获得openid
				if (! isset ( $_GET ['code'] )) {
					// 触发微信返回code码
					$rurl = \WxPayConf_pub::JS_API_CALL_URL . '?body=' . $body . '&out_trade_no=' . $out_trade_no . '&total_fee=' . $total_fee ;
					$url = $jsApi->createOauthUrlForCode ($rurl);
					Header ( "Location: $url" );
					exit ();
				} else {
					// 获取code码，以获取openid
					$code = $_GET ['code'];
					$jsApi->setCode ( $code );
					$openid = $jsApi->getOpenId ();
				}
			}

			//$openid = 'o98KptxBHkpzf0BU2t0mPlEj_9p0';
			// =========步骤2：使用统一支付接口，获取prepay_id============
			// 使用统一支付接口
			$unifiedOrder = new \UnifiedOrder_pub ();

			////Log::write('the param create prepay_id, openid:'. $openid . '-body:' . $body.'--out_trade_no:' .$out_trade_no. '--total_fee:' .$total_fee );
			$unifiedOrder->setParameter ( "openid", $openid ); // 商品描述
			$unifiedOrder->setParameter ( "body", "$body" ); // 商品描述
			// 自定义订单号，此处仅作举例
			$timeStamp = time ();
			// $out_trade_no = $appid . "$timeStamp"; //\WxPayConf_pub::APPID . "$timeStamp";
			$unifiedOrder->setParameter ( "out_trade_no", "$out_trade_no" ); // 商户订单号
			$unifiedOrder->setParameter ( "total_fee", $total_fee * 100 ); // 总金额
			$noUrl  =  \WxPayConf_pub::NOTIFY_URL;
			$unifiedOrder->setParameter ( "notify_url", "$noUrl" ); // 通知地址
			$unifiedOrder->setParameter ( "trade_type", "JSAPI" ); // 交易类型
			$re = $unifiedOrder->getPrepayId ();
			//Log::write('prepay_id:' . $re, 'DEBUG');
			//dump($re);
			$prepay_id = $re["prepay_id"];

			//Log::write('prepay_id:' . $prepay_id, 'DEBUG');
			//TODO 加入prepay_id 的判断

			//$prepay_id='';
			////Log::write('prepay_id:' . $prepay_id, 'DEBUG');
			if($prepay_id == '') {
				$this->error('支付失败，请您重新支付', '/user/index');
			} else {
				// =========步骤3：使用jsapi调起支付============
				$jsApi->setPrepayId ( $prepay_id );
				$jsApiParameters = $jsApi->getParameters ();
				$this->assign ( "dingdanhao", $out_trade_no );
				$this->assign ( "order_sumPrice", $total_fee );
				$this->assign ( "jsApiParameters", $jsApiParameters );
				// 				///Log::write('jsApiParameters:' . $jsApiParameters, 'DEBUG');
				$this->display ( 'end' );
			}
		} catch ( Exception $e ) {
			//Log::write ( $e );
			$this->redirect ( 'user/index' );
		}
	}

	/**
	 * $body 商品描述
	 * $out_trade_no 商户订单号  微信退款(weixinRefund)
	 * $transaction_id 微信订单号
	 * total_fee 金额
	 * */
public function weixinRefund($transaction_id,$out_trade_no,$out_refund_no,$total_fee,$refund_fee, $sid) {
		try {
			Vendor ( 'WeiXin.WxPayPubHelper' );

			$sid = '90baa3315883e80ce926c4034fc811';//$this->shopId;
			$shopModel = D('shop');
			$pid =  $shopModel->getUidForP($sid); //获取子店对应的父店对应的id，即对应的appid等

			$path = C ( 'spconf_path_'. $pid);
			$appid =C ( 'spconf_appid_'. $pid) ;//; ''
			$appsecret = C ( 'spconf_appsecret_'. $pid);//'';
			$mchid = C ( 'spconf_wxpayone_'. $pid);//;//''; ''
			$key = C ( 'spconf_wxpaytwo_'. $pid) ;//''
			$op_user_id = $mchid;

			// =========步骤2：使用申请退款接口，获取prepay_id============
			// 使用申请退款接口
			$unifiedOrder = new \Refund_pub ();
			//dump($path);

			/*Log::write('key---' . $key);
			Log::write('appid---' . $appid);
			Log::write('path---' . $path);*/
			$unifiedOrder->setPath($path);
			$unifiedOrder->setKey($key);
			$unifiedOrder->setAppid($appid);



			/*Log::write('mchid---' . $mchid);
			Log::write('appid---' . $appid);
			Log::write('transaction_id---' . $transaction_id);
			Log::write('out_trade_no---' . $out_trade_no);
			Log::write('out_refund_no---' . $out_refund_no);
			Log::write('total_fee---' . $total_fee);
			Log::write('refund_fee---' . $refund_fee);
			Log::write('op_user_id---' . $op_user_id);*/
			$unifiedOrder->setParameter ( "mch_id", $mchid ); // 商户号
			$unifiedOrder->setParameter('appid', $appid);
			//Log::write('the param create prepay_id, openid:'. $openid . '-body:' . $body.'--out_trade_no:' .$out_trade_no. '--total_fee:' .$total_fee );
			$unifiedOrder->setParameter('transaction_id', $transaction_id);//微信订单号
			$unifiedOrder->setParameter ( "out_trade_no", "$out_trade_no" ); // 商户订单号
			$unifiedOrder->setParameter("out_refund_no", $out_refund_no);//商户退款单号
			$unifiedOrder->setParameter ( "total_fee", $total_fee * 100 ); // 总金额
			$unifiedOrder->setParameter ( "refund_fee", $refund_fee * 100 ); // 退款金额
			$unifiedOrder->setParameter("op_user_id", $op_user_id);//操作员
			// 自定义订单号，此处仅作举例
			$timeStamp = time ();
			$refund_id = $unifiedOrder->getResult();
			//dump($refund_id);
			return $refund_id;
		} catch ( Exception $e ) {
			//Log::write ( $e );
			$this->redirect ( 'user/index' );
		}
	}
	public function wexinRefundQuery($transaction_id,$out_trade_no,$out_refund_no,$refund_id,$sid){
		try{
			Vendor ( 'WeiXin.WxPayPubHelper' );
// 			$sid = '90baa3315883e80ce926c4034fc811';//$this->shopId;
			$shopModel = D('shop');
			$pid =  $shopModel->getUidForP($sid); //获取子店对应的父店对应的id，即对应的appid等

			$path = C ( 'spconf_path_'. $pid);
			$appid =C ( 'spconf_appid_'. $pid) ;//; ''
			//Log::write('appid>>>' .$appid);
			$appsecret = C ( 'spconf_appsecret_'. $pid);//'';
			$mchid = C ( 'spconf_wxpayone_'. $pid);//;//''; ''
			//Log::write('mch_id>>>'.$mchid);
			$key = C ( 'spconf_wxpaytwo_'. $pid) ;//''
			//Log::write('key>>' .$key);
			// =========步骤2：使用申请退款接口，获取prepay_id============
			// 使用申请退款接口
			$unifiedOrder = new \RefundQuery_pub ();
			//dump($path);
			//Log::write('path---' . $path);
			$unifiedOrder->setPath($path);
			$unifiedOrder->setAppid($appid);
			$unifiedOrder->setKey($key);
			$unifiedOrder->setParameter('appid', $appid);//公众号账号id
			$unifiedOrder->setParameter ( "mch_id", $mchid ); // 商户号
			$unifiedOrder->setParameter("device_info", '013467007045764');//门店编号、设备的ID
			$unifiedOrder->setParameter('transaction_id', $transaction_id);//微信订单号
			$unifiedOrder->setParameter ( "out_trade_no", $out_trade_no ); // 商户订单号
			$unifiedOrder->setParameter("out_refund_no", $out_refund_no);//商户退款单号
			$unifiedOrder->setParameter("refund_id", $refund_id);//微信退款单号


			// 自定义订单号，此处仅作举例
			$timeStamp = time ();
			$refund_query_info = $unifiedOrder->getResult ();
			return $refund_query_info;


		}catch (Exception $e){
			//Log::write ( $e );
			$this->redirect ( 'user/index' );
		}
	}

}
