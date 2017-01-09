<?php
class scoreAction extends frontendAction {
	
	public function _initialize() {
		parent::_initialize ();
	}
	/**
	 * 积分有礼
	 */
	public function integration() {
		//获取积分商品
		$map['uid'] = $this->shopId;
		$count = M('score_item')->where($map)->count();
		$pager = new Page($count,20);
		$list  = M('score_item')->where($map)->order('ordid desc')->limit($pager->firstRow.','.$pager->listRows)->select();
		$this->assign('list',$list);
		$this->display();
	}
	
	public function details(){
		$id = $this->_get('id', 'intval');
		!$id && $this->_404();
		$item_mod = M('score_item');
		$item = $item_mod->where(array('id' => $id))->find();
		!$item && $this->_404();
		$this->assign('item', $item);
		$this->display();
	}
	
	public function exchange(){
		$id = $this->_get('id', 'intval');
		$num = $this->_get('num', 'intval');
		$item_mod = M('score_item');
		$item = $item_mod->where(array('id' => $id))->find();
		$this->assign('item', $item);
		
		$userid = $this->visitor->info ['id'];
		$user = M ( 'user' )->where ( array ('id' => $userid ) )->find();
		$this->assign('user', $user);
		$user_address_mod = M ( 'user_address' );
		$coupons_mod = M('coupons_code');
		$uid= $this->visitor->info['id'];
		//默认地址'
		$where = array(
				'uid'	=>	$userid,
				'moren'	=>	'1',
				'shopid'=>	$this->shopId
		);
		$default_list = $user_address_mod->where ($where)->limit(1)->select ();
		//非默认地址
			$where1 = array(
					'uid'	=>	$this->visitor->info['id'],
					'moren'	=>	'0',
					'shopid'=>	$this->shopId
			);
			$address_list = $user_address_mod->where ( $where1 )->select ();
			if (null == $default_list && count($address_list) != 0) {
			  $default_list = $address_list[0];
			}
			unset($address_list[0]);
			$this->assign ( 'address_list', $address_list );
			$this->assign ( 'default_list', $default_list );
		
		$num = !empty($num) ? $num : 1;
		$sumPrice = $item['price'] * $num;
		$sumScore = $item['score'] * $num;
		$this->assign ('sumPrice', $sumPrice);
		$this->assign ('sumScore', $sumScore);
		
		$this->display();
	}
	
	public function doexchange(){
		$itemid = $this->_request('itemid', 'intval');
		$num = $this->_request('num', 'intval');
		if(empty($itemid) ){
			$data = array (
					'status' => 0,
					'msg' => '参数错误'
			);
		}else{
			
			$user_address = M ( 'user_address' );
			$userid = $this->visitor->info ['id']; //用户ID
			$username =$this->visitor->info ['username']; //用户账号
				
			$item = M('score_item')->where(array('id' => $itemid))->find();
			
			//判断积分是否足够
			$user = M ( 'user' )->where ( array ('id' => $userid ) )->find();
			if($user['score'] < $item['score']*$num ){
				$data = array (
						'status' => 0,
						'msg' => '没有足够积分'
				);
				echo json_encode ( $data );exit;
			}
			
			//生成订单号
			$dingdanhao = date ( "Y-m-dH-i-s" );
			$dingdanhao = str_replace ( "-", "", $dingdanhao );
			$dingdanhao .= rand ( 1000, 2000 );
				
			$time = time (); //订单添加时间
			$address_options = $this->_post ( 'address_options', 'intval' ); //地址  0：刚填的地址 大于0历史的地址
			$remark = $this->_post ( 'remark', 'trim' ); //卖家留言
				
			$payment_id = $this->_post ('payment_id', 'trim');  //$_POST ['payment_id'];
	
			//兑换所需要的费用
			$sumPrice = $item['price'] * $num;
			// 运费
			// 获取运费
			$uid = $this->shopId;
			$shop = M ( 'shop' )->where ( array ('uid' => $uid) )->find ();
			if ($shop) {
				$freepost = $shop ['delivery_freemoney'];
				if ($distributeId == 2 || $freepost <= $sumPrice) { // 到店自提 或者费用大于包邮
					$freesum = 0.00;
				} else {
					$freesum = $shop ['delivery_money'];
				}
			}
			
			$data['remark'] = $remark;
			$data['order_sn'] = $dingdanhao; // 订单号
			$data['add_time'] = $time; // 添加时间
			$data['freesum'] = $freesum;//运费
			$data['sumprice'] = $sumPrice; // 商品总额
			$data['order_score'] = $item['score']*$num;
			
			$data['uid'] = $this->shopId;
			$data['userid'] = $userid; // 用户ID
			$data['username'] = $username; // 用户名
			
			$data['item_id'] = $itemid;
			$data['item_name'] = $item['title'];
			$data['item_num'] = $num;
			$data['status'] = 1;
			
			// 配送方式
			$distributeId = $this->_post ( 'distributeId', 'trim' ); // 1物流配送 2到店自提
			$data ['freetype'] = $distributeId;
			//物流配送的时候，处理收货地址
			if ($address_options == 0) {
				$consignee = $this->_post ( 'getName', 'trim' ); // 真实姓名
				$sheng = $this->_post ( 'sheng', 'trim' ); // 省
				$shi = $this->_post ( 'shi', 'trim' ); // 市
				$qu = $this->_post ( 'qu', 'trim' ); // 区
				$address = $this->_post ( 'getAddressDetail', 'trim' ); // 详细地址
				$phone_mob = $this->_post ( 'getPhone', 'trim' ); // 电话号码
				$save_address = '222'; // 默认所有新增保存地址
				$data ['consignee'] = $consignee; // 收货人姓名
				$data ['mobile'] = $phone_mob; // 电话号码
				$data ['address'] = $sheng . $shi . $qu . $address; // 地址
					
				if ($save_address && !empty($consignee) && !empty($phone_mob)) // 保存地址
				{
					$add_address ['uid'] = $userid; // 用户id
					$add_address ['consignee'] = $consignee;
					$add_address ['address'] = $address;
					$add_address ['mobile'] = $phone_mob;
					$add_address ['sheng'] = $sheng;
					$add_address ['shi'] = $shi;
					$add_address ['qu'] = $qu;
					$add_address ['shopid'] = $this->shopId;
					$add_address ['moren'] = 1; //默认地址
					$user_address->data ( $add_address )->add ();
				}
					
			} else {
				$address = $user_address->where ( 'uid=' . $userid )->find ( $address_options ); //取到地址
				$data ['consignee'] = $address ['consignee']; //收货人姓名
				$data ['mobile'] = $address ['mobile']; //电话号码
				$data ['address'] = $address ['sheng'] . $address ['shi'] . $address ['qu'] . $address ['address']; //地址
			}	
				
			// 时间范围
			$beginTime = $this->_post ( 'distributpayeTimeStart', 'trim' );
			$endTime = $this->_post ( 'distributeTimeEnd', 'trim' );
			$data ['begin_time'] = strtotime ( $beginTime );
			$data ['end_time'] = strtotime ( $endTime );
			$ret = D('score_order')->add($data);
			if($ret !== false){
				$url = U ( 'score/pay', array (
						'orderId' => $dingdanhao
				) );
				$data = array (
						'status' => 1,
						'url' => $url
				);
			}else{
				$data = array (
						'status' => 0,
						'msg' => '兑换失败'
				);
			}
		}
		echo json_encode ( $data );
	}
	
	public function pay(){
		if(!isset ( $_GET ['orderId'] )){
			$this->_404 ();
		}
		$score_order = M ( 'score_order' );
		$orderId = $_GET ['orderId']; //订单号
		//Log::write("orderid:" .  $orderId);
		$orders = $score_order->where ( 'userid=' . $this->visitor->info ['id'] . ' and order_sn=' . $orderId )->find ();
		if (! is_array ( $orders ))
			$this->_404 ();
		$sumPrice = $orders ['sumprice'];
		$payment_id =  $orders ['supportmetho'];
		if ($sumPrice == 0 ) { //如果金额为0 直接结束订单
			$data ['status'] = 2;
			$data ['support_time'] = time ();
			$paymethod_id = date ( "Y-m-dH-i-s" );
			$paymethod_id = str_replace ( "-", "", $paymethod_id );
			$data['support_id'] = $paymethod_id;
			if (M ( 'score_order' )->where ( 'userid=' . $this->visitor->info ['id'] . ' and order_sn=' . $orderId )->data ( $data )->save ()) {
				$prefix = C(DB_PREFIX);
				//修改库存
				M('')->execute("update ".$prefix."score_item set buy_num = buy_num + ".$orders['item_num'].",stock=stock-".$orders['item_num'] ." where id=".$orders['item_id']);
				//减掉用户相应积分
				M ( 'user' )->where(array('id' => $this->visitor->info ['id']))->setDec('score',$orders['order_score']);
				//积分日志
				$score_log_mod = D('score_log');
				$score_log_mod->create(array(
						'uid' => $this->visitor->info ['id'],
						'uname' => $this->visitor->info ['username'],
						'action' => '积分兑换',
						'score' => '-'.$orders['order_score']*$orders['item_num'],
				));
				$score_log_mod->add();
				
				//记录订单记录
				D('orders_log')->addLog($orderId, 1, $this->visitor->info ['id']);
				
				$this->redirect ( 'my/index' );
			} else {
				$this->error ( '操作失败!' );
			}
		}
		else {
			$this->goPay($payment_id, $orderId, $orderId, $sumPrice);
		}
	}
	
	public function goPay($payment_id, $orderid, $dingdanhao, $order_sumPrice){
		//Log::write('goPay>>' .  $payment_id . '--' .$orderid . '--' .  $dingdanhao . '--' .$order_sumPrice);
		$item_order = M ( 'score_order' )->where ( 'userid=' . $this->visitor->info ['id'] . ' and order_sn=' . $dingdanhao )->find ();
		! $item_order && $this->_404 ();
		$body = "佳鲜商城";
		$this->weixinPay( $body, $dingdanhao, $item_order ['sumprice'] );
	}
	/**
	 * $body 商品描述
	 * $out_trade_no 商户订单号
	 * total_fee 金额
	 * */
	public function weixinPay($body, $out_trade_no, $total_fee) {
		//Log::write ( "weixinPay" );
		try {
			Vendor ( 'WeiXin.WxPayPubHelper' );
			$jsApi = new \JsApi_pub ();
			$sid = $this->shopId;
	
			$shopModel = D('shop');
			$pid =  $shopModel->getUidForP($sid); //获取子店对应的父店对应的id，即对应的appid等
			$appid =C ( 'spconf_appid_'. $pid) ;//; ''
			$appsecret = C ( 'spconf_appsecret_'. $pid);//'';
			$mchid = C ( 'spconf_wxpayone_'. $pid);//;//''; ''
			$key = C ( 'spconf_wxpaytwo_'. $pid) ;//''
			$jsApi->setAppid ( $appid );
			$jsApi->setAppsecret ( $appsecret );
			$uid = $this->visitor->info ['id'];
			//Log::write('uid>>>>>' . $uid);
			$wechat_user = M ('wechat_user' );
			if ($uid) {
				$wechat_users = $wechat_user->field ( 'openid' )->where ( "id='" . $uid . "'" )->find ();
				$openid = $wechat_users ['openid'];
			} else {
				// =========步骤1：网页授权获取用户openid============
				// 通过code获得openid
				if (! isset ( $_GET ['code'] )) {
					// 触发微信返回code码
					$js_api_call_url = U('score/weixinPay');
					$rurl = C('pin_baseurl') .  $js_api_call_url . '?body=' . $body . '&out_trade_no=' . $out_trade_no . '&total_fee=' . $total_fee ;
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
			//$openid = 'oG9fXt6HvQbZ8sMSnDvWLq3WZaL0';
			// =========步骤2：使用统一支付接口，获取prepay_id============
			// 使用统一支付接口
			$unifiedOrder = new \UnifiedOrder_pub ();
			$unifiedOrder->setKey($key);
	
			////Log::write('the param create prepay_id, openid:'. $openid . '-body:' . $body.'--out_trade_no:' .$out_trade_no. '--total_fee:' .$total_fee );
			$unifiedOrder->setParameter ( "openid", $openid ); //
			$unifiedOrder->setParameter ( "body", "$body" ); // 商品描述
			$timeStamp = time ();
			// $out_trade_no = $appid . "$timeStamp"; //\WxPayConf_pub::APPID . "$timeStamp";
			$unifiedOrder->setParameter ( "out_trade_no", "$out_trade_no" ); // 商户订单号
			$unifiedOrder->setParameter ( "total_fee", $total_fee * 100 ); // 总金额
	
			//$this->parameters["appid"] = WxPayConf_pub::APPID;//公众账号ID
			$unifiedOrder->setParameter ( "appid", $appid ); // 商户号
			$unifiedOrder->setParameter ( "mch_id", $mchid ); // 商户号
			//$noUrl  =  \WxPayConf_pub::NOTIFY_URL;
			$noUrl =  C('pin_baseurl') . U('score/payBack');
			$unifiedOrder->setParameter ( "notify_url", "$noUrl" ); // 通知地址
			$unifiedOrder->setParameter ( "trade_type", "JSAPI" ); // 交易类型
			$prepay_id = $unifiedOrder->getPrepayId ();
			//Log::write('prepay_id:' . $prepay_id, 'DEBUG');
	
			if($prepay_id == '') {
				$this->error('支付失败，请您重新支付', '/my/index');
			} else {
				// =========步骤3：使用jsapi调起支付============
				$jsApi->setPrepayId ( $prepay_id );
				$jsApi->setAppid($appid);
				$jsApi->setKey($key);
				$jsApiParameters = $jsApi->getParameters ();
				$this->assign ( "dingdanhao", $out_trade_no );
				$this->assign ( "order_sumPrice", $total_fee );
				$this->assign ( "jsApiParameters", $jsApiParameters );
				$this->display ( 'end' );
			}
		} catch ( Exception $e ) {
			$this->redirect ( 'user/index' );
		}
	}
	
	public function payend() {
		if (IS_POST) {
			Vendor ( 'WeiXin.WxPayPubHelper' );
			//$orderid = $_POST ['orderid'];
			$dingdanhao = $_POST ['dingdanhao'];
			$shopModel = D('shop');
			$sid = $this->shopId;
			//Log::write ( "sid>>" . $sid );
			$pid =  $shopModel->getUidForP($sid); //获取子店对应的父店对应的id，即对应的appid等
			//Log::write ( "pid>>" . $pid );
			$appid =C ( 'spconf_appid_'. $pid) ;//; ''
			$appsecret = C ( 'spconf_appsecret_'. $pid);//'';
			//Log::write ( "appid>>" . $appid );
			//Log::write ( "appsecret>>" . $appsecret );
			$mchid = C ( 'spconf_wxpayone_'. $pid);//;//''; ''
			$key = C ( 'spconf_wxpaytwo_'. $pid) ;//''
			//Log::write ( "mchid>>" . $mchid );
			//Log::write ( "key>>" . $key );
			//查询一次订单
			$queryOrder = new \OrderQuery_pub();
			$queryOrder->setParameter ( "out_trade_no", "$dingdanhao" ); //商户订单号
			$queryOrder->setParameter ( "appid", $appid ); // 商户号
			$queryOrder->setParameter ( "mch_id", $mchid ); // 商户号
			$queryOrder->setKey($key);
			$queryOrderResult = $queryOrder->queryOrder ();
			///echo arrayToXml($queryOrderResult);
			if ($queryOrderResult ['return_code'] == 'SUCCESS') {
				if ($queryOrderResult ['result_code'] == 'SUCCESS') {
					if ($queryOrderResult['trade_state'] == 'SUCCESS') {
						//微信支付订单号
						$transaction_id = $queryOrderResult['transaction_id'];
						if ($queryOrderResult ['out_trade_no'] == $dingdanhao) {
							$item_order = M ( 'score_order' )->where ( 'userid=' . $this->visitor->info ['id'] . ' and order_sn=' . $dingdanhao )->find ();
							! $item_order && $this->_404 ();
							$data ['status'] = 2;
							$data ['support_time'] = time ();
							$data['transaction_id'] = $transaction_id; //微信支付订单号
							$ret = M ( 'score_order' )->where ( 'userid=' . $this->visitor->info ['id'] . ' and order_sn=' . $dingdanhao )->data ( $data )->save ();
							if ($ret !== false) {
								$prefix = C(DB_PREFIX);
								//修改库存
								M('')->execute("update ".$prefix."score_item set buy_num = buy_num + ".$item_order['item_num'].",stock=stock-".$item_order['item_num'] ." where id=".$item_order['item_id']);
								//减掉用户相应积分
								M ( 'user' )->where(array('id' => $this->visitor->info ['id']))->setDec('score',$item_order['order_score']);
								//积分日志
								$score_log_mod = D('score_log');
								$score_log_mod->create(array(
										'uid' => $this->visitor->info ['id'],
										'uname' => $this->visitor->info ['username'],
										'action' => '积分兑换',
										'score' => '-'.$item_order['order_score']*$item_order['item_num'],
								));
								$score_log_mod->add();
								$this->redirect ( 'my/index' );
							} else {
								$this->error ( '操作失败!' );
							}
						}
					}
				}
	
			} else {
				$this->redirect ( 'my/index' );
			}
		}
	
	}
	
	/**
	 * 微信支付微信服务器发送支付结果
	 */
	public function payBack(){
		//echo "payBack";
		$ip = $_SERVER["REMOTE_ADDR"];
		//Log::write("id--" . $ip);
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];
		//Log::write ( "payBack ok:" . $postStr);
		$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
		echo $postObj;
		$out_trade_no = $postObj->out_trade_no;
		//Log::write ( "out_trade_no" . $out_trade_no );
		if ($out_trade_no != "") {
			//Log::write( "okok");
			$data ['status'] = 2;
			$data ['support_time'] = time ();
			$ret = M ( 'score_order' )->where ( 'order_sn=' . $out_trade_no )->data ( $data )->save ();
			if ($ret !== false) {
				$orders = M ( 'score_order' )->where ( ' order_sn=' . $out_trade_no )->find();
				$prefix = C(DB_PREFIX);
				//修改库存
				M('')->execute("update ".$prefix."score_item set buy_num = buy_num + ".$orders['item_num'].",stock=stock-".$orders['item_num'] ."where id=".$orders['item_id']);
				$userid = $orders['userid'];
				$user = M ( 'user' )->where ( array ('id' => $userid ) )->find();
				if($user){
					//减掉用户相应积分
					M ( 'user' )->where(array('id' => $userid))->setDec('score',$orders['order_score']);
					//积分日志
					$score_log_mod = D('score_log');
					$score_log_mod->create(array(
							'uid' => $userid,
							'uname' => $user['username'],
							'action' => '积分兑换',
							'score' => '-'.$orders['order_score']*$orders['item_num'],
					));
					$score_log_mod->add();
				}
			} 
		}
		$textTpl = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
		echo $textTpl;
	}
	
	public function doexchangeOld(){
		$itemid = $this->_request('itemid', 'intval');
		$addressid = $this->_request('addressid', 'intval');
		$num = $this->_request('num', 'intval');
		if(empty($itemid) || empty($addressid)){
			$data = array (
					'status' => 0,
					'msg' => '参数错误'
			);
		}else{
			$userid = $this->visitor->info ['id'];
			$username = $this->visitor->info ['username'];
			$address = M ( 'user_address' )->where ( array ('id' => $addressid ) )->find();
			$item = M('score_item')->where(array('id' => $itemid))->find();
			//生成订单号
			$dingdanhao = date ( "Y-m-dH-i-s" );
			$dingdanhao = str_replace ( "-", "", $dingdanhao );
			$dingdanhao .= rand ( 1000, 2000 );
			
			$data = array ('uid' => $this->shopId,
					'order_sn' =>  $dingdanhao,
					'userid' => $userid,
					'username' => $this->visitor->info['username'],
					'item_id'=>$itemid,
					'item_name' => $item['title'],
					'item_num' => $num,
					'consignee' => $address['consignee'],
					'address' => $address['sheng'].$address['shi'].$address['qu'].$address['address'],
					'mobile' => $address['mobile'],
					'order_score' => $item['score'],
					'status' => 0,
					'add_time' => time(),
			);
			$ret = D('score_order')->add($data);
			if($ret !== false){
				$prefix = C(DB_PREFIX);
				M('')->execute("update ".$prefix."score_item set buy_num = buy_num + $num,stock=stock-$num where id=$itemid");
				//减掉相应积分
				M ( 'user' )->where(array('id' => $userid))->setDec('score',$item['score']);
				//积分记录
				//积分日志
				$score_log_mod = D('score_log');
				$score_log_mod->create(array(
						'uid' => $userid,
						'uname' => $username,
						'action' => '积分兑换',
						'score' => '-'.$item['score'],
				));
				$score_log_mod->add();
				$data = array (
						'status' => 1,
						'msg' => '兑换成功'
				);
			}else{
				$data = array (
						'status' => 0,
						'msg' => '兑换失败'
				);
			}
		}
		echo json_encode ( $data );
	}
	
	public function index_ajax() {
		$tag = $this->_get('tag', 'trim'); //标签
		$sort = $this->_get('sortId', 'trim'); //排序
		//Log::write('sort by >>' . $sort);
		switch ($sort) {
			case '0':
				$order =  'buy_Num  DESC, ';
				break;
			case '1':
				$order =  'price ASC ,';
				break;
			case '2':
				$order = 'price DESC,';
				break;
			case '3':
				$order =  'add_time DESC,';
				break;
		}
		$order = $order . 'ordid DESC,id DESC ';
		$where = array();
		// dump($where);
		$tag && $where['intro'] = array('like', '%' . $tag . '%');
	
		//$this->wall_item($where, $order);
	
		$spage_size = 5;// C('pin_wall_spage_size'); //每次加载个数
		$p = $this->_get('p', 'intval', 1); //页码
	
		$where['uid'] = $this->shopId;
		$count = M('score_item')->where($where)->count();
		
		$start = $p - 1;
		$list  = M('score_item')->where($where)->order($order)->limit($start.','.$spage_size)->select();
		
		$this->assign('list', $list);
		$resp = $this->fetch('public:intergrationList');
		$data = array(
				'isfull' => 1,
				'html' => $resp
		);
		//数据查询完
		//Log::write($count);
		//Log::write($start + $spage_size);
		$count <= $start + $spage_size && $data['isfull'] = 0;
		$this->ajaxReturn(1, '', $data);
	
	
	
	}
}