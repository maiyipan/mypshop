<?php

class OrderAction extends userbaseAction {
	private $_ship_company = array('圆通'=> 'YT','申通'=>'ST','中通'=>'ZT','邮政EMS'=>'YZEMS','天天'=>'TT','优速'=>'YS','快捷'=>'KJ','全峰'=>'QF','增益'=>'ZY');


	var $cart;
	public function _initialize() {
		parent::_initialize();
		import('Think.ORG.Cart');// 导入分页类
		$this->cart=new Cart($this->shopId);
		$this->cart->setCartKey($this->shopId);

	}
	/**
	 * 订单列表
	 */
	public function index(){
		$item_order = M ( 'item_order' );
		$order_detail = M ( 'order_detail' );
		$status = $_GET ['status'];
		if (! isset ( $_GET ['status'] )) {
			$where = array('status'=>1,'userId'=>$this->visitor->info['id']);
		} else if($_GET['status'] == 0){
			$where =array('userId'=>$this->visitor->info['id']);
		}else {
			$where = array('status'=>$_GET['status'],'userId'=>$this->visitor->info['id']);
		}
		$item_orders = $item_order->where ($where)->order ( 'id desc' )->select ();
		//Log::write($item_order->getLastSql(), 'DEBUG');
		foreach ( $item_orders as $key => $val ) {
			$order_details = $order_detail->where ( "orderId='" . $val ['orderId'] . "'" )->select ();
			foreach ( $order_details as $val ) {
				$items = array ('title' => $val ['title'], 'img' => $val ['img'], 'price' => $val ['price'], 'quantity' => $val ['quantity'], 'itemId' => $val ['itemId'] );
				$item_orders [$key] ['items'] [] = $items;
			}
		}
		$this->assign ( 'item_orders', $item_orders );
		$this->assign ( 'status', $status );
		$this->display ();
	}

	public function zhifu() {
		$this->_config_seo ();
		$this->display ();
	}

	public function cancelOrder() { //取消订单
		$orderId = $_GET ['orderId'];
		! $orderId && $this->_404 ();

		$this->assign ( 'orderId', $orderId );
		$this->_config_seo ();
		$this->display ();
	}
	public function confirmOrder() //确认收货
{
		$orderId = $_GET ['orderId'];
		$status = $_GET ['status'];
		! $orderId && $this->_404 ();
		$item_order = M ( 'item_order' );
		$item = M ( 'item' );
		$item_orders = $item_order->where ( 'orderId=' . $orderId . ' and userId=' . $this->visitor->info ['id'] . ' and status=3' )->find ();
		if (! is_array ( $item_orders )) {
			$this->error ( '该订单不存在!' );
			exit();
		}
		$data ['status'] = 4; //收到货
		if ($item_order->where ( 'orderId=' . $orderId . ' and userId=' . $this->visitor->info ['id'] )->save ( $data )) {
		    //用户操作订单日志
		    $this->addwwwlogs($this->visitor->info ['id'], $orderId, 'confirmOrder','确认收货');
			$order_detail = M ( 'order_detail' );
			$order_details = $order_detail->where ( 'orderId=' . $orderId )->select ();
			foreach ( $order_details as $val ) {
				$item->where ( 'id=' . $val ['itemId'] )->setInc ( 'buy_num', $val ['quantity'] );
			}

			//score
			$goods_sumPrice = $item_orders['goods_sumPrice'];
			$score = $goods_sumPrice; //获取积分变量
			//Log::write("score:" + $score);
			if (intval($score) == 0)  { //return false; //积分为0
				$score = 0;
			}
			$score_data = array('score'=>array('exp','score+'.$score), 'score_level'=>array('exp', 'score_level+'.abs($score)));

			$uid = $this->visitor->info ['id'];
			$username = $this->visitor->info ['username'];
			//Log::write("uid--" .  $uid . '--username-'. $username);
			M('user')->where(array('id'=>$uid))->setField($score_data); //改变用户积分
			//积分日志
			 $score_log_mod = D('score_log');
			$score_log_mod->create(array(
					'uid' => $uid,
					'uname' => $username,
					'action' => 'confirmOrder',
					'score' => $score,
			));
			$score_log_mod->add();
			$url = U('my/publishEvaluation',array('sid'=>$this->shopId));
			$this->success('请您评价', $url );
			//$this->redirect ( '/user/index/status/'. $status);
		} else {
			$this->error ( '确定收货失败' );
		}


	}

	public function closeOrder() { // 关闭订单
		$discount = M ( 'discount' );
		$full_cut = M ( 'full_cut' );
		$gift = M ( 'gift' );
		$orderId = $_POST ['orderId'];
		$cancel_reason = $_POST ['cancel_reason'];
		! $orderId && $this->_404 ();
		$item_order = M ( 'item_order' );
		$item = M ( 'item' );
		$order_detail = M ( 'order_detail' );
		$order = $item_order->where ( 'orderId=' . $orderId . ' and userId=' . $this->visitor->info ['id'] )->find ();
		$coupon = $order ['coupon'];
		$coupon_type = substr ( $coupon, 0, 1 ); // 优惠券类型

		$where ['random'] = substr ( $coupon, 1 );
		if ("Z" == $coupon_type) {
			$info = $discount->where ( $where )->find ();
			$info ['valid'] = 0;
			$discount->data ( $info )->save ();
		} elseif ("M" == $coupon_type) {
			$info = $full_cut->where ( $where )->find ();
			$info ['valid'] = 0;
			$full_cut->data ( $info )->save ();
		} elseif ("B" == $coupon_type) {
			$info = $gift->where ( $where )->find ();
			$info ['valid'] = 0;
			// 恢复余额
			//Log::write($info ['surplus']  . '---' . $order ['couponprice']);
			$info ['surplus'] = $info ['surplus'] + $order ['couponprice'];
			$gift->data ( $info )->save ();
			////Log::write ( $gift->getLastSql (), 'debug' );
		}

		if (! is_array ( $order )) {
			$this->error ( '该订单不存在' );
		} else {
			$status = $order['status'];
			$data ['status'] = 5;
			$data ['closemsg'] = $cancel_reason;
			if ($item_order->where ( 'orderId=' . $orderId )->save ( $data )) { // 设置为关闭
			    //用户操作订单日志
			    $this->addwwwlogs($this->visitor->info ['id'], $orderId, 'closeOrder','关闭订单');
				$order_details = $order_detail->where ( 'orderId=' . $orderId )->select ();
				foreach ( $order_details as $val ) {
					$item->where ( 'id=' . $val ['itemId'] )->setInc ( 'goods_stock', $val ['quantity'] );
				}
// 				$this->success('退订成功','index/status/'.$status);
				$this->success('退订成功',U('order/index',array('status'=>$status)));
			} else {
				$this->error ( '关闭订单失败!' );
			}
		}
	}

	public function checkOrder() //查看订单
{
		$orderId = $_GET ['orderId'];
		! $orderId && $this->_404 ();
		$status = $_GET ['status'];
		$item_order = M ( 'item_order' );
		$order = $item_order->where ( 'orderId=' . $orderId . ' and userId=' . $this->visitor->info ['id'] )->find ();
		if (! is_array ( $order )) {
			$this->error ( '该订单不存在' );
		} else {

			$order_detail = M ( 'order_detail' );

			$order_details = $order_detail->where ( "orderId='" . $order ['orderId'] . "'" )->select ();
			$item_detail = array ();
			foreach ( $order_details as $val ) {
				$items = array ('title' => $val ['title'], 'img' => $val ['img'], 'price' => $val ['price'], 'quantity' => $val ['quantity'] );
				//$order[$key]['items'][]=$items;
				$item_detail [] = $items;
			}
		}
/* 

		$ch = curl_init();
		//  $url = 'http://apis.baidu.com/ppsuda/waybillnoquery/waybillnotrace?expresscode=YT&billno=200093247451';
		$header = array(
				'apikey: 01a24c0bb4aef2f6ec728501231ac4f5',
		);

		// 添加apikey到header
		$expressname = $order['userfree'];
		//    dump($expressname); //ok
		$expresscode = $this->_ship_company[$expressname];
		//   dump($expresscode); //ok
		$billno =  $order['shipcode'];
		//     dump($billno); //ok
	//	dump($expresscode);
	//	dump($billno);

		$url = 'http://apis.baidu.com/ppsuda/waybillnoquery/waybillnotrace?expresscode='.$expresscode.'&billno='.$billno;

		curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// 执行HTTP请求
		curl_setopt($ch , CURLOPT_URL , $url);
		$res = curl_exec($ch);
	//	dump($res);
		//    var_dump(json_decode($res));

		$billInfo = json_decode($res);


		$wayBills =object_to_array ($billInfo)['data'][0]['wayBills'];

		//dump($wayBills);
		$this->assign('waybills',$wayBills); */
		$whereshop['uid'] = $order['uid'];
		$shopinfo = M('shop')->where($whereshop)->field('name, address,tel')->find();
		$this->assign ( 'item_detail', $item_detail );
		$this->assign ( 'order', $order );
		$this->assign('shop',$shopinfo);
// 		$this->_config_seo ();
		$this->display ();;
	}

	public function refundOrder(){//申请退款
		if(IS_POST){
			$item_order=M('item_order');
			$orderId=$this->_post('orderId',trim);
			$cancel_reason = $this->_post('cancel_reason',trim);
			! $orderId && $this->_404 ();
			$data['refund'] = 1;
			$item_order->where('orderId= ' .$orderId)->save($data);
			//用户操作订单日志
			$this->addwwwlogs($this->visitor->info ['id'], $orderId, 'refundOrder','申请退款');
			//Log::write('refund'.'-----'.$data['refund']);
			$this->success('退款后台审核中',U('order/index',array('status'=>3)));

		}else {
			$orderId = $this->_get('orderId',trim);
			$status  = $this->_get('status',trim);
			! $orderId && $this->_404 ();
			$this->assign ( 'orderId', $orderId );
			$this->_config_seo ();
			$this->display();
			}
	}

	public function jiesuan() { //结算
		if ($this->cart -> countCheckedGoods() > 0) {
			$shopaddress = M('shop')->where('uid =' . "'$this->shopId'" )->find();
			$this->assign('shop',$shopaddress);

			$coupons_mod = M('coupons_code');
			$cp_list = $coupons_mod->where('uid=' . $this->visitor->info['id'])->select();
			$this->assign('cp_list',$cp_list);


			$user_address_mod = M ( 'user_address' );
			$coupons_mod = M('coupons_code');
			$uid= $this->visitor->info['id'];
			
			/* $time=date("Y-m-d",time());
			$discoutlist=$coupons_mod->join('LEFT JOIN weixin_discount 
			         ON weixin_discount.random = weixin_coupons_code.random')
			       ->where('uid = ' ."'$uid'" .'  and type= ' . "'Z'" . ' and expiretime >= ' ."'$time'" .' and valid=0' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();
			//Log::write($coupons_mod->getLastSql(), 'DEBUG');
			$full_cutlist=$coupons_mod->join('LEFT JOIN weixin_full_cut 
			    ON weixin_full_cut.random = weixin_coupons_code.random')
			->where('uid = ' ."'$uid'" .'  and type= ' . "'M'" . ' and expiretime >= ' ."'$time'" .' and valid=0' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();
			//Log::write($coupons_mod->getLastSql(), 'DEBUG');
			$giftlist=$coupons_mod->join('LEFT JOIN weixin_gift 
			    ON weixin_gift.random = weixin_coupons_code.random')
			->where('uid = ' ."'$uid'" .'  and type= ' . "'B'" . ' and expiretime >= ' ."'$time'" .' and valid=0' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();
			//Log::write($coupons_mod->getLastSql(), 'DEBUG');
			$this->assign('full_cut',$full_cutlist);
			$this->assign('gift',$giftlist);
			$this->assign('cart',$discoutlist); */
			
			if (isset($_GET['type']) &&  isset($_GET['random']) ) {
				$this->assign('youhuiid',$_GET['type'] . $_GET['random']); 
				$this->assign('isyouhui', 1);
			} else {
				$this->assign('isyouhui', 0);
			}

			
			$cp_list = $coupons_mod->where('uid=' . $this->visitor->info['id'])->select();
			//Log::write($coupons_mod->getLastSql(), 'DEBUG');
			$this->assign('cp_list',$cp_list);

			
			 // begin  地址选择方式修改 by jimyan 2016-04-11 15:14:55
			
			//默认地址'
			$where['uid'] = $this->visitor->info['id'];
			$where['shopid'] = $this->shopId;
			/* $where['uid'] = array(
					'uid'	=>	$this->visitor->info['id'],
					'moren'	=>	'1',
					'shopid'=>	$this->shopId
			); */
			 if (isset ( $_GET ['addressid'] ) ) {
			 	$where['id'] = $_GET ['addressid'] ;
			 	
			 } else {
			 	$where['moren'] = '1';
			 }
			 if ($_SESSION ['default_list'] != null  && !isset ( $_GET ['addressid'] )  ) {
			 	 $default_list = $_SESSION ['default_list'] ;
			 } else {
			 	 $default_list = $user_address_mod->where ($where)->find ();
			 }
			////Log::write('addrsssql--'.$user_address_mod->getLastSql(), 'DEBUG' , 'DEBUG');
			$_SESSION ['default_list'] = $default_list;
			/*
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
			$this->assign ( 'address_list', $address_list ); */
			//dump($default_list);
			$this->assign ( 'default_list', $default_list );

			//end
			$items = M ( 'item' );
			$pingyou = 0;
			$kuaidi = 0;
			$ems = 0;
			$freesum = 0;


			//修改订单运费计算的算法
			$sumPrice = 0;
			foreach ( $_SESSION [$this->cart->cartKey] as $item ) {
				//Log::write('item_status'. '-----'.$item['status']);
				if($item['status'] == 0){
					$sumPrice += $item['price'] * $item['num'];
					/* $free = $items->field ( 'free,pingyou,kuaidi,ems' )->where ( 'free=2' )->find ( $item ['id'] );
					if (is_array ( $free )) {
						$pingyou += $free ['pingyou'];
						$kuaidi += $free ['kuaidi'];
						$ems += $free ['ems'];
						$freesum += $free ['pingyou'] + $free ['kuaidi'] + $free ['ems'];
					} */

				}

			}

			//Log::write('sumPrice----'.$sumPrice);

			if($sumPrice > C ( 'pin_baoyou' )) {
				$freesum = 0;
			} else {
				$freesum = C ( 'pin_freeprice' );
			}
            //Log::write('freesum---'.$freesum);	
            // $dingdanhao = date("Y-m-dH-i-s");
			// $dingdanhao = str_replace("-","",$dingdanhao);
			// $dingdanhao .= rand(1000,2000);
			/* import ( 'Think.ORG.Cart' ); // 导入分页类
			$cart = new Cart ($this->shopId);
			$cart->setCartKey($this->shopId); */
			$sumPrice = $this->cart->getPrice ();

			$freearr = array ();
			if ($pingyou > 0) {
				$freearr [] = array ('value' => 1, 'price' => $pingyou );
			}
			if ($kuaidi > 0) {
				$freearr [] = array ('value' => 2, 'price' => $kuaidi );
			}
			if ($ems > 0) {
				$freearr [] = array ('value' => 3, 'price' => $ems );
			}

			$freearr [] = array ('value' => 4, 'price' => $freesum );
			//Log::write('freearr------value--' .$freearr[0]['value'].'----price--'.$freearr[0]['price']);
			//满减信息
			$fullgiveModel = new FullGive();
			$fullgivewhere['uid'] = $this->shopId;
			$fullgivewhere['is_close'] = 0;
// 			$fullgivewhere['award_type'] = 1;
			$fullgivewhere['start_time'][] = array('elt', date('Y-m-d'));
			$fullgivewhere['end_time'][] = array('egt', date('Y-m-d'));
			$fullgiveList = D ( 'promotion_give' )->where($fullgivewhere)->order('id desc')->select();
			$catefullgive = array();
			foreach ($fullgiveList as $fullgive){
				$fullgiveModel->addFullGive($fullgive, $this->shopId);
				if($fullgive['good_type'] == 2){
					$catefullgive[] = $fullgive;
				}
			}
			foreach ($catefullgive as $fullgive){//子分类
				$cates = D('item_cate')->get_child_ids($fullgive['good_value']);
				foreach ($cates as $cate){
// 					$fullgiveModel->addFullGive($fullgive, $cate, $fullgive['good_type'], $this->shopId);
					$fullgive['good_value'] = $cate; 
					$fullgiveModel->addFullGive($fullgive, $this->shopId);
				}
			}
			//满减信息--end
			/*******start********/
			$sumPrice = 0.00;
			$youhui = 0.00;
			$itemsArr = array();
			foreach ($_SESSION[$this->cart->cartKey] as $item) {
				if($item['id'] == 0){
					unset($_SESSION[$this->cart->cartKey][$item['id']]);
					continue;
				}
				//Log::write('getPrice--' . $item['status'] );
				if($item['status'] == 0){
					$itemsArr[] = $item;
					//组合商品
					if($item['types'] == 1){
						$assemblewhere['id'] = $item['id'];
						$assemblewhere['start_time'][] = array('elt', date('Y-m-d'));
						$assemblewhere['end_time'][] = array('egt', date('Y-m-d'));
						$assemble = M('assemble')->where($assemblewhere)->find();
						if($assemble){
							$item['oldprice'] =  $assemble['original_price'];
							//Log::write('oldprice---'.$item['oldprice']);
							$item['price'] = $assemble['assemble_price'];
							//Log::write('price---'.$item['price']);
							$item['title'] = $assemble['name'];
							//Log::write('title---'.$item['title']);
							$itemSumPrice = $item['num'] * $assemble['assemble_price'];
							//Log::write('itemSumprice---'.$itemSumPrice);
						}else{
							$item['disable'] = 1;
							//Log::write('disable---'.$item['disable']);
						}
						$prefix = C(DB_PREFIX);
						//获取组合商品列表
						$assembleItems  = M('assemble_item')->field('i.comments,i.id,b.size,b.unitName, i.price as price,b.originplace,b.goodsId,b.title,b.img')
									->join($prefix.'item i ON i.id='.$prefix.'assemble_item.item_id')
									->join($prefix.'item_base b ON i.baseid=b.id')
									->where(array('assemble_id' => $item['id']))->select();
						$assembles[$item['id']] = $assembleItems;
					}else{
						//查看是否是限时抢购商品
						$limitbuywhere['type'] = 1;
						$limitbuywhere['condition'] = $item['id'];
						$limitbuywhere['is_close'] = 0;
						$limitbuywhere['start_time'][] = array('elt', date('Y-m-d H:i:s'));
						$limitbuywhere['end_time'][] = array('egt', date('Y-m-d H:i:s'));
						$limitbuy=  D ( 'promotion' )->where($limitbuywhere)->order('id desc')->find();
						
						if($limitbuy){
							//Log::write('limitbuy-----yes');
							$itemSumPrice = $item['num'] * $limitbuy['award_value'];
							$item['oldprice'] =  $item['price'];
						}else{
							$itemSumPrice = $item['num'] * $item['price'];
							
							$fullgiveModel->add($item['id'], $item['price'], $item['num'], $item['cate_id']);
							//update -- start
// 							//查看满减商品
// 							$fullcutwhere['type'] = 0;
// 							$fullcutwhere['condition'] = $item['id'];;
// 							$fullcutwhere['is_close'] = 0;
// 							$fullcutwhere['start_time'][] = array('elt', date('Y-m-d'));
// 							$fullcutwhere['end_time'][] = array('egt', date('Y-m-d'));
// 							$fullcut = D ( 'promotion' )->where($fullcutwhere)->order('id desc')->find();
// 							if($fullcut && $fullcut['reserve'] <= $itemSumPrice){
// 								//Log::write('fullcut--------yes');
// 								$item['award_type'] =  $fullcut['award_type'];
// 								$item['reserve'] =  $fullcut['reserve'];
// 								if($fullcut['award_type'] == 1){
// 									$youhui = $youhui + $fullcut['award_value'];
// 									//$itemSumPrice = $itemSumPrice - $fullcut['award_value'];
// 									$item['youhui'] =  $fullcut['award_value'];
// 								}else if($fullcut['award_type'] == 2){
// 									$item['youhui'] =  $fullcut['award_value'];
// 								}
// 							}
							//update -- end
						}
					}
					$sumPrice = $sumPrice+ $itemSumPrice;
				}
			}
			//满减计算
			$fullgive = $fullgiveModel->givegife();
			if(!empty($fullgive)){
				if($fullgive['tt'] == 1){
					$sumPrice = $sumPrice - $fullgive['award_value'];
					$youhui = $youhui + $fullgive['award_value'];
				}
			}	
			$this->assign ( 'fullgive', $fullgive );
			
			//获取运费
			$uid = $this->shopId;
			$shop = M('shop')->where(array('uid'=>$uid))->find();
			if($shop){
				$freepost = $shop['delivery_freemoney'];
				if($freepost <= $sumPrice){
					$freesum = 0.00;
				}else{
					$freesum =  $shop['delivery_money'];
				}
			}
			// seesion 中保存一个值做为地址管理时区分是个人中心地址还是订单进去的地址
			$_SESSION ['isjiesuan'] = '0';
			
			$this->assign('assembles', $assembles );
			$this->assign ( 'item', $itemsArr);
			$this->assign('goods_sumPrice', $this->cart->getPrice()); //商品金额
			$this->assign ( 'sumPrice', $sumPrice  +$freesum );  //$sumPrice+$freesum 
			$this->assign ( 'youhui', $youhui );
			$this->assign ( 'freesum', $freesum );
			// 商品金额 - 优惠 + 运费 = 订单金额
			/*******end********/
			$this->display ();
		} else {
			$this->error('请先选择商品',  U('shopcart/index'));
		}
	}
	
	
	/**
	 *  订单结算
	 * */
	public function pay(){ //出订单
		$_SESSION ['isjiesuan'] = null;
		$_SESSION ['default_list'] = null;
		//Log::write('pay order>>');
		if (IS_POST && count ( $_SESSION [$this->cart->cartKey] ) > 0) {
			$user_address = M ( 'user_address' );
			$item_order = M ( 'item_order' );
			$order_detail = M ( 'order_detail' );
			$item_goods = M ( 'item' );
			$discount = M('discount');
			$full_cut = M('full_cut');
			$gift = M('gift');
			$order_coupon = M('order_coupon');

			$uid = $this->visitor->info ['id']; //用户ID
			$this->visitor->info ['username']; //用户账号
			//Log::write('uid>>' . $uid);
			//Log::write('usernam>>' . $this->visitor->info ['username']);
			//生成订单号
			$dingdanhao = date ( "Y-m-dH-i-s" );
			$dingdanhao = str_replace ( "-", "", $dingdanhao );
			$dingdanhao .= rand ( 1000, 2000 );
			//Log::write('dingdanhao>>' . $dingdanhao);
			$time = time (); //订单添加时间
			$address_options = $this->_post ( 'address_options', 'intval' ); //地址  0：刚填的地址 大于0历史的地址
			//Log::write('address_options>>>' .$address_options);
			$shipping_id = $this->_post ( 'shipping_id', 'intval' ); //配送方式
			//Log::write('shipping_id>>>'.$shipping_id);
			$postscript = $this->_post ( 'postscript', 'trim' ); //卖家留言
			//Log::write('postscript'.$postscript);
			$payment_id = $this->_post ('payment_id', 'trim');  //$_POST ['payment_id'];
			Log::write('payment_id'.$payment_id , Log::DEBUG);
			//isyouhuicode
			$useCode = $this->_post('isyouhuicode', 'trim'); // 是否使用优惠码   1是   0否
			//Log::write('useCode'.$useCode);
			//得到优惠码
			$coupon = $this->_post ('exchangeCode', 'trim');
			//Log::write('coupon'.$coupon);
			if ($useCode && $useCode == 1 && $coupon) {
				$nowdate = strtotime(date("Y-m-d H-i-s"));
				$coupon_type = substr($coupon,0,1); //优惠码类型
				//Log::write('coupon_type'.$coupon_type);
				if ("Z" == $coupon_type) {//折扣码
					$where['random']= substr($coupon,1);
					$info= $discount->where($where)->find();
					if ($info && $info['valid'] == 0){
						if ($info && $nowdate < strtotime($info['begintime']) &&  $nowdate > strtotime($info['expiretime'])){
// 							$this->error ( '优惠未开始或已过期!' );
							$this->ajaxReturn(0,'优惠未开始或已过期!');
						} else {
							$info['coupon_type'] = "Z";
						}
					} else {
// 						$this->error ( '优惠码已使用!' );
						$this->ajaxReturn(0,'优惠码已使用!' );
					}
				} elseif ("M" == $coupon_type){//满减码
					$where ['random'] = substr ( $coupon, 1 );
					$info = $full_cut->where ( $where )->find ();
					if ($info && $info ['valid'] == 0) {
						if ($info && $nowdate < strtotime($info['begintime']) &&  $nowdate > strtotime($info['expiretime'])){
							$this->ajaxReturn(0,'优惠码未开始或已过期!' );
						} else {
							$info['coupon_type'] = "M";
						}
					} else {
						$this->ajaxReturn(0,'优惠码已使用!' );
					}
				} elseif ("B" == $coupon_type){
					$where['random']= substr($coupon,1);
					$info= $gift->where($where)->find();

					if ($info && $info ['valid'] == 0) {
						if ($info && $nowdate < strtotime($info['begintime']) &&  $nowdate > strtotime($info['expiretime'])){
							$this->ajaxReturn(0,'优惠码未开始或已过期!' );
						} else {
							$info['coupon_type'] = "B";
						}
					} else {
						$this->ajaxReturn(0,'优惠码已使用!' );
					}
				} else {
					$this->ajaxReturn(0,'优惠码格式错误,生成订单失败!' );
				}
				//Log::write('itemtypename>>>'.$info['itemtypename']);
				if($info['itemtypename'] == 1){//指定商品
					$itemids = array();
					$couponstypeid = $info['typeid'];
					//Log::write('typeid>>>'.$couponstypeid);
					if($couponstypeid){//查询对应的商品
						$couponslist = M('coupons')->where(array('typeid'=>$couponstypeid))->select();
						foreach ($couponslist as $couponss){
							$itemids[] = $couponss['itemid'];
						}
					}
				}
			}

			//满减信息
			$fullgiveModel = new FullGive();
			$fullgivewhere['uid'] = $this->shopId;
			$fullgivewhere['is_close'] = 0;
			$fullgivewhere['start_time'][] = array('elt', date('Y-m-d'));
			$fullgivewhere['end_time'][] = array('egt', date('Y-m-d'));
			$fullgiveList = D ( 'promotion_give' )->where($fullgivewhere)->order('id desc')->select();
			$catefullgive = array();
			foreach ($fullgiveList as $fullgive){
				$fullgiveModel->addFullGive($fullgive, $this->shopId);
				if($fullgive['good_type'] == 2){
					$catefullgive[] = $fullgive;
				}
			}
			foreach ($catefullgive as $fullgive){//子分类
				$cates = D('item_cate')->get_child_ids($fullgive['good_value']);
				foreach ($cates as $cate){
					$fullgive['good_value'] = $cate;
					$fullgiveModel->addFullGive($fullgive, $this->shopId);
				}
			}
			//满减信息--end

			//重新计算总价
			$sumPrice = 0.00;
			$youhui = 0.00;
			//需要优惠的商品价格
			$needcouponPrice = 0.00;
			//会员折扣金额
			$needcardPrice = 0.00;
			foreach ($_SESSION[$this->cart->cartKey] as $item) {
				if($item['id'] == 0){
					continue;
				}
				if($item['status'] == 0){
					$iscoupon = false;
					//组合商品
					if($item['types'] == 1){
						$assemblewhere['id'] = $item['id'];
						$assemblewhere['start_time'][] = array('elt', date('Y-m-d'));
						$assemblewhere['end_time'][] = array('egt', date('Y-m-d'));
						$assemble = M('assemble')->where($assemblewhere)->find();
						if($assemble){
							//Log::write('assemble>>>ture');
							$iscoupon = true;
							$item['oldprice'] =  $assemble['original_price'];
							$item['price'] = $assemble['assemble_price'];
							$item['title'] = $assemble['name'];
							$itemSumPrice = $item['num'] * $assemble['assemble_price'];
							$youhui = $youhui + ($item['oldprice']*$item['num'] -$itemSumPrice);
						}else{
							$item['disable'] = 1;
						}
						$prefix = C(DB_PREFIX);
						//获取组合商品列表
						$assembleItems  = M('assemble_item')->field($prefix.'item.*')
										->join($prefix.'item ON '.$prefix.'item.id='.$prefix.'assemble_item.item_id')
										->where(array('assemble_id' => $item['id']))->select();
						$item['items'] = $assembleItems;
					}else{
						//查看是否是限时抢购商品
						$limitbuywhere['type'] = 1;
						$limitbuywhere['condition'] = $item['id'];
						$limitbuywhere['is_close'] = 0;
						$limitbuywhere['start_time'][] = array('elt', date('Y-m-d H:i:s'));
						$limitbuywhere['end_time'][] = array('egt', date('Y-m-d H:i:s'));
						$limitbuy=  D ( 'promotion' )->where($limitbuywhere)->order('id desc')->find();
						if($limitbuy){
							//Log::write('limitbuy>>>true');
							$iscoupon = true;
							$itemSumPrice = $item['num'] * $limitbuy['award_value'];
							//$item['oldprice'] =  $item['price'];
							//调整-记录优惠信息
							$detailYouhui[$item['id']]['type']  =  10; //限时抢购优惠
							$detailYouhui[$item['id']]['value'] =  $item['num']*$item['oldprice'] - $itemSumPrice;
// 							$youhui = $youhui + $detailYouhui[$item['id']]['value'];
						}else{
							$itemSumPrice = $item['num'] * $item['price'];
							$fullgiveModel->add($item['id'], $item['price'], $item['num'], $item['cate_id']);
							//查看满减商品
// 							$fullcutwhere['type'] = 0;
// 							$fullcutwhere['condition'] = $item['id'];;
// 							$fullcutwhere['is_close'] = 0;
// 							$fullcutwhere['start_time'][] = array('elt', date('Y-m-d'));
// 							$fullcutwhere['end_time'][] = array('egt', date('Y-m-d'));
// 							$fullcut = D ( 'promotion' )->where($fullcutwhere)->order('id desc')->find();
// 							if($fullcut && $fullcut['reserve'] <= $itemSumPrice){
// 								//Log::write('fullcut>>>price>>>'.$itemSumPrice);
// 								$item['award_type'] =  $fullcut['award_type'];
// 								$item['reserve'] =  $fullcut['reserve'];
// 								if($fullcut['award_type'] == 1){
// 									//Log::write('fullcut>>>true');
// 									$iscoupon = true;
// 									$youhui = $youhui + $fullcut['award_value'];
// 									$itemSumPrice = $itemSumPrice - $fullcut['award_value'];
// 									$item['youhui'] =  $fullcut['award_value'];
// 									//调整-记录优惠信息
// 									$detailYouhui[$item['id']]['type']  =  11; //满减优惠
// 									$detailYouhui[$item['id']]['value'] =  $fullcut['award_value'];
// 								}else if($fullcut['award_type'] == 2){
// 									$item['youhui'] =  $fullcut['award_value'];
// 								}
// 							}
						}
					}
					if($iscoupon === false ){//没有参与优惠活动的商品价格
						//Log::write("false");
						//Log::write("itemSumPrice>>>".$itemSumPrice);
						if($info && ($info['itemtypename'] == 0 || in_array($item['id'], $itemids))){//优惠码为所商品或者指定了该商品
							$needcouponPrice = $needcouponPrice + $itemSumPrice;
						}else{
							if($item['mbdscnt'] == 1){//加入会员折扣
								$needcardPrice = $needcardPrice + $itemSumPrice;
							}
						}
					}
					//先计算所有金额，后面再减掉满足优惠码条件的商品金额

					$sumPrice = $sumPrice+ $itemSumPrice;
				}
			}
			//满减计算
			$fullgive = $fullgiveModel->givegife();
			if(!empty($fullgive)){
				if($fullgive['tt'] == 1){
					$sumPrice = $sumPrice - $fullgive['award_value'];
					$youhui = $youhui + $fullgive['award_value'];
					foreach ($fullgive['items'] as $key => $item){
						//记录优惠信息
// 						$detailYouhui[$item['item_id']]['type']  =  11; //满减优惠
// 						$detailYouhui[$item['item_id']]['value'] =  $fullgive['award_value']/count($fullgive['items']);
					}
				}
				if($fullgive['award_type'] == 2){
					$data['give'] = $fullgive['award_value'];
				}
				if($fullgive['award_type'] == 3){
					$data['give_coupon'] = $fullgive['award_value'];
				}
			}
			$this->assign ( 'fullgive', $fullgive );
			
			//开始计算后面相应的优惠
			$couponPrice = 0.00;
			if( $useCode && $useCode == 1 && $coupon && $info && $needcouponPrice){
				$order_coupon_data['orderID'] = $dingdanhao;
				$order_coupon_data['couponID'] = $coupon;
				$order_coupon->data ( $order_coupon_data )->add ();
				if($info['coupon_type'] == 'Z'){
					$couponPrice = $needcouponPrice * (1 - $info['discount'] / 10);
					$data['coupon'] = $coupon;
					$data['couponprice'] = $couponPrice;
					$info['valid'] = 1;
					$discount->save($info);
					//Log::write('discount>>>random>>>'.$coupon);
					//Log::write('discount>>>price>>>'.$couponPrice);
				}elseif ($info['coupon_type'] == 'M'){
					$full = $info ['full'];
					if ($needcouponPrice < $full) //不能使用满减券
					{
						$this->ajaxReturn(0,'订单总金额未到' . $full . ',请继续选择商品!' );
					} else {
						$couponPrice = $info ['cut'];
						$data['coupon'] = $coupon;
						$data['couponprice'] = $couponPrice;
						$info['valid'] = 1;
						$full_cut->save($info);
						//Log::write('fullcut>>>random>>>'.$coupon);
						//Log::write('fullcut>>>price>>>>'.$couponPrice);
					}
				}elseif ($info['coupon_type'] == 'B'){
					$surplus = $needcouponPrice - $info ['surplus'];
					//礼金卡使用完
					if ($surplus >= 0) {
						$couponPrice =  $info ['surplus']; //优惠金额为礼金卡所有金额
						$info['surplus'] = 0;
						$info['valid'] = '1';
					} else {
						//礼金卡有余额
						$info['surplus'] = $info ['surplus'] - $needcouponPrice;
						$info['valid'] = '0';
						$couponPrice = $needcouponPrice ; //礼金卡未使用完，则优惠金额为满足条件的商品金额
					}
					$data['coupon'] = $coupon;
					$data['couponprice'] = $couponPrice;
					$gift->save ( $info );
					//Log::write('gift>>>random>>>'.$coupon);
					//Log::write('gift>>>price>>>>'.$couponPrice);
				}
			}
			//订单总金额=商品的原价(包括各种活动 价)-满足优惠码条件的商品金额-会员折扣金额-推荐码金额+运费
			//满足优惠码条件的商品金额
			$orderSumPrice = $sumPrice -$couponPrice;

			//支付方式
			$supportmetho = $this->_post ( 'payment_id', 'trim' );
			$data['supportmetho'] = $supportmetho;
			//Log::write('supportmetho>>>'.$supportmetho);
			if ($supportmetho = 4) {
				//会员折扣金额
				$cardinfo = $_SESSION ['card_info'] [$uid];
				if($cardinfo &&  $cardinfo ['discount'] > 0){
					$adjustments = $cardinfo ['discount'] != null ? $cardinfo ['discount']: 0 ;
					$cardCouponPrice = $needcardPrice - $needcardPrice * $adjustments; // 折扣金额
					$data['adjustment'] = $adjustments;
					$data['total_adjustment'] = $cardCouponPrice;
				}
				$orderSumPrice = $orderSumPrice - $cardCouponPrice;
			}

			// 配送方式
			$distributeId = $this->_post ( 'distributeId', 'trim' ); // 1物流配送 2到店自提
			//Log::write('freetype>>>'.$distributeId);
			$data ['freetype'] = $distributeId;
			// 时间范围
			$beginTime = $this->_post ( 'distributpayeTimeStart', 'trim' );
			$endTime = $this->_post ( 'distributeTimeEnd', 'trim' );
			$data ['begin_time'] = strtotime ( $beginTime );
			$data ['end_time'] = strtotime ( $endTime );
			//Log::write('tiem>>startTime>>'.$beginTime.'>>>endTime>>>'.$endTime);
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
				//Log::write('freesum>>>'.$freesum);
			}
			//满足推荐码减后总金额
			//Log::write('promotionPrice>>>>>'.$promotionPrice);
			$promotionPrice = $this->_post('promotionPrice','trim');//使用推荐码获减金额
			$orderSumPrice = $orderSumPrice -$promotionPrice;
			//Log::write('orderSumPrice>>>>>'.$orderSumPrice);
			//订单总金额
			$orderSumPrice = $orderSumPrice + $freesum;
			$data ['order_sumPrice'] = $orderSumPrice;
			$data['freeprice'] = $freesum; //运费保存
			//优惠金额
			$data ['youhui'] = $youhui + $couponPrice + $cardCouponPrice + $promotionPrice;
			
			//保存推荐码到订单
			$data['promotionCode'] = $this->_post('promotionCode','trim');
			
			if (! empty ( $postscript )){ // 卖家留言
				$data ['note'] = $postscript;
			}

			$data ['orderId'] = $dingdanhao; // 订单号
			$data ['add_time'] = $time; // 添加时间
			$data ['goods_sumPrice'] = $this->cart->getOldPrice (); // 商品总额
			$data ['userId'] = $this->visitor->info ['id']; // 用户ID
			$data ['userName'] = $this->visitor->info ['username']; // 用户名
			
			
			if (isset($address_options)) {
				//Log::write('address_options>>>>>>'.$address_options);
				if ($address_options == 0) {
					
					$consignee = $this->_post ( 'getName', 'trim' ); // 真实姓名
					$sheng = $this->_post ( 'sheng', 'trim' ); // 省
					$shi = $this->_post ( 'shi', 'trim' ); // 市
					$qu = $this->_post ( 'qu', 'trim' ); // 区
					$address = $this->_post ( 'getAddressDetail', 'trim' ); // 详细地址
					$phone_mob = $this->_post ( 'getPhone', 'trim' ); // 电话号码
					$save_address = '222'; // 默认所有新增保存地址
					$data ['address_name'] = $consignee; // 收货人姓名
					$data ['mobile'] = $phone_mob; // 电话号码
					$data ['address'] = $sheng . $shi . $qu . $address; // 地址
				
					if ($save_address){ // 保存地址
						//Log::write('saveAddress>>>>true');
						$add_address ['uid'] = $this->visitor->info ['id']; // 用户id
						$add_address ['consignee'] = $consignee;
						$add_address ['address'] = $address;
						$add_address ['mobile'] = $phone_mob;
						$add_address ['sheng'] = $sheng;
						$add_address ['shi'] = $shi;
						$add_address ['qu'] = $qu;
						$add_address ['shopid'] = $this->shopId;
						$add_address ['moren'] = 0; //默认地址
						$user_address->data ( $add_address )->add ();
					}
				
				} else {
					
					$address = $user_address->where ( 'uid=' . $this->visitor->info ['id'] )->find ( $address_options ); //取到地址
					$data ['address_name'] = $address ['consignee']; //收货人姓名
					$data ['mobile'] = $address ['mobile']; //电话号码
					$data ['address'] = $address ['sheng'] . $address ['shi'] . $address ['qu'] . $address ['address']; //地址
				}
			} 

			//发票信息
			$isInvoice = $this->_post ( 'isInvoice', 'trim' );
			$data['invoice'] = $isInvoice;
			$enterpriceAddress = $this->_post ( 'enterpriceAddress', 'trim' );
			$data['invoice_title'] = $enterpriceAddress;



// 			$data ['coupon'] = $coupon;
			$data ['uid'] = $this->shopId;
			$data ['random'] = $random;
			$shipcode = time();//date("Ymd"). $this->getSerialnumber();
			
			$shipcode = substr($shipcode, 2);
			$data ['shipcode'] = $shipcode;
			$data ['distTime'] =  $this->_post ( 'distTime', 'trim' );;//配送时间
			if ($orderid = $item_order->data ( $data )->add ()) {//添加订单成功
				//Log::write('add_Order>>>>ture');
				$orders ['orderId'] = $dingdanhao;
				foreach ( $_SESSION [$this->cart->cartKey] as $item ) {
					if($item['status'] != 0){
						continue;
					}
					if($item['types'] == 1){
						$prefix = C(DB_PREFIX);
						//保存组合商品
						$assembleItems  = M('assemble_item')->field('i.id,b.barcodeid,i.price as price,b.goodsId,b.title as name,b.img')
							->join($prefix.'item i ON i.id='.$prefix.'assemble_item.item_id')
							->join($prefix.'item_base b ON i.baseid=b.id')
							->where(array('assemble_id' => $item['id']))->select();
						//Log::write('assembleItems----'.M('assemble_item')->getLastSql(), 'DEBUG');
						foreach ( $assembleItems as $assemble ) {
							$item_goods->where ( 'id=' . $assemble ['id'] )->setDec ( 'goods_stock', $item ['num'] );
							////Log::write('itemgoo--' . $item_goods->getLastSql(), 'DEBUG');
							//Log::write('id>>>>' .$assemble ['id'], 'DEBUG');
							$orders ['itemId'] = $assemble ['id']; //商品ID
							$orders ['title'] = $assemble ['name']; //商品名称
							$orders ['img'] = $assemble ['img']; //商品图片
							$orders ['price'] = $assemble ['price']; //商品价格
							$orders ['oldprice'] = $assemble ['price']; //
							$orders ['quantity'] = $item ['num']; //购买数量
							////Log::write('goodsid--'  . $item ['goodsId']);
							$orders ['goodsId'] = $assemble ['goodsId']; //
							$orders ['coupon_type'] = 12; //组合商品优惠
							$orders ['couponval'] =($item ['oldprice']*$item ['num']-$item ['price']*$item ['num'])/count($assembleItems);
							$orders ['barcodeid'] = $assemble ['barcodeId']; //
							$order_detail->data ( $orders )->add ();
						}
					}else{
						$item_goods->where ( 'id=' . $item ['id'] )->setDec ( 'goods_stock', $item ['num'] );
						////Log::write('itemgoo--' . $item_goods->getLastSql(), 'DEBUG');
						//Log::write('id>>>>' .$item ['id'], 'DEBUG');
						$orders ['itemId'] = $item ['id']; //商品ID
						$orders ['title'] = $item ['name']; //商品名称
						$orders ['img'] = $item ['img']; //商品图片
						$orders ['price'] = $item ['price']; //商品价格
						$orders ['oldprice'] = $item ['oldprice']; //
						$orders ['quantity'] = $item ['num']; //购买数量
						////Log::write('goodsid--'  . $item ['goodsId']);
						$orders ['goodsId'] = $item ['goodsId']; //
						$orders ['coupon_type'] = $detailYouhui[$item ['id']]['type']; //
						$orders ['couponval'] =   $detailYouhui[$item ['id']]['value'];
						$orders ['barcodeid'] = $item ['barcodeid']; // 
						
						$order_detail->data ( $orders )->add ();
						//Log::write($order_detail->getLastSql(), 'DEBUG'); 
					}
				}
				//记录订单记录
				D('orders_log')->addLog($orders['orderId'], 1, $data['userId']);
				//$cart->clear (); //清空购物车
 				$this->cart->clear_has_jiesuan();
				$this->assign ( 'orderid', $orderid ); //订单ID
				$this->assign ( 'dingdanhao', $dingdanhao ); //订单号
				$this->assign ( 'order_sumPrice', $data ['order_sumPrice'] );
				//$this->display();
				//$this->goPay($payment_id, $orderid, $dingdanhao, $data ['order_sumPrice']);
				$url = U ( 'order/pay', array (
						'orderId' => $dingdanhao
				) );
				$data = array (
						'status' => 1,
						'url' => $url
				);
				//Log::write('orderId_url>>>'.$url);
				$this->ajaxReturn ( 1, '', $data );
			} else {
				//Log::write('order_failed');
				$this->ajaxReturn(0, '生成订单失败!' );
			}
		} else if (isset ( $_GET ['orderId'] )) {
			$item_order = M ( 'item_order' );
			$orderId = $_GET ['orderId']; //订单号
			//Log::write("orderid:" .  $orderId);
			$orders = $item_order->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $orderId )->find ();
			if (! is_array ( $orders ))
				$this->_404 ();
			//查询余额
			$sumPrice = $orders ['order_sumPrice'];
			$payment_id =  $orders ['supportmetho'];
			if ($sumPrice == 0 ) { //如果金额为0 直接结束订单
				//Log::write('sumprice>>>0');
				$data ['status'] = 2;
				$data ['support_time'] = time ();
				$paymethod_id = date ( "Y-m-dH-i-s" );
				$paymethod_id = str_replace ( "-", "", $paymethod_id );
				$data['support_id'] = $paymethod_id;
				if (M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $orderId )->data ( $data )->save ()) {
					$this->frist_order($orderId);//首单操作
					$this->redirect ( 'my/index' );
				} else {
					$this->error ( '操作失败!' );
				}
			} else {
				$this->goPay($payment_id, $orderId, $orderId, $sumPrice);
			}
		} else {
			$this->redirect ( 'my/index' );
		}
	}
	
	private function bindCounpon($urlid, $userid){
		$item_info = M('coupons_url')->field('url,shopid,itemtypename,typeid')-> where('urlid='.$urlid)->find();
		if(!$item_info){
			return;
		}
		$url = $item_info['url'];
		$shopid = $item_info['shopid'];
		$u = substr($url,strpos($url,'urlid'));
		$params = explode("/", $u);
		$data = array();
		foreach ($params as $key => $val){
			if($key % 2 == 0){
				$k = $params[$key];
				$v = $params[$key+1];
				$data[$k] = $v;
			}
		}
		if(!empty($data['discount'])){
			$where = array(
					'urlid'=>$data['urlid'],					//优惠券urlid
					'userid'=>$userid,//用户id号
					'discount'=>$data['discount'],
					'begintime' => $data['begintime'],			//活动开始时间
					'expiretime' =>$data['expiretime'] 		//活动结束时间
			);
		}
		if(!empty($data['full'])||!empty($data['cut'])){
			$where = array(
					'urlid'=>$_GET['urlid'],					//优惠券urlid
					'userid'=>$userid,//用户id号
					'full'=>$data['full'],
					'cut'=>$data['cut'],
					'begintime' =>$data['begintime'] ,			//活动开始时间
					'expiretime' =>$data['expiretime'] ,		//活动结束时间
			);
		}
		if(!empty($data['gift'])){
			$where = array(
					'urlid'=>$data['urlid'],					//优惠券urlid
					'userid'=>$userid,//用户id号
					'gift'=>$data['gift'],
					'begintime' => $data['begintime'],			//活动开始时间
					'expiretime' => $data['expiretime'],		//活动结束时间
			);
		}
		if(!empty($data['discount'])){
			$where_arr = array('discount'=>$data['discount'],'urlid'=>$data['urlid']);
			$cardID  = M('coupons_card')->where($where_arr)->max('cardid');
		}
		if(!empty($data['full'])||!empty($data['cut'])){
			$cardID  = M('coupons_card')->where('full='.$data['full'].' and cut='.$data['cut'])->max('cardid');
		}
		if(!empty($data['gift'])){
			$cardID  = M('coupons_card')->where('gift='.$data['gift'])->max('cardid');
		}
		$userinfo= $userid; // 用户id
		$discount= $data['discount'];			//折扣
		$full    = $data['full'];				//满
		$cut     = $data['cut'];				//减
		$gift    = $data['gift'];				//礼金
		$share	 = $data['share'];
		$cardID += $data['id'];
		$begintime =$data['begintime'];
		$expiretime = $data['expiretime'];
		$urlid = $data['urlid'];
		
		$infos = M('coupons_card')->select();
		$exclude_codes_array = array();
		if($infos){
			$x = 0;
			foreach ($infos as $r => $infos){
				$exclude_codes_array[$x] = $infos['random'];
				$x ++;
			}
		}
		$random = $this->generate_promotion_code(1,$exclude_codes_array,8);
		//Log::write('<<<<urlid>>>'.$urlid);
		if($cardID < $share || $cardID == $share){
			$dataList[]=array(
					'urlid'=>$urlid,
					'cardid'=>$cardID,
					'userid'=>$userinfo,
					'shopid'=>$shopid,
					'discount'=>$discount,				//type:折扣
					'full'=>$full,						//type:满减
					'cut'=>$cut,						//type:满减
					'gift'=>$gift,						//type:礼金
					'surplus'=>$gift,
					'begintime' => $begintime,			//活动开始时间
					'expiretime' => $expiretime,		//活动结束时间
					'share'=>$share,					//活动优惠券总数量
					'random' => "H".$random [0],		//优惠券码
					'createtime'=>date("Y-m-d H-m-s") , //创建时间
					//指定商品字段
					'itemtypename'=>$item_info['itemtypename'], //是否指定商品1是0否
					'typeid'=>$item_info['typeid']		  //指定商品编码
	
			);
			M('coupons_card')->addAll($dataList);
			if($discount != null){
				$code_list =array(
						'type'=>Z,
						'random'=>"H".$random[0],					//兑换码
						'uid'=>$userinfo,					//领取人员
						'receive_time'=>date("Y-m-d H:i:s"),//领取时间
						'shopid'=>$shopid
				);
				M('coupons_code')->add($code_list);
				//Log::write(M('coupons_code')->getLastSql(), 'DEBUG');
				M('discount')->addAll($dataList);
			}else if ($full != null || $cut !=null){
				$code_list =array(
						'type'=>M,
						'random'=>"H".$random[0],					//兑换码
						'uid'=>$userinfo,						//领取人员
						'receive_time'=>date("Y-m-d H:i:s"),//领取时间
						'shopid'=>$shopid
				);
				M('coupons_code')->add($code_list);
				M('full_cut')->addAll($dataList);
			}elseif ($gift != null){
				$code_list =array(
						'type'=>B,
						'random'=>"H".$random[0],					//兑换码
						'uid'=>$userinfo,						//领取人员
						'receive_time'=>date("Y-m-d H:i:s"),//领取时间
						'shopid'=>$shopid
				);
				M('coupons_code')->add($code_list);
				M('gift')->addAll($dataList);
			}
			//Log::write('绑定优惠券成功');
		}else {
			//Log::write('绑定优惠券失败');
		}
	}
	
	/**
	 * 首单生成推荐码
	 * 存在推荐码用户返还代金券
	 * @param unknown $orderId
	 */  
    private function frist_order($orderId){
    	
    	$promo_info = M('shop_promo')->where(array('shopid'=>$this->shopId))->find();
    	if(!empty($promo_info) && ($promo_info['status'] == 1)){
    		//Log::write('创建推荐码，返送代金券');
    		$gift_promo_time  = '+'.$promo_info['gift_time'].'  Month'; // 返送券使用时间'+1 Month'
    		$money_promo_time = '+'.$promo_info['time'].'  Month';      //推荐码使用时间'+3 Month'
    		$gift 			  = $promo_info['gift'];					//5元代金券
    		//判断是否首单
    		$where = array(
    				'uid'=>$this->shopId,					//店铺id
    				'userId'=>$this->visitor->info ['id'],	//用户id
    				'status'=>2								//订单状态待发货
    		);
    		//查询支付时间最早的订单号
    		$order_nums = M('item_order')->field('orderId,promotionCode')->where($where)->order('support_time desc')->find();
    		$userInfo_my =  M('promo_code')->where(array('uid'=>$this->shopId,'userId'=>$this->visitor->info['id']))->find();
    		
    		//判断订单号是否一致
    		if($order_nums['orderId'] == $orderId){
    			//判断推荐是否已经生成过
    			if(empty($userInfo_my)){
    				//Log::write('frist_promo');
    			//生成优惠码
    			$infos = M('promo_code')->select();
    			$exclude_codes_array = array();
    			if($infos){
    				$x = 0;
    				foreach($infos as $r => $info){
    					$exclude_codes_array[$x] = $info['random'];
    					$x ++;
    				}
    			}
    			$random = $this->generate_promotion_code (1,$exclude_codes_array,9);
    			$dataList = array (
    					'uid'=>$this->shopId,					//店铺id
    					'userId'=>$this->visitor->info ['id'],	//用户id
    					'begintime' => time(),//开始时间
    					'expiretime' => strtotime(''.$money_promo_time, time()),//结束时间
    					'random' => "S".$random [0],
    					'createtime'=>date("Y-m-d H:i:s") ,//创建时间
    			);
    			M('promo_code')->add($dataList);
    			//推送优惠码消息给用户
    					$promo_code = "S".$random [0];
     					$this->__WxpromoSMS($promo_code);
    			}
    			//推送抵扣券给用户
    			if(!empty($order_nums['promotionCode'])){
    				//Log::write('pull_gift');
    				$ere = array('random'=>$order_nums['promotionCode']);
    				$userInfo = M('promo_code')->field('uid,userId')->where($ere)->find();
    				$infos = M('gift')->select();
    				$exclude_codes_array = array();
    				if($infos){
    					$x = 0;
    					foreach($infos as $r => $info){
    						$exclude_codes_array[$x] = $info['random'];
    						$x ++;
    					}
    				}
    				$random_code = $this->generate_promotion_code (1,$exclude_codes_array,8);
    				$dataList = array (
    						'gift' => $gift,
    						'random' => $gift . $random_code [0],
    						'surplus' => $gift,
    						'begintime' => date('Y-m-d H:i:s',time()),//开始时间
    						'expiretime' => date('Y-m-d H:i:s',strtotime(''.$gift_promo_time, time())),//代金券使用时间一个为一个月
    						'shopid'=>$this->shopId,					//店铺id
    						'createtime'=>date("Y-m-d H-m-s") ,//创建时间
    				);
    				M('gift')->add($dataList);
    				//抵金券兑换
    				$uid = $userInfo['userId'];
    				$dataList=array(
    						'type'=>'B',
    						'random'=>$gift . $random_code [0],	//兑换码
    						'uid'=>$uid,						//领取人员
    						'receive_time'=>date("Y-m-d H:i:s"),//领取时间
    						'shopid'=>$this->shopId
    				);
    				M('coupons_code')->add($dataList);
    				
    			}
    		}
    	  }
    	}
	/**
	 * 调用微信模板消息推送邀请码
	 */
 	private function __WxpromoSMS($promo_code){
 		//Log::write('promo_code>>>',$promo_code);
 		$uid  = $this->visitor->info ['id'];
 		$openid =  $_SESSION[$uid . 'openid']['openid'];
 		$nickname =  $_SESSION[$uid . 'openid']['nickname'];
 		$msg = array ();
 		$msg ['touser'] = $openid;
 		$msg ['template_id'] = 'jArkSy_kEbAzPN_lnYVLLLUiG9OMye7KyozbKJf6skc';
 		$msg ['topcolor'] = '#FF0000';
 		$msg ['url'] = C('pin_baseurl').'/order/checkPromo/promo_code/'. $promo_code;
 		$data = array ();
 		$user = array ();
 		$user ['value'] = $nickname . ',您获得一张推荐码!';
 		$user ['color'] = '#173177';
 		
 		$ordernum = array (
 				'value' =>  $promo_code,
 				'color' => '#173177'
 		);
 		
 		$priceArr = array (
 				'value' =>  $promo_code,
 				'color' => '#173177'
 		);
 		
 		$ordertime = array (
 				'value' => date ( "Y/m/d" ) . ' ' . date ( "h:i:sa" ),
 				'color' => '#173177'
 		);
 		
 		$data ['first'] = $user;
 		$data ['orderno'] = $ordernum;
 		$data ['amount'] = $priceArr;
 		
 		$data ['remark'] = '如您还有疑问，请留言';
 		$msg ['data'] = $data;
 		
 		if (false === $shopconf = F ( 'shop' )) {
 			$conf = D ( 'shop' )->shop_cache ();
 		}
 		C ( F ( 'shop' ) );//获取缓存
 		$sid = '45834d07b3c99f45c51058f35409ab';
 		
 		$wxInterface = new WeixinInterface ( $sid );
 		$data = $wxInterface->sendTemplate ( $sid, $msg );
 		
 	}

 	public function checkPromo(){
 		$promo_code = $_GET ['promo_code'];
 		! $promo_code && $this->_404 ();
 		$promo_info_user  	= M('promo_code')->where(array('random'=>$promo_code))->find();	//获取推荐码信息
 		$list['random'] 	= $promo_code;														//推荐码
 		$list['begintime'] 	= $promo_info_user['begintime'];									//推荐码开始时间
 		$list['expiretime'] = $promo_info_user['expiretime'];								//推荐码结束时间
 		$promo_info_arr 	= M('shop')->where(array('uid'=>$promo_info_user['uid']))->find();	//获取店铺信息
 		$list['name'] 		= $promo_info_arr['name'];											//可用店铺名称
 		$list['address'] 	= $promo_info_arr['city_names'].$promo_info_arr['address'];			//可用店铺地址
 		$this->assign('list',$list);
 		$this->display();
 	}
 	
	
	public function end() {
		if (IS_POST) {
			$payment_id = $_POST ['payment_id'];
			$orderid = $_POST ['orderid'];
			$dingdanhao = $_POST ['dingdanhao'];
			$item_order = M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->find ();
			! $item_order && $this->_404 ();

			//判断商品价格，为0时直接完成订单
			$order_sumPrice = $item_order ['order_sumPrice'];
			if ($order_sumPrice > 0) {
				if ($payment_id == 2){ //货到付款
					$data ['status'] = 2;
					$data ['supportmetho'] = 2;
					$data ['support_time'] = time ();
					if (M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->data ( $data )->save ()) {
						$this->redirect ( 'user/index' );
					} else {
						$this->error ( '操作失败!' );
					}
				} elseif ($payment_id == 1){ //支付宝
					$data ['supportmetho'] = 1;
					if (M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->data ( $data )->save ()) {
						$alipay = M ( 'alipay' )->find ();
						echo "<script>location.href='wapapli/alipayapi.php?WIDseller_email=" . $alipay ['alipayname'] . "&WIDout_trade_no=" . $dingdanhao . "&WIDsubject=" . $dingdanhao . "&WIDtotal_fee=" . $item_order ['order_sumPrice'] . "'</script>";
					} else {
						$this->error ( '操作失败!' );
					}
				} elseif ($payment_id == 3){ //wechat
					$data ['supportmetho'] = 3;
					if (M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->data ( $data )->save ()) {
					}
					$body = "佳鲜商城";
					$this->weixinPay ( $body, $dingdanhao, $item_order ['order_sumPrice'] );
				} elseif ($payment_id == 4){ //零钱
					$data ['supportmetho'] = 4;
					if (M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->data ( $data )->save ()) {
					}
					$this->balancePay($dingdanhao, $item_order ['order_sumPrice'] );
				}else {
					$this->error ( '操作失败!' );
				}
			} else {
				$data ['status'] = 2;
				$data ['support_time'] = time ();
				$paymethod_id = date ( "Y-m-dH-i-s" );
				$paymethod_id = str_replace ( "-", "", $paymethod_id );
				$paymethod_id .= rand ( 2000, 3000 );
				$data ['support_id'] = $paymethod_id;
				$this->sendPayTemp($dingdanhao, $item_order['order_sumPrice']);
				if (M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->data ( $data )->save ()) {
					//推荐码推送
					$this->frist_order($dingdanhao);
					//记录订单操作信息
					D('orders_log')->addLog($dingdanhao, 2, $this->visitor->info ['id']);

					$this->success( '下单成功' );
					$this->redirect ( '/user/index/status/2' );
				} else {
					$this->error ( '操作失败!' );
				}
			}


		}
	}

	public function getFree($type) {


		import ( 'Think.ORG.Cart' );
		$cart = new Cart ();
		$money = 0;
		$items = M ( 'item' );
		$method = array (1 => 'pingyou', 2 => 'kuaidi', 3 => 'ems' );
		foreach ( $_SESSION [$this->cart->cartKey] as $item ) {
			$free = $items->field ( 'free,pingyou,kuaidi,ems' )->where ( 'free=2' )->find ( $item ['id'] );
			if (is_array ( $free )) {
				$money += $free [$method [$type]];
			}
		}
		return $money;
	}

	/**
	 * 微信支付先用ajax返回数据
	 * */
	public function ajaxWeixinapy($body, $out_trade_no, $total_fee) {
		$url = U ( 'order/pay', array (
				'orderId' => $out_trade_no
		) );
		$data = array (
				'status' => 1,
				'url' => $url
		);
		//Log::write($url, 'DEBUG');
		if (IS_AJAX) {
			$this->ajaxReturn ( 1, '', $data );
		} else {
			//$this->display ( 'weixinPay' );
		}
	}

	/**
	 * $body 商品描述
	 * $out_trade_no 商户订单号
	 * total_fee 金额
	 * */
	public function weixinPay($body, $out_trade_no, $total_fee) {
		//Log::write ( "weixinPay---".$body.'---'.$out_trade_no.'---'.$total_fee );

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
					$js_api_call_url = U('order/weixinPay');
					$rurl = C('pin_baseurl') .  $js_api_call_url . '?body=' . $body . '&out_trade_no=' . $out_trade_no . '&total_fee=' . $total_fee ;
					//Log::write('rurl----'.$rurl);
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
			//Log::write('the param create prepay_id, openid:'. $openid . '-body:' . $body.'--out_trade_no:' .$out_trade_no. '--total_fee:' .$total_fee );
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
			$noUrl =  'http://jx.i-lz.cn/sync/updateorder/payBack';//'http://jx.i-lz.cn/?g=sync&m=updateorder&a=payBack';//C('pin_baseurl') . U('order/payBack');
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
		/* } catch ( Exception $e ) {
			//Log::write ( $e );
			$this->redirect ( 'user/index' );
		} */
	}


	public function  validCoupon($info){
	if ($info && $info ['valid'] == 1) {
				// //Log::write("dazhe:". $info['discount']);
				// //Log::write("原 price:". $data ['order_sumPrice']);
				// //Log::write("zhe price:". $data ['order_sumPrice'] * $info['discount'] / 10);
		    $nowdate = time();
			if ($info && $nowdate < $info ['begintime'] && $nowdate < $info ['begintime']) {
				$this->error ( '优惠未开始或已过期!' );
			} else {
				$data ['order_sumPrice'] = $this->cart->getPrice () * $info ['discount'] / 10;
			}
		} else {
			$this->error ( '优惠券已使用!' );
		}
	}
	public function getLogistics() {
		$typeCom = $_GET ["com"]; // 快递公司
		$typeNu = $_GET ["nu"]; // 快递单号
		Vendor ( 'KuaiDi100.KuaiDi100PubHelper' );
		$logistics = new \KuaiDi100PubHelper ();

		$logistics->setTypeNu ( $typeNu );
		$logistics->setTypeCom ( $typeCom );
		$data = $logistics->kuaidiInfo ();
		print_r ( $data . '<br/>' );
		exit ();
	}

	/**
	 * 微信支付之后js结果.
	 * payBack为微信服务主动发送的支付结果
	 */
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
							$item_order = M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->find ();
							! $item_order && $this->_404 ();
							$data ['status'] = 2;
							$data ['support_time'] = time ();
							$data['transaction_id'] = $transaction_id; //微信支付订单号
							if (M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->data ( $data )->save ()) {
								//支付成功推送推荐号和抵金券
								$this->frist_order($dingdanhao);
								//记录订单操作信息
								D('orders_log')->addLog($dingdanhao, 2, $this->visitor->info ['id']);
								if(!empty($item_order['give_coupon'])){//绑定相应优惠券
									$this->bindCounpon($item_order['give_coupon'], $item_order['userId']);
								}
								$this->sendPayTemp($dingdanhao, $item_order['order_sumPrice']);
								$this->sendEmailForOrder($dingdanhao);
								$this->sendOrderDuanMsg($item_order['mobile'], $dingdanhao, $item_order['shipcode']);
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
	 * 支付
	 * @param unknown $payment_id
	 * @param unknown $orderid
	 * @param unknown $dingdanhao
	 * @param unknown $order_sumPrice
	 */
	public function goPay($payment_id, $orderid, $dingdanhao, $order_sumPrice){
		//Log::write('goPay>>' .  $payment_id . '--' .$orderid . '--' .  $dingdanhao . '--' .$order_sumPrice);
		$item_order = M ( 'item_order' )->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->find ();
		! $item_order && $this->_404 ();
		if ($payment_id == 2){ //货到付款
			//Log::write('deliveryPay');
				$this->redirect ( 'user/index' );
		} elseif ($payment_id == 1){ //支付宝
			//Log::write('aliPay');
			$data ['supportmetho'] = 1;
			$alipay = M ( 'alipay' )->find ();
			echo "<script>location.href='wapapli/alipayapi.php?WIDseller_email=" . $alipay ['alipayname'] . "&WIDout_trade_no=" . $dingdanhao . "&WIDsubject=" . $dingdanhao . "&WIDtotal_fee=" . $item_order ['order_sumPrice'] . "'</script>";
		} elseif ($payment_id == 3){ //wechat
			//Log::write('wechatPay');
			$body = "佳鲜商城";
			$this->weixinPay( $body, $dingdanhao, $item_order ['order_sumPrice'] );
		} elseif ($payment_id == 4) { //零钱支付
			//Log::write('changePay');
			$sumPrice = $item_order ['order_sumPrice'];
			$this->getUserInfoWebservice($sumPrice);
			$this->balancePay($dingdanhao, $sumPrice);
		} else  { // 如果支付方式为空，则默认进行微信支付
			//Log::write('支付方式为空，默认微信支付');
			$body = "佳鲜商城";
			$this->weixinPay( $body, $dingdanhao, $item_order ['order_sumPrice'] );
		}
	}

	/**
	 *优惠码减金额
	 **/
	public function ajaxCoupon(){
		$useCode = $this->_request('isyouhuicode', 'trim');
		$coupon = $this->_request ('coupon', 'trim');
		$vip = $this->_request ('payId', 'trim');
		$distributeId = $this->_request('distributeId', 'trim');
		$promotionPrice = $this->_request('promotionPrice','trim');//推荐码减少金额
		$data = $this->calculateOrder($useCode, $coupon, $distributeId, $vip,$promotionPrice);
		if (IS_AJAX) {
			echo json_encode ( $data );
		} else {
			$this->error('优惠未开始或已过期!');
		}
	}


	/**
	 * 好友兑换推荐码
	 */
	public function referral_code(){
		$status = 0;
		$msg = '网络超时，请刷新！';
		//好友兑换使用
		$where_arr = array('random'=>$_POST['code'],'uid'=>$this->shopId);
		//查询推荐码是否存在
		$promo = M('promo_code')->where($where_arr)->find();
		$promo_money = M('shop_promo')->field('money')->where(array('shopid'=>$this->shopId))->find();
		if(!empty($promo)){
			if($promo['expiretime']<time()){
				$status = 0;
				$msg = '推荐码已过期。';
				$data = array('time'=>$promo['expiretime'],'random'=>$_POST['code'],'uid'=>$this->shopId,'userId'=>$this->visitor->info['id']);
				goto end;
			}
			$where = array(
					'uid'=>$this->shopId,					//店铺id
					'userId'=>$this->visitor->info ['id'],	//用户id
			);
			$orders = M('item_order')->where($where)->find();
			//好友首单创建优惠码
			if(empty($orders)){
				//用户得到抵扣券
				$status = 1;
				$msg = '推荐码输入成功。';
				$data = array('breaks_nums'=>$promo_money['money'],'random'=>$_POST['code'],'uid'=>$this->shopId,'userId'=>$this->visitor->info['id']);//减免金额
				goto end;
			}else{
				$status = 0;
				$msg = '此订单非首单，本优推荐码不可用.';
				$data = array('random'=>$_POST['code'],'uid'=>$this->shopId,'userId'=>$this->visitor->info['id']);
				goto end;
			}
		}else {
			$status = 0;
			$msg = '推荐码不存在.';
			$data = array('random'=>$_POST['code'],'uid'=>$this->shopId,'userId'=>$this->visitor->info['id']);
			goto end;
		}
		end:;
		$this->ajaxReturn($status,$msg,$data);
	}
	/**
	 * 检测余额
	 * */
	public function ajaxBalance(){
		//$sumPrice = $this->_request('sumPrice');
		$sumPrice = $this->cart->getPrice();
		$useCode = $this->_request('isyouhuicode', 'trim');
		$coupon = $this->_request ('coupon', 'trim');
		$vip = $this->_request ('payId', 'trim');
		$distributeId = $this->_request('distributeId', 'trim');
		$promotionPrice = $this->_request('promotionPrice','trim');//推荐码减少金额
		$data = $this->getUserInfoWebservice($sumPrice, $useCode, $coupon,  $distributeId, $vip,$promotionPrice);
		if (IS_AJAX) {
			echo json_encode ( $data );
		} else {
			$this->error('优惠未开始或已过期!');
		}
	}


	public function balancePay($dingdanhao, $sumPrice){
		//Log::write ( 'balancePay...', 'INFO' );
		$uid = $this->visitor->info ['id'];
		// 折扣
		$card_info = $_SESSION ['card_info'] [$uid];
		$adjustment = $card_info ['discount'] != null ? $card_info ['discount']: 0 ;
		/* dump($adjustment);
		exit(); */
		$actual = $sumPrice ; // 实际的  $sumPrice * $adjustment
		$total_adjustment =0; // 折扣金额

		$this->assign ( 'adjustment', $adjustment );
		$this->assign ( 'dingdanhao', $dingdanhao );
		$this->assign ( 'sumPrice', $sumPrice );
		$this->assign ( 'actual', sprintf ( "%.2f", $actual ) );
		$this->assign ( 'total_adjustment', sprintf ( "%.2f", $total_adjustment ) );

		$url = U ( 'order/balancePay', array (
				'adjustment' => $adjustment,
				'dingdanhao' => $dingdanhao,
				'sumPrice' => $sumPrice,
				'actual' => sprintf ( "%.2f", $actual ),
				'total_adjustment' => sprintf ( "%.2f", $total_adjustment )
		) );
		$data = array (
				'status' => 1,
				'url' => $url
		);
		// $this->display ( 'balancePay' );
		if (IS_AJAX) {
			$this->ajaxReturn ( 1, '', $data );
		} else {
			$this->display ( 'balancePay' );
		}

	}

	/**
	 * 会员卡支付
	 * @param unknown $adjustment
	 * @param unknown $actual
	 * @param unknown $total_adjustment
	 * @param unknown $dingdanhao
	 */
	public function balanceCommit($adjustment,$actual, $total_adjustment,$dingdanhao){

		//Log::write('balanceCommit:' .  $adjustment . $actual. $total_adjustment . $dingdanhao);
		$uid  = $this->visitor->info ['id'];
		if(IS_POST) {
			//查询会员信息
			$userMod = M('card');
			$item_order = M('item_order');
			$user = $userMod->where('id = '. $uid)->field('card_num,public_store')->find();
			//会员卡号
			$userid = 'wxtest';
			$card_num = $user['card_num'];
			//$password = $this->_request('p1'). $this->_request('p2') . $this->_request('p3'). $this->_request('p4'). $this->_request('p5'). $this->_request('p6');
			$password = $this->_request('password');
			$public_store = $user['public_store'];
			$card_info = $_SESSION['card_info'][$uid];

			//Log::write('password user:' . $password .'--card_info--'.$card_info['password'],'DEBUG');
			 if ($password == $card_info['password']) {
				$shipcode = date("Ymd"). $this->getSerialnumber();
				//Log::write('shipcode:' . $shipcode);
				//$tradeCode = $userid . $shipcode;
				// 余额支付修改订单号 
				$tradeCode = $dingdanhao; 
				Vendor ( 'FrdifService.HttpService' );
				$client =  new \HttpService();

				$client -> setCmdid('1011');
				//$queryParam = $tradeCode . ',' . $card_num . ',' . $public_store . ',' . $actual; //. $password . ','
				 $param = array(
					 '_param0' =>$tradeCode,
					 '_param1' => $card_num,
					 '_param2' => $public_store,
					 '_param3' => $actual
				 );
				$client->setParam($param);
				//Log::write('queryParam' . $queryParam,'DEBUG');
				$result= $client->getInfo();
				Log::write("the pay result---"  . $result);
				//支付成功
				if ($result != -1) {
					$resultDataInfo = explode('	', $result);
					Log::write("the pay result 2---"  . $resultDataInfo[0]);
					if ($resultDataInfo[0] == 1) {
						$data ['status'] = 2;
						$data ['support_time'] = time ();
						$paymethod_id = date ( "Y-m-dH-i-s" );
						$paymethod_id = str_replace ( "-", "", $paymethod_id );
						$paymethod_id .= rand ( 2000, 3000 );
						$data ['support_id'] = $paymethod_id;

						$data ['order_sumPrice'] = $actual;
						$data ['adjustment'] = $adjustment;
						$data ['total_adjustment'] = $total_adjustment;
						/* $data ['shipcode'] = $shipcode; */
						$item_order->where('orderId = ' .  $dingdanhao) ->save($data);
						
						$item_order_data = $item_order->where ( 'userId=' . $this->visitor->info ['id'] . ' and orderId=' . $dingdanhao )->find ();
						! $item_order_data && $this->_404 ();
						try {
							$this->sendOrderDuanMsg($item_order_data['mobile'],
								$dingdanhao, $item_order_data['shipcode']);
						} catch (Exception $e) {
							Log::write('sendOrderDuanMsg err..', 'ERR');
						}

						try{
							$this->sendPayTemp($dingdanhao, $actual);
						} catch (Exception $e) {
							Log::write('sendPayTemp err..', 'ERR');
						}

						try{
							$this->frist_order($dingdanhao);

						} catch (Exception $e) {
							Log::write('frist_order err..', 'ERR');
						}

						try{
							$this->sendEmailForOrder($dingdanhao);
						} catch (Exception $e) {
							Log::write('sendEmailForOrder err..', 'ERR');
						}

						$client->setCmdid ( '1010' );
						$param = array(
							'_param0' =>3,
							'_param1' => $card_num
						);
						$client->setParam ($param);
						Log::write ( 'get account info...' );
						$resultInfo = $client->getInfo();
						$i = 0;
						if ($resultInfo  != - 1) {
							$resultDataInfoAcc = explode ( '	', $resultInfo  );
							$datauser ['card_score'] = $card_score = $resultDataInfoAcc [2];
							$datauser ['card_balance'] = $card_balance = $resultDataInfoAcc [3];
							$datauser ['id'] = $uid;
							$userMod->save ( $datauser );
						} else {
							Log::write ( 'get info error:' . $resultInfo );
						}

						if(!empty($item_order_data['give_coupon'])){//绑定相应优惠券
							$this->bindCounpon($item_order_data['give_coupon'], $item_order_data['userId']);
						}

						if (IS_AJAX) {
						  $this->ajaxReturn(1, '支付成功');
						} else {
						   $this->success("支付成功", U('my/index', array('status'=>2)));
						}

					} else {
					    if (IS_AJAX) {
						  $this->ajaxReturn(0, '支付失败');
						} else {
						   $this->error("支付失败", U('my/index', array('status'=>2)));
						}
					}
				  } else {
				     if (IS_AJAX) {
						  $this->ajaxReturn(0, '支付失败');
						} else {
						   $this->error("支付失败", U('my/index', array('status'=>2)));
						}
				  }
				} else {
				  if (IS_AJAX) {
				    $this->ajaxReturn(2, '会员密码错误，请重新输入');
				  } else {
				    $this->error("会员密码错误，请重新输入", U('my/index', array('status'=>2)));
				  }
				}
				//支付成功之后重新查询用户信息，更新积分和余额
				//模板通知
			 } else {
			  if (IS_AJAX) {
				  $this->ajaxReturn(0, '支付失败');
			  } else {
				  $this->error("支付失败", U('my/index', array('status'=>2)));
			  }
			}
		}


	public function getSerialnumber(){
		//Log::write('getSerialnumber>>', 'INFO');
		$Mod = M('order_serialnumber');
		$date = date("Ymd");
		$info = $Mod->where('date = ' . $date)->find();
		//echo $info['serial_number'];
		if ($info) {
			$serial_number = $info['serial_number'];
			//echo '--' . ($serial_number+1) . '</br>';
			$data['serial_number'] = ($serial_number+1);
			$data['id']  = $info['id'];
			$Mod->save($data);
			//echo  '-==-' . $this->addZero($serial_number+1) . '</br>';
			return $this->addZero($serial_number+1);
		} else {
			//echo '++++';
			$data['date'] = $date;
			$serial_number = '1';
			$data['serial_number'] = $serial_number;
			$Mod->add($data);
			//echo  $this->addZero($serial_number);
			return $this->addZero($serial_number);
		}
	}
	//不够5位前面加 0
	public function addZero($serial_number){
		//Log::write('addZero>>', 'INFO');
		//Log::write('serial_number>>' .  $serial_number, 'INFO');
		$var=sprintf("%05d", $serial_number);//生成4位数，不足前面补0
		//Log::write('var>>'. $var, 'INFO');
		return $var;//结果为0002
	}


	/**
	 * 推送订单消息时需要订单号和订单金额；推荐码用于首单推送；抵扣券单号用于首单使用推荐码时推送
	 * @param unknown $dingdanhao  订单号
	 * @param unknown $price	        订单金额
	 * @param unknown $promo_code  推荐码
	 * @param unknown $random	        抵扣券单号
	 */
	function sendPayTemp($dingdanhao, $price  ){

		if (false === $shopconf = F ( 'shop' )) {
			$conf = D ( 'shop' )->shop_cache ();
		}
		C ( F ( 'shop' ) );//获取缓存

		$uid  = $this->visitor->info ['id'];
		$openid =  $_SESSION[$uid . 'openid']['openid'];
		$nickname =  $_SESSION[$uid . 'openid']['nickname'];

		$shopModel = D('shop');
		$sid = $this->shopId;
		//Log::write ( "sid>>" . $sid );
		$pid =  $shopModel->getUidForP($sid);

		//session ($uid . 'openid', $openid);
		$template_id =  M('templ')->field('val')-> where(array('uid'=>$pid,'key'=>1))->find();//获取template_id
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

		$wxInterface = new WeixinInterface ( $pid );
 		$data = $wxInterface->sendTemplate ( $pid, $msg );
		return $data;
	}
	

	/**
	 * 从webService 读取用户信息，保存到session
	 * */
	public function getUserInfoWebservice($sumPrice, $useCode, $coupon,  $distributeId, $vip,$promotionPrice){
		//Log::write('UserInfo----'.$sumPrice.'----'.$useCode.'----'.$coupon.'---'.$distributeId.'----'.$vip.'---'.$promotionPrice);
		$uid = $this->visitor->info['id'];
		//检测有没有绑定
		$User = D('user');
		$Card = D('card');
		$bindInfo= $User->queryBindStatus($uid);
		if ($bindInfo['bindstatus'] == 1) {
			$queryType = '3';
			//从会员系统读取会员信息
			$queryParam = $bindInfo['card_num'];
			Vendor ( 'FrdifService.HttpService' );
			$client =  new \HttpService();

			$client->setCmdid ( '1010' );

			$param = array(
				'_param0' =>$queryType,
				'_param1' => $queryParam
			);
			$client->setParam ($param );
			Log::write('begin get info from crm--', Log::DEBUG);
			$resultInfo = $client->getInfo();
			$i = 0;
			Log::write('end get info from crm--', Log::DEBUG);
			Log::write('rtn----'.$resultInfo);
			if ($resultInfo != - 1) {
				$resultDataInfo = explode('	', $resultInfo);
				$data ['card_score'] = $card_score = $resultDataInfo [2];
				$data ['card_balance'] = $card_balance = $resultDataInfo [3];
				$data ['password'] = $password = $resultDataInfo[4];
				//查询会员折扣
				$cardInfo = $Card->field('discount')->where('card_num=' . $bindInfo['card_num'])->find();
				$data ['discount'] = $discount = $cardInfo['discount'];
				//Log::write('$discount : ' . $discount . ' $sumPrice:' .  $sumPrice);
				if ($sumPrice  *  $discount  >  $card_balance) {
					$data = array ('status' => 0 );
					$data['err'] = '余额不足';
				} else {
					//保存密码等信息
					//Log::write('save session ' );
					$_SESSION['card_info'][$uid] = $data;
					$te = $_SESSION['card_info'][$uid];
					$data = array ('status' => 1 );
					$data['err'] = '';
					//$data['order_sumPrice'] = sprintf ("%.3f", $sumPrice * $discount );
					//修改以前的会员支付逻辑，当选择会员绑定时，先同步一次会员信息，再计算会员的折扣
					$data = $this->calculateOrder($useCode, $coupon,  $distributeId, $vip);
				}
				/* if ($data['status'] == 1) {
					if ($_SESSION['coupon'] !=0  ) {
						$data['order_sumPrice'] = $data['order_sumPrice'] - $_SESSION['coupon']['couponPrice'];
					}
				} */
			
			} else {
				Log::write('get info error:' . $resultInfo['errormsg'] , Log::DEBUG);
					$data = array ('status' => 3);
					$data['err'] = '系统异常';
			}
		} else {
			Log::write('get info error:nobind'  , Log::DEBUG);
			$data = array ('status' => 2 );
			$data['err'] = '你还没有绑定任何会员卡，不能用余额支付。';
			$data['bindurl'] = U('usercard/activateMenbership');
		}
		Log::write('return  data status--' . $data['status']);
		return $data;
	}


	public function vippwd(){
		$this->display();
	}


	/**
	 * 计算订单的价格 限时抢购，组合，满减，优惠券，优惠码，会员折扣
	 * @author jimyan
	 * @date 2015-11-28 13:15:36
	 * @parameters useCode 是否使用优惠码
	 * @parameters coupon 优惠码
	 * @parameters vip 会员优惠  是否享受会员优惠，默认不享受 0  不享受，4 会员卡支付
	 * @param$promotionPrice 推荐码金额
	 * @parameters $distributeId 送货方式，默认为  佳鲜配送
	 * @return 订单总价 分别优惠 订单实际要支付金额
	 *
	 * */
	public function calculateOrder($useCode, $coupon, $distributeId = 1, $vip = 0 ,$promotionPrice)
	{
		$discount = M('discount');
		$full_cut = M('full_cut');
		$gift = M('gift');
		$order_coupon = M('order_coupon');
		$data['status'] = 1;

		$uid = $this->visitor->info ['id']; //用户ID
		if ($useCode && $useCode == 1 && $coupon) {
			$nowdate = strtotime(date("Y-m-d H-i-s"));
			$coupon_type = substr($coupon, 0, 1); //优惠码类型
			if ("Z" == $coupon_type) {//折扣码
				$where['random'] = substr($coupon, 1);
				$info = $discount->where($where)->find();
				if ($info && $info['valid'] == 0) {
					if ($info && $nowdate < strtotime($info['begintime']) && $nowdate > strtotime($info['expiretime'])) {
						$data['status'] = 0;
						$data['msg'] = '优惠码未开始或已过期!';
						return $data;
					} else {
						$info['coupon_type'] = "Z";
					}
				} else {
					return $data;
				}
			} elseif ("M" == $coupon_type) {//满减码
				$where ['random'] = substr($coupon, 1);
				$info = $full_cut->where($where)->find();
				if ($info && $info ['valid'] == 0) {
					if ($info && $nowdate < strtotime($info['begintime']) && $nowdate > strtotime($info['expiretime'])) {
						$data['status'] = 0;
						$data['msg'] = '优惠码未开始或已过期!';
						return $data;
					} else {
						$info['coupon_type'] = "M";
					}
				} else {
					$data['status'] = 0;
					$data['msg'] = '优惠码已使用!';
					return $data;
				}
			} elseif ("B" == $coupon_type) {
				$where['random'] = substr($coupon, 1);
				$info = $gift->where($where)->find();

				if ($info && $info ['valid'] == 0) {
					if ($info && $nowdate < strtotime($info['begintime']) && $nowdate > strtotime($info['expiretime'])) {
						$data['status'] = 0;
						$data['err'] = '优惠码未开始或已过期!';
						return $data;
					} else {
						$info['coupon_type'] = "B";
					}
				} else {
					$data['status'] = 0;
					$data['err'] = '优惠码已使用!';
					return $data;
				}
			} else {
				$data['status'] = 0;
				$data['err'] = '优惠码格式错误!';
			    return $data;
			}

			if ($info['itemtypename'] == 1) {//指定商品
				$itemids = array();
				$couponstypeid = $info['typeid'];
				if ($couponstypeid) {//查询对应的商品
					$couponslist = M('coupons')->where(array('typeid' => $couponstypeid))->select();
					foreach ($couponslist as $couponss) {
						$itemids[] = $couponss['itemid'];
					}
				}
			}
		}


		//满减信息
		$fullgiveModel = new FullGive();
		$fullgivewhere['uid'] = $this->shopId;
		$fullgivewhere['is_close'] = 0;
		$fullgivewhere['start_time'][] = array('elt', date('Y-m-d'));
		$fullgivewhere['end_time'][] = array('egt', date('Y-m-d'));
		$fullgiveList = D ( 'promotion_give' )->where($fullgivewhere)->order('id desc')->select();
		$catefullgive = array();
		foreach ($fullgiveList as $fullgive){
			$fullgiveModel->addFullGive($fullgive, $this->shopId);
			if($fullgive['good_type'] == 2){
				$catefullgive[] = $fullgive;
			}
		}
		foreach ($catefullgive as $fullgive){//子分类
			$cates = D('item_cate')->get_child_ids($fullgive['good_value']);
			foreach ($cates as $cate){
				$fullgive['good_value'] = $cate;
				$fullgiveModel->addFullGive($fullgive, $this->shopId);
			}
		}
		//满减信息--end
		

		//重新计算总价
		$sumPrice = 0.00;
		$youhui = 0.00;
		//需要优惠的商品价格
		$needcouponPrice = 0.00;
		//会员折扣金额
		$needcardPrice = 0.00;
		foreach ($_SESSION[$this->cart->cartKey] as $item) {
			if ($item['id'] == 0) {
				continue;
			}
			if ($item['status'] == 0) {
				$iscoupon = false;
				//组合商品
				if ($item['types'] == 1) {
					$assemblewhere['id'] = $item['id'];
					$assemblewhere['start_time'][] = array('elt', date('Y-m-d'));
					$assemblewhere['end_time'][] = array('egt', date('Y-m-d'));
					$assemble = M('assemble')->where($assemblewhere)->find();
					if ($assemble) {
						$iscoupon = true;
						$item['oldprice'] = $assemble['original_price'];
						$item['price'] = $assemble['assemble_price'];
						$item['title'] = $assemble['name'];
						$itemSumPrice = $item['num'] * $assemble['assemble_price'];
					} else {
						$item['disable'] = 1;
					}
					$prefix = C(DB_PREFIX);
					//获取组合商品列表
					$assembleItems = M('assemble_item')->field($prefix . 'item.*')
							->join($prefix . 'item ON ' . $prefix . 'item.id=' . $prefix . 'assemble_item.item_id')
							->where(array('assemble_id' => $item['id']))->select();
					$item['items'] = $assembleItems;
				} else {
					//查看是否是限时抢购商品
					$limitbuywhere['type'] = 1;
					$limitbuywhere['condition'] = $item['id'];
					$limitbuywhere['is_close'] = 0;
					$limitbuywhere['start_time'][] = array('elt', date('Y-m-d'));
					$limitbuywhere['end_time'][] = array('egt', date('Y-m-d'));
					$limitbuy = D('promotion')->where($limitbuywhere)->order('id desc')->find();
					if ($limitbuy) {
						$iscoupon = true;
						$itemSumPrice = $item['num'] * $limitbuy['award_value'];
						$item['oldprice'] = $item['price'];
					} else {
						$itemSumPrice = $item['num'] * $item['price'];
						$fullgiveModel->add($item['id'], $item['price'], $item['num'], $item['cate_id']);
// 						//查看满减商品
// 						$fullcutwhere['type'] = 0;
// 						$fullcutwhere['condition'] = $item['id'];;
// 						$fullcutwhere['is_close'] = 0;
// 						$fullcutwhere['start_time'][] = array('elt', date('Y-m-d'));
// 						$fullcutwhere['end_time'][] = array('egt', date('Y-m-d'));
// 						$fullcut = D('promotion')->where($fullcutwhere)->order('id desc')->find();
// 						if ($fullcut && $fullcut['reserve'] <= $itemSumPrice) {
// 							$item['award_type'] = $fullcut['award_type'];
// 							$item['reserve'] = $fullcut['reserve'];
// 							if ($fullcut['award_type'] == 1) {
// 								$iscoupon = true;
// 								$youhui = $youhui + $fullcut['award_value'];
// 								$itemSumPrice = $itemSumPrice - $fullcut['award_value'];
// 								$item['youhui'] = $fullcut['award_value'];
// 							} else if ($fullcut['award_type'] == 2) {
// 								$item['youhui'] = $fullcut['award_value'];
// 							}
// 						}
					}
				}
				if ($iscoupon === false) {//没有参与优惠活动的商品价格
					//Log::write("false");
					//Log::write("itemSumPrice>>>" . $itemSumPrice);
					if ($info && ($info['itemtypename'] == 0 || in_array($item['id'], $itemids))) {//优惠码为所商品或者指定了该商品
						$needcouponPrice = $needcouponPrice + $itemSumPrice;
					} else {
						if ($item['mbdscnt'] == 1) {//加入会员折扣
							$needcardPrice = $needcardPrice + $itemSumPrice;
						}
					}
				}
				//先计算所有金额，后面再减掉满足优惠码条件的商品金额

				$sumPrice = $sumPrice + $itemSumPrice;
			}
		}
		
		//满减计算
		$fullgive = $fullgiveModel->givegife();
		if(!empty($fullgive)){
			if($fullgive['tt'] == 1){
				$sumPrice = $sumPrice - $fullgive['award_value'];
				$youhui = $youhui + $fullgive['award_value'];
				foreach ($fullgive['items'] as $item){
					//记录优惠信息
					$detailYouhui[$item['id']]['type']  =  11; //满减优惠
					$detailYouhui[$item['id']]['value'] =  $fullgive['award_value']/count($fullgive['items']);
				}
			}
			if($fullgive['award_type'] == 2){
				$data['give'] = $fullgive['award_value'];
			}
			if($fullgive['award_type'] == 3){
				$data['give_coupon'] = $fullgive['award_value'];
			}
		}
		$this->assign ( 'fullgive', $fullgive );
		
		//开始计算后面相应的优惠
		$couponPrice = 0.00;
		if ($useCode && $useCode == 1 && $coupon && $info) {
			$order_coupon_data['couponID'] = $coupon;
			$order_coupon->data($order_coupon_data)->add();
			if ($info['coupon_type'] == 'Z') {
				$couponPrice = $needcouponPrice * (1 - $info['discount'] / 10);
				$data['coupon'] = $coupon;
				$data['couponprice'] = $couponPrice;
				$info['valid'] = 1;
				if (!IS_AJAX) {
					$discount->save($info);
				}

			} elseif ($info['coupon_type'] == 'M') {
				$full = $info ['full'];
				if ($needcouponPrice < $full) //不能使用满减券
				{
					$data['status'] = 0;
					$data['err'] = '订单总金额未到' . $full . ',请继续选择商品!';
					return $data;
				} else {
					$couponPrice = $info ['cut'];
					$data['coupon'] = $coupon;
					$data['couponprice'] = $couponPrice;
					$info['valid'] = 1;
					if (!IS_AJAX) {
						$full_cut->save($info);
					}
				}
			} elseif ($info['coupon_type'] == 'B') {
				$surplus = $needcouponPrice - $info ['surplus'];
				//礼金卡使用完
				if ($surplus >= 0) {
					$couponPrice = $info ['surplus']; //礼金卡使用完代表优惠金额为礼金卡所有金额
					$info['surplus'] = 0;
					$info['valid'] = '1';
				} else {
					//礼金卡有余额
					$info['surplus'] = $info ['surplus'] - $needcouponPrice;
					$info['valid'] = '0';
					$couponPrice = $needcouponPrice; //礼金卡未使用完，则优惠金额为满足条件的商品金额
				}
				$data['coupon'] = $coupon;
				$data['couponprice'] = $couponPrice;
				if (!IS_AJAX) {
					$gift->save($info);
				}
			}
		}
		//订单总金额=商品的原价(包括各种活动 价)-满足优惠码条件的商品金额-会员折扣金额+运费
		//满足优惠码条件的商品金额
		$orderSumPrice = $sumPrice - $couponPrice;

		//会员优惠
		if ($vip == 4) {
			//会员折扣金额
			$cardinfo = $_SESSION ['card_info'] [$uid];
			if ($cardinfo && $cardinfo ['discount'] > 0) {
				$adjustments = $cardinfo ['discount'] != null ? $cardinfo ['discount'] : 0;
				$cardCouponPrice = $needcardPrice - $needcardPrice * $adjustments; // 折扣金额
			}
			$orderSumPrice = $orderSumPrice - $cardCouponPrice;
		}
		//推荐码优惠价格
		$orderSumPrice = $orderSumPrice - $promotionPrice;
		//获取运费
		$uid = $this->shopId;
		$shop = M('shop')->where(array('uid' => $uid))->find();
		if ($shop) {
			$freepost = $shop['delivery_freemoney'];
			if ($distributeId == 2 || $freepost <= $sumPrice) { //到店自提 或者费用大于包邮
				$freesum = 0.00;
			} else {
				$freesum = $shop['delivery_money'];
			}
		}
		$orderSumPrice = $orderSumPrice + $freesum;
		
		
		//$needcardPrice   sprintf ("%.3f", $sumPrice * $discount );
		$data ['order_sumPrice'] =   sprintf ("%.3f",  $orderSumPrice);  
		$data ['youhui'] = sprintf ("%.3f", $youhui + $couponPrice + $cardCouponPrice + $promotionPrice);
		$data ['goods_sumPrice'] = sprintf ("%.3f", $this->cart->getPrice()); //商品总额
		$data ['freesum'] =  sprintf ("%.3f",$freesum);
		$data ['promotionPrice'] =  sprintf ("%.3f",$promotionPrice);
		return $data;
	}

	public function test() {
		$shopModel = D('shop');
		$shopModel->shop_cache();
		$sid = '90baa3315883e80ce926c4034fc811';
		//Log::write ( "sid>>" . $sid );
		$pid =  $shopModel->getUidForP($sid); //获取子店对应的父店对应的id，即对应的appid等
		$appid =C ( 'spconf_appid_'. $pid) ;//; ''
		$appsecret = C ( 'spconf_appsecret_'. $pid);//'';
		//Log::write ( "appid>>" . $appid );
		//Log::write ( "appsecret>>" . $appsecret );
		$mchid = C ( 'spconf_wxpayone_'. $pid);//;//''; ''
		$key = C ( 'spconf_wxpaytwo_'. $pid) ;//''
		//Log::write ( "mchid>>" . $mchid );
		//Log::write ( "key>>" . $key );
		dump($pid);
	}

	/**
	 * @param int $no_of_codes//定义一个int类型的参数 用来确定生成多少个优惠码
	 * @param array $exclude_codes_array//定义一个exclude_codes_array类型的数组
	 * @param int $code_length //定义一个code_length的参数来确定优惠码的长度
	 * @return array//返回数组
	 */
	function generate_promotion_code($no_of_codes, $exclude_codes_array = '', $code_length = 4) {
		$characters = "0123456789";
		$promotion_codes = array (); //这个数组用来接收生成的优惠码
		for($j = 0; $j < $no_of_codes; $j ++) {
			$code = "";
			for($i = 0; $i < $code_length; $i ++) {
				$code .= $characters [mt_rand ( 0, strlen ( $characters ) - 1 )];
			}
			//如果生成的4位随机数不再我们定义的$promotion_codes函数里面
			if (! in_array ( $code, $promotion_codes )) {
				if (is_array ( $exclude_codes_array )) //
				{
					if (! in_array ( $code, $exclude_codes_array )) //排除已经使用的优惠码
					{
						$promotion_codes [$j] = $code; //将生成的新优惠码赋值给promotion_codes数组
					} else {
						$j --;
					}
				} else {
					$promotion_codes [$j] = $code; //将优惠码赋值给数组
				}
			} else {
				$j --;
			}
		}
		return $promotion_codes;
	}
	
	/***
	 * 订单成功之后发送邮件
	 * */
	 public function sendEmailForOrder($ordernum) {
	     $sid = $this->shopId; //'01233a79c297477d554051b1bb3650';
	     $content  = C('pin_order_email_tpl');
	     $body = str_replace('{ordernum}',$ordernum,$content);
	     $subject  = C('pin_order_email_subject');
	     $emailList = M('admin')->field('email')->where(array('uid'=>$sid))->select();
	     $to[] = array();
	     foreach ($emailList as $email) {
	         $to = $email['email'];
	     }
	 	 $result = $this->_mail_queue($to, $subject, $body, $priority = 1);
	 	 D('mail_queue')->send();
	 }
     /**
      * 订单
      * */
	 public function sendOrderDuanMsg($phone,$orderid, $shipcode) {
	   //Log::write('send msg info ...' . $phone . '--' . '--'. $orderid . '---' . $shipcode);
	 	$corpid='302062';
	 	$codenum=date('His');
	 	$content  = C('pin_order_msg_tpl');
	 	$content = str_replace('{order}',$orderid,$content);
	 	$content = str_replace('{shipcode}',$shipcode,$content);
	 	$sms_send=smsSend($phone, $corpid,$content);
	 	//Log::write('send result---' . $sms_send['Count']);
	 	if($sms_send['Count'] == '-102'){
	 		
	 	}else {
	 		
	 	}
	 	
	 }
	 
	 public  function  test1 () {
	 	$noUrl =  C('pin_baseurl') . U('order/payBack');
	 	dump($noUrl);
	 	$isdebug = $_GET['debug'];
	 	dump($isdebug);
	 	dump($isdebug == 'true');
	 }
	 
	 
	 public function youhui() {
	 	$coupons_mod = M('coupons_code');
		$uid = $this->visitor->info ['id'];
		$time = date ( "Y-m-d", time () );
		$discoutlist = $coupons_mod->join ( 'LEFT JOIN weixin_discount
			         ON weixin_discount.random = weixin_coupons_code.random' )->where ( 'uid = ' . "'$uid'" . '  and type= ' . "'Z'" . ' and expiretime >= ' . "'$time'" . ' and valid=0' . '  and  weixin_coupons_code.shopid = ' . "'$this->shopId'" )->select ();
		//Log::write ( $coupons_mod->getLastSql () );
		$full_cutlist = $coupons_mod->join ( 'LEFT JOIN weixin_full_cut
			    ON weixin_full_cut.random = weixin_coupons_code.random' )->where ( 'uid = ' . "'$uid'" . '  and type= ' . "'M'" . ' and expiretime >= ' . "'$time'" . ' and valid=0' . '  and  weixin_coupons_code.shopid = ' . "'$this->shopId'" )->select ();
		//Log::write ( $coupons_mod->getLastSql () );
		$giftlist = $coupons_mod->join ( 'LEFT JOIN weixin_gift
			    ON weixin_gift.random = weixin_coupons_code.random' )->where ( 'uid = ' . "'$uid'" . '  and type= ' . "'B'" . ' and expiretime >= ' . "'$time'" . ' and valid=0' . '  and  weixin_coupons_code.shopid = ' . "'$this->shopId'" )->select ();
		//Log::write ( $coupons_mod->getLastSql () );
	    //dump($full_cutlist);
		//dump($giftlist);
		//dump($discoutlist); 
		$cartlist =  $_SESSION [$this->cart->cartKey];
		/* foreach ( $_SESSION [$this->cart->cartKey] as $item ) { //购物车商品列表
			//Log::write('item_status'. '-----'.$item['status']);
			if($item['status'] == 0){  //勾选的商品 */
				foreach ($full_cutlist as $info) {
					if ($info ['itemtypename'] == 1) { // 指定商品
						$couponstypeid = $info ['typeid'];
						if ($couponstypeid) { // 查询对应的商品
							$couponslist = M ( 'coupons' )->where ( array ('typeid' => $couponstypeid) )->select (); //可以用此券 商品id list
							foreach ( $couponslist as $couponss ) {
								foreach ( $cartlist as $item ) {
									if($item['status'] == 0){
										if ($couponss ['itemid'] == $item['id'] ) {
											$full_cut [] = $info;
										} else {
											$nofull_cut [] = $info;
										}
									}
								}
							}
						}
					} else {
						$full_cut [] = $info;
					}
				}
				
				foreach ($giftlist as $info) {
					if ($info ['itemtypename'] == 1) { // 指定商品
						$couponstypeid = $info ['typeid'];
						if ($couponstypeid) { // 查询对应的商品
							$couponslist = M ( 'coupons' )->where ( array ('typeid' => $couponstypeid) )->select (); //可以用此券 商品id list
							foreach ( $couponslist as $couponss ) {
							foreach ( $cartlist as $item ) {
									if($item['status'] == 0){
										if ($couponss ['itemid'] == $item['id'] ) {
											$gift[] = $info;
										} else {
											$nogift [] = $info;
										}
									}
								}
							}
						}
					} else {
						$gift [] = $info;
					}
				}
				
				foreach ($discoutlist as $info) {
					//dump($info);
					if ($info ['itemtypename'] == 1) { // 指定商品
						$couponstypeid = $info ['typeid'];
						if ($couponstypeid) { // 查询对应的商品
							$couponslist = M ( 'coupons' )->where ( array ('typeid' => $couponstypeid) )->select (); //可以用此券 商品id list
							foreach ( $couponslist as $couponss ) {
							foreach ( $cartlist as $item ) {
									if($item['status'] == 0){
										if ($couponss ['itemid'] == $item['id'] ) {
											$discout [] = $info;
										} else {
											$nodiscout [] = $info;
										}
									}
								}
							}
						} 
					} else {
							$discout [] = $info;
						}
				}
		
		
	  //dump($discout);
	  //dump($nodiscout);
	  
		if ($full_cut == null && $gift ==null && $discout  == null
				&& $nofull_cut== null && $nogift==null && $nodiscout==null) {
					$this->assign('haveyouhui','1');
		}
		$this->assign('full_cut',$full_cut);
		$this->assign('gift',$gift);
		$this->assign('discout',$discout);
		
		$this->assign('nofull_cut',$nofull_cut);
		$this->assign('nogift',$nogift);
		$this->assign('nodiscout',$nodiscout);
		$this->display();
	}
	
	/**
	 * 优惠码兑换优惠券
	 *  */
	public function Coupon_exchange(){
		//exit();
		if(IS_POST){
			$re_code = 0;
			$re_msg = '网络超时。';
			$code_mod=M('coupons_code');
			$uid=$this->visitor->info['id'];
			$code=$_POST['code'];
			$type=substr($code,0,1);
			$code=trim($code,$type);
			$dataList[]=array(
					'type'=>$type,
					'random'=>$code,					//兑换码
					'uid'=>$uid,						//领取人员
					'receive_time'=>date("Y-m-d H:i:s"),//领取时间
					'shopid'=>$this->shopId
			);
			//判断优惠券是否存在或过期
			if(($type != 'Z') && ($type != 'M') && ($type != 'B')){
				$re_code = 0;
				$re_msg = '此兑换码不存在，请确认后再试。';
				goto end;
	
			}else {
				if($type == 'Z'){
					$dis_time = M('discount')->field('expiretime')->where('random = ' ."'$code'" . 'and shopid ='."'$this->shopId'")->find();
					if(empty($dis_time)){
						$re_code = 0;
						$re_msg = '此优惠码无法在本店兑换。';
						goto end;
					}else if(strtotime($dis_time['expiretime'])<time()){
						$re_code = 0;
						$re_msg = '此优惠码已过期。';
						goto end;
					}
				}
				if($type == 'M'){
					$dis_time = M('full_cut')->field('expiretime')->where('random = ' ."'$code'" . 'and shopid ='."'$this->shopId'")->find();
					if(empty($dis_time)){
						$re_code = 0;
						$re_msg = '此优惠码无法在本店兑换。';
						goto end;
					}else if(strtotime($dis_time['expiretime'])<time()){
						$re_code = 0;
						$re_msg = '此优惠码已过期。';
						goto end;
					}
				}
				if($type == 'B'){
					$dis_time = M('gift')->field('expiretime')->where('random = ' ."'$code'" . 'and shopid ='."'$this->shopId'")->find();
					if(empty($dis_time)){
						$re_code = 0;
						$re_msg = '此优惠码无法在本店兑换。';
						goto end;
					}else if(strtotime($dis_time['expiretime'])<time()){
						$re_code = 0;
						$re_msg = '此优惠码已过期。';
						goto end;
					}
				}
					
			}
			$codeid=$code_mod->field('random')->where('random=' ."'$code'" .' and shopid ='."'$this->shopId'")->select();
			if($codeid){
				$re_code = 0;
				$re_msg = '此优惠码已被兑换，请更换优惠码再次兑换。';
				goto end;
			}else {
				$code_mod->addAll($dataList);
				$re_code = 1;
				$re_msg = '优惠码兑换成功。';
				goto end;
			}
			end:;
			$this->ajaxReturn($re_code,$re_msg);
		}else {
			$this->display();
		}
	}
}
