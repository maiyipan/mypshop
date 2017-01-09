<?php
class updateorderAction extends baseAction{
	
	
	/**
	 * 	关闭订单
	 */
	public function closeOrder(){
		//1小时订单为支付，状态由（待付款）转换为（关闭）
		$order_info = M('item_order')-> field('add_time,orderId')
		->where(array('status'=>1))->select();
		//Log::write(M('item_order')->getLastSql(), 'DEBUG');
		foreach($order_info as $key=>$val){
			$now_time = time();
			$order_time = strtotime('+' . C('pin_close_order_time').'  hours' , $val['add_time']);//订单时间$val['add_time']
			//Log::write('order---'.$order_time);
			$time_info =  ($now_time - $order_time);
			if($time_info > 0){
				$save_info['status'] = 5;
				$save_info ['closemsg'] = '订单超时未支付';
				M('item_order')->where(array('orderId'=>$val['orderId']))
				->save($save_info);
				//Log::write(M('item_order')->getLastSql(), 'DEBUG');
			}
		}
	}
	/**
	 *	订单确认 
	 */
	public function confirmGoods(){
		//7天订单未确认收货，状态由（待收货）转换为（完成）
		$order_info = M('item_order')-> field('add_time,orderId')->where(array('status'=>3))->select();
		//Log::write(M('item_order')->getLastSql(), 'DEBUG');
		//转态由待收货改变为确认收货
		foreach($order_info as $key=>$val){
			$now_time = time();
			$order_time = strtotime('+' . C('pin_confirm_goods_time').'  days' , $val['add_time']);//订单时间$val['add_time']
			//Log::write('order---'.$order_time);
			$time_info =  ($now_time - $order_time);
			if($time_info > 0){
				$save_info['status'] = 4;
				M('item_order')->where(array('orderId'=>$val['orderId']))->save($save_info);
				//Log::write(M('item_order')->getLastSql(), 'DEBUG');
			}
		}
	}
	/**
	 * 微信退款状态查询
	 */
	public function wechatrefundstatus(){
		$order_info = M('item_order')->field('orderId,transaction_id,refund_id,uid')->where('status = 7  and  transaction_id IS NOT NULL and supportmetho = 3')->select();
		//Log::write(M('item_order')->getLastSql(), 'DEBUG');
		$refundStatus = array();
		foreach ($order_info as $key=>$var_info){
			$sid = $var_info['uid'];
			$transaction_id = $var_info['transaction_id'];
			$refund_id = $var_info['refund_id'];
			$out_trade_no = $var_info['orderId'];
			$out_refund_no = date ( "Y-m-dH-i-s" );
			$out_refund_no = str_replace ( "-", "", $out_trade_no );
			$out_refund_no .= rand ( 1000, 2000 );
			$wechatrefundQuery= new WeixinPay();
			$refundStatus[] = $wechatrefundQuery->wexinRefundQuery($transaction_id, $out_trade_no, $out_refund_no, $refund_id, $sid);
		}
		foreach ($refundStatus as $key=>$refund_order_info){
		  if($refund_order_info['return_code'] == 'SUCCESS'){
		    switch ($refund_order_info['refund_status_0']){
		      case SUCCESS: print ('微信单号'.$refund_order_info['transaction_id'].'退款成功</br>');
		      //Log::write('微信单号'.$refund_order_info['transaction_id'].'退款成功');
		      $data['fee_status'] ='退款成功';
		      $data['status'] ='9';
		      break;
		      case FAIL: print ('微信单号'.$refund_order_info['transaction_id'].'退款失败</br>');
		      //Log::write('微信单号'.$refund_order_info['transaction_id'].'退款失败');
		      $data['fee_status'] ='退款失败';
		      $data['status'] ='8';
		      break;
		      case PROCESSING: print ('微信单号'.$refund_order_info['transaction_id'].'退款处理中</br>');
		      $data['fee_status'] ='退款处理中';
                      $data['status'] ='7';
		      break;
		      case NOTSURE: print ('微信单号'.$refund_order_info['transaction_id'].'未确定，需要商户原退款单号重新发起</br>');
		      //Log::write('微信单号'.$refund_order_info['transaction_id'].'未确定，需要商户原退款单号重新发起');
		      $data['fee_status'] ='未确定，需要商户原退款单号重新发起';
                      $data['status'] ='6';
		      break;
		      case CHANGE: print ('微信单号'.$refund_order_info['transaction_id'].'转入代发，退款到银行发现用户的卡作废或者冻结了，导致原路退款银行卡失败，资金回流到商户的现金帐号，需要商户人工干预，通过线下或者财付通转账的方式进行退款。</br>');
		      //Log::write('微信单号'.$refund_order_info['transaction_id'].'转入代发，退款到银行发现用户的卡作废或者冻结了，导致原路退款银行卡失败，资金回流到商户的现金帐号，需要商户人工干预，通过线下或者财付通转账的方式进行退款。');
		      $data['fee_status'] ='转入代发';
                      $data['status'] ='6';
		      break;
		    }
		  }else {
		    print ($refund_order_info['return_code'].$refund_order_info['return_msg'].'</br>');
		    //Log::write($refund_order_info['return_code'].$refund_order_info['return_msg']);
		    $data['fee_status'] ='错误-' . $refund_order_info['return_code'].$refund_order_info['return_msg'];
		  }
		  	
		  M('item_order')->where(array('transaction_id'=>$refund_order_info['transaction_id']))->save($data);
                    $ordersLog ['orderId'] = $refund_order_info['out_trade_no'];
	            $ordersLog ['logTime'] = time ();
	            $ordersLog ['logUserId'] = '';
	            $ordersLog ['logType'] = 0;
	            $ordersLog ['logContent'] = $data['fee_status'];
	            $ordersLog['logAccount']='';
	            M ( 'orders_log' )->add ( $ordersLog );
		}
		
		
	}
	
	public function  test() {
	  dump(C ( 'pin_baoyou' ));
	  dump( C('pin_close_order_time'));
	  $order_time = strtotime('+' . C(pin_close_order_time).'  hours' , 1452502453);//订单时间$val['add_time']
	   dump($order_time);
	   
	}
	
	/**
	 * 微信支付微信服务器发送支付结果
	 */
	public function payBack(){
		//echo "payBack";
		$ip = $_SERVER["REMOTE_ADDR"];
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];
		//Log::write ( "payBack ok:" . $postStr);
		$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
		$out_trade_no = $postObj->out_trade_no;
		$transaction_id =   $postObj->transaction_id;
		//Log::write ( "out_trade_no" . $out_trade_no );
		//Log::write ( "transaction_id" . $transaction_id);
		if ($out_trade_no != "") {
			//Log::write( "okok");
			$data ['status'] = 2;
			$data ['support_time']  = time ();
			//$data['transaction_id'] = $transaction_id;
			if (M ( 'item_order' )->where (' orderId=' . $out_trade_no )->data ( $data )->save ()) {
				$textTpl = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
				echo $textTpl;
			} else {
				$this->error ( '操作失败!' );
			}
		} else {
			//Log::write('err');
		}
		
	}
}