<?php
class item_orderAction extends backendAction {
	private $_payment_type = array('4'=>'会员卡支付', '3'=>'微信支付');
	private $_ship_company = array('圆通'=> 'YT','申通'=>'ST','中通'=>'ZT','邮政EMS'=>'YZEMS','天天'=>'TT','优速'=>'YS','快捷'=>'KJ','全峰'=>'QF','增益'=>'ZY');
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('item_order');
        $order_status=array(0=>'全部',1=>'待付款',2=>'待发货',3=>'待收货',4=>'完成',5=>'关闭',6=>'退款审核',7=>'退款中',8=>'退款失败',9=>'退款成功',10=>'人工关闭');  //zdc modified
        $this->assign('order_status',$order_status);
    }
    public function _before_index() {
        $this->assign('type', $this->_payment_type);
        $data['uid'] = array('in',$this->getUidList () );
    return $data;
  }
  /**
   * 首页(non-PHPdoc)
   * @see backendAction::index()
   */
  public function index(){
  	$map = $this->_search();
  	$mod = D($this->_name);
  	!empty($mod) && $this->_list($mod, $map);
  	$this->display();
  }
  /**
   * 订单完成
   */
  public function status() {
    $orderId = $this->_request ( 'orderId', 'trim' );
    $status = $this->_request ( 'status', 'intval' );
    if (! $orderId || ! $status) {
      $this->ajaxReturn ( 0, L ( 'illegal_parameters' ) );
    }
    
    if ($status == 4) {
      $data ['status'] = $status;
      if ($this->_mod->where ( 'orderId=' . $orderId )->save ( $data )) {
        $order_detail = M ( 'order_detail' );
        $item = M ( 'item' );
        $order_details = $order_detail->where ( 'orderId=' . $orderId )->select ();
        foreach ( $order_details as $val ) {
          $item->where ( 'id=' . $val ['itemId'] )->setInc ( 'buy_num', $val ['quantity'] );
        }
        //管理员操作日志
        $this->addadminlogs($_SESSION['admin']['username'], $orderId, 'status','商家确认已收货，订单完成');
        $ordersLog ['orderId'] = $orderId;
        $ordersLog ['logTime'] = time ();
        $ordersLog ['logUserId'] = $_SESSION ['admin'] ['id'];
        $ordersLog ['logType'] = 0;
        $ordersLog ['logContent'] = '商家确认已收货，订单完成';
        $ordersLog['logAccount']=$_SESSION['admin']['username'];
        M ( 'orders_log' )->add ( $ordersLog );
        $this->ajaxReturn ( 1, L ( 'operation_success' ) );
      } else {
        $this->ajaxReturn ( 0, L ( 'operation_failure' ) );
      }
    } else {
      $data ['status'] = $status;
      if ($this->_mod->where ( 'orderId=' . $orderId )->save ( $data )) {
        $ordersLog ['orderId'] = $orderId;
        $ordersLog ['logTime'] = time ();
        $ordersLog ['logUserId'] = $_SESSION ['admin'] ['id'];
        $ordersLog ['logType'] = 0;
        $ordersLog['logAccount']=$_SESSION['admin']['username'];
        if ($status == 2) {
            $this->addadminlogs($_SESSION['admin']['username'], $orderId, 'status','商家已受理订单');
            $ordersLog ['logContent'] = '商家已受理订单';
        }
        if ($status == 3) {
             $this->addadminlogs($_SESSION['admin']['username'], $orderId, 'status','商家已发货');
             $ordersLog ['logContent'] = '商家已发货';
        }
        M ( 'orders_log' )->add ( $ordersLog );
        $this->ajaxReturn ( 1, L ( 'operation_success' ) );
      } else {
        $this->ajaxReturn ( 0, L ( 'operation_failure' ) );
      }
    }
  }
  protected function _search($status_info ='') {
    $map = array ();
    // 'status'=>1
    ($time_start = $this->_request ( 'time_start', 'trim' )) && $map ['add_time'] [] = array (
        'egt',
        strtotime ( $time_start ) 
    );
    ($time_end = $this->_request ( 'time_end', 'trim' )) && $map ['add_time'] [] = array (
        'elt',
        strtotime ( $time_end ) + (24 * 60 * 60 - 1) 
    );
    
    ($time_start_support = $this->_request ( 'start_support_time', 'trim' )) && $map ['support_time'] [] = array (
        'egt',
        strtotime ( $time_start_support ) 
    );
    ($time_end_support = $this->_request ( 'end_support_time', 'trim' )) && $map ['support_time'] [] = array (
        'elt',
        strtotime ( $time_end_support ) + (24 * 60 * 60 - 1) 
    );
    
    ($price_min = $this->_request ( 'price_min', 'trim' )) && $map ['order_sumPrice'] [] = array (
        'egt',
        $price_min 
    );
    ($price_max = $this->_request ( 'price_max', 'trim' )) && $map ['order_sumPrice'] [] = array (
        'elt',
        $price_max 
    );
    ($userName = $this->_request('userName', 'trim')) && $map['userName'] = array('like', '%'.$userName.'%');
        
        ($address_name = $this->_request('address_name','trim'))&&$map['address_name'] = array('like','%'.$address_name.'%');
        ($mobile = $this->_request('mobile','trim'))&&$map['mobile'] = array('like','%'.$mobile.'%');
        
        $status = $this->_request('status', 'trim');
        	
        if(!$status){
        	$status = 0;
        }else{
        	if($status == 5){
        		$map['status']= array('in','10,'.$status);
        	}else{
        		$map['status'] = $status;
        	}
        }
        if(!empty($status_info)){
        	$map['status'] = $status_info;
	         if(!$status){
	        	$status = 0;
	        }else{
	        	if($status == 5){
	        		$map['status']= array('in','10,'.$status);
	        	}else{
	        		$map['status'] = $status;
	        	}
	        }
        }
        ($supportmetho = $this->_request('supportmetho', 'trim'))&& $map['supportmetho']= array('eq',$supportmetho);
 
 
        ($orderId = $this->_request('orderId', 'trim')) && $map['orderId'] = array('like', '%'.$orderId.'%');
        $this->assign('search', array(
            'time_start' => $time_start,
            'time_end' => $time_end,
            'price_min' => $price_min,
           	'price_max' => $price_max,
            'start_support_time' => $time_start_support,
           	'end_support_time' => $time_end_support,
            'userName' => $userName,
            'status' =>$status,
            'selected_ids' => $spid,
            'orderId' => $orderId,
        	'address_name' => $address_name,
        	'supportmetho' => $supportmetho,
        	'mobile' => $mobile,
        		
        ));
        
        $map['uid'] = array('in', $this->getUidList());
        return $map;
    }

    public function details() {
    	$id = $this->_get('id','intval');
        $item = $this->_mod->where(array('id'=>$id))->find();
           
        $order_detail=M('order_detail')->where('orderId='.$item['orderId'])->select();
        if($item['fahuoaddress']){
        	$fahuoaddress=M('address')->find($item['fahuoaddress']);
        	$this->assign('fahuoaddress',$fahuoaddress);//发货地址
        }
        $orderId = $item['orderId'];
        $orderslogs = M('orders_log')->where(array('orderId'=>$orderId))->select();
        $this->assign('orderslogs',$orderslogs);
        $this->assign('order_detail',$order_detail);//订单商品信息
        $this->assign('info', $item); // 订单详细信息
        
        $ch = curl_init();
      //  $url = 'http://apis.baidu.com/ppsuda/waybillnoquery/waybillnotrace?expresscode=YT&billno=200093247451';
        $header = array(
        		'apikey: 01a24c0bb4aef2f6ec728501231ac4f5',
        );
         
        // 添加apikey到header
        $expressname = $item['userfree'];
    //    dump($expressname); //ok
        $expresscode = $this->_ship_company[$expressname];
     //   dump($expresscode); //ok
        $billno =  $item['shipcode'];
   //     dump($billno); //ok
   
        $url = 'http://apis.baidu.com/ppsuda/waybillnoquery/waybillnotrace?expresscode='.$expresscode.'&billno='.$billno;
        
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);
        
    //    var_dump(json_decode($res));
    
        $billInfo = json_decode($res);
        
        
        $wayBills =object_to_array ($billInfo)['data'][0]['wayBills'];
        
        $this->assign('waybills',$wayBills);
  //      dump($wayBills);
//         dump (object_to_array());
      
        
        $this->display();
    }
    
    public function updateRemark(){
    	$txtSellerRemark= $_POST['txtSellerRemark'];//用客服备注
    	$id=$_POST['id'];//订单ID
    	$data['sellerRemark']=$txtSellerRemark;
    	$orderinfo = M('item_order')->where('id='.$id)->find();
    	if(M('item_order')->where('id='.$id)->save($data)!==false)
    	{
    	    $this->addadminlogs($_SESSION['admin']['username'], $orderinfo['orderId'], 'updateRemark','客服备注:'.$txtSellerRemark);
    	    $ordersLog ['orderId'] = $orderinfo['orderId'];
    	    $ordersLog ['logTime'] = time ();
    	    $ordersLog ['logUserId'] = $_SESSION ['admin'] ['id'];
    	    $ordersLog ['logType'] = 0;
    	    $ordersLog ['logContent'] = '客服备注';
    	    $ordersLog['logAccount']=$_SESSION['admin']['username'];
    	    M ( 'orders_log' )->add ( $ordersLog );
    		$this->success('修改成功！');
    	}else 
    	{
    		$this->error('修改失败！');
    	}
    	
    
    }
    
    /**
     * 退款排序
     */
    public function refund_order(){
    	
        $where = $this->_search($status_info='6');
        $count =  M('item_order')->where($where)->count();
        $pager = new Page($count, $pagesize);
        $page = $pager->show();
        $this->assign("page", $page);
        
        $select =  M('item_order')->where($where)->order('add_time desc')->limit($pager->firstRow.','.$pager->listRows);
        $list = $select->select();
        
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }
    /**
     * 退款审核
     */
    public  function audit(){
    	
    	if(IS_POST){
    		$id = $_POST['id'];
    		$orderId = $_POST['orderId'];
    		$refund_fee = $_POST['refundprice'];
    		$item_order = $this->_mod->where(array('id'=>$id))->find();
    		!$item_order && $this->_404 ();
    		if($refund_fee == null){
    			$this->error('退款金额不能为空！');
    		}
    		if ($refund_fee > $item_order['order_sumPrice']) {
    			$this->error('退款金额不能大于订单总金额！');
    		} else {
    			$data['status'] = 6;
    			$data['refund_fee'] =$refund_fee;
    			$save = $this->_mod->where(array('orderId'=>$orderId))->save($data);
    			if (!empty($save)){
    			    //日志记录
    			    $this->addadminlogs($_SESSION['admin']['username'], $orderId, 'audit','退款审批申请成功');
    			    $ordersLog ['orderId'] = $orderId;
    			    $ordersLog ['logTime'] = time ();
    			    $ordersLog ['logUserId'] = $_SESSION ['admin'] ['id'];
    			    $ordersLog ['logType'] = 0;
    			    $ordersLog ['logContent'] = '退款审批申请成功';
    			    $ordersLog['logAccount']=$_SESSION['admin']['username'];
    			    M ( 'orders_log' )->add ( $ordersLog );
    				$this->success('申请成功',U('item_order/index'));
    			}else {
    				$this->error('你已经申请过了，无需再申请，请耐心等待财务人员审批！',U('item_order/index'));
    			}
    		}
    	}else {
    		$id = $this->_get('id','intval');
    		$item = $this->_mod->where(array('id'=>$id))->find();
			$orderId = $item['orderId'];
			$orderslogs = M('orders_log')->where(array('orderId'=>$orderId))->select();
			$this->assign('orderslogs',$orderslogs);
			$this->assign('order_detail',$order_detail);//订单商品信息
			$this->assign('info', $item); // 订单详细信息
			
			$transaction_id = $item['orderId'];
			$out_refund_no = date("Ymdhis").$tansaction;
			$total_fee = $item['order_sumPrice'];
			$op_user_id =  $item['uid'];
			$supportmetho= $item['supportmetho'];
			$response = $this->fetch();
			$this->ajaxReturn(1, '', $response);
    	}
	    	
    }
    /**
     * 退款
     */
    public function refund(){
    	//Log::write("refund");
    	if(IS_POST) {
	      $shopModel = D ( 'shop' );
	      $sid = $this->shopId ();
	      $sid = $shopModel->getUidForP ();
	      $path = ''; // 证书路径
	      $orderid = $this->_post ( 'orderid' );
	      // $refund_fee = $this->_post('refundprice');
	      $itemOrder = $this->_mod->where ( array (
	          'orderId' => $orderid 
	      ) )->find ();
	      if (! $itemOrder) {
	        $this->error ( "未找到该订单" );
	      } else {
	        $refund_fee = $itemOrder ['refund_fee'];
	        
	        if ($refund_fee == 0) {
	          $this->success ( "该订单金额为0，无需退款" );
	        } else {
	        $supportmetho = $itemOrder ['supportmetho'];
	        $out_trade_no = $itemOrder ['orderId'];
	        
	        $out_refund_no = date ( "Y-m-dH-i-s" );
	        $out_refund_no = str_replace ( "-", "", $out_trade_no );
	        $out_refund_no .= rand ( 1000, 2000 );
	        $total_fee = $itemOrder ['order_sumPrice'];
	        $transaction_id = $itemOrder ['transaction_id'];
	        // 会员退款接口调用
	        if ($supportmetho == 4) {
	          $actual = - $refund_fee; // 退款金额为
	          $dingdanhao = $orderid;
	          $userid = 'wxtest';
	          $shipcode = date ( "Ymd" ) . $this->getSerialnumber ();
	          //$tradeCode = $userid . $shipcode;
	          $tradeCode = $dingdanhao . 'T'; 
	          $user_info = M ( 'card' )->field ( 'card_num,public_store' )->where ( array (
	              'id' => $itemOrder ['userId'] 
	          ) )->find ();
	          $public_store = $user_info ['public_store']; // '0011'
	          $card_num = $user_info ['card_num']; // '9904141'
	          $balanceCommit = new UserCardPay ();
	          $result = $balanceCommit->balanceCommit_pay ( $tradeCode, $actual, $card_num, $public_store );
	          /*
	           * ["processdataResult"] => int(1)
	           * ["outputpara"] => string(1) "1"
	           * ["rtn"] => int(1)
	           */
	          // 支付成功
	          if ($result && $result  != - 1) {
	            $resultDataInfo = explode ( '	', $result  );
	            //Log::write ( "the pay result 2---" . $resultDataInfo [0] );
	            if ($resultDataInfo [0] == 1) {
	              $save_info ['refund_id'] = $tradeCode;
	              $save_info ['refund_fee'] = $refund_fee;
	              $save_info ['status'] = 7;
	              $this->_mod->where ( array (
	                  'id' => $itemOrder ['id'],
	                  'orderId' => $orderid 
	              ) )->save ( $save_info );
	              //订单管理日日志
	              $this->addadminlogs($_SESSION['admin']['username'], $orderid, 'refund','会员退款');
	              $ordersLog ['orderId'] = $orderid;
	              $ordersLog ['logTime'] = time ();
	              $ordersLog ['logUserId'] = $_SESSION ['admin'] ['id'];
	              $ordersLog ['logType'] = 0;
	              $ordersLog ['logContent'] = '会员退款';
	              $ordersLog['logAccount']=$_SESSION['admin']['username'];
	              M ( 'orders_log' )->add ( $ordersLog );
	              //Log::write ( $this->_mod->getLastSql () );
	              //Log::write ( $this->_mod->getLastSql () );
	              IS_AJAX && $this->ajaxReturn ( 1, L ( 'operation_success' ), '', 'edit' );
	              $this->success ( L ( 'operation_success' ) );
	            } else {
	              IS_AJAX && $this->ajaxReturn ( 0, L ( 'operation_failure' ) );
	              $this->error ( L ( 'operation_failure' ) );
	            }
	          } else {
	            IS_AJAX && $this->ajaxReturn ( 0, L ( 'operation_failure' ) );
	            $this->error ( L ( 'operation_failure' ) );
	          }
	        } elseif ($supportmetho == 3) { // 微信退款接口调用
	          $weixinrefund = new WeixinPay ();
	          $refund_id = $weixinrefund->weixinRefund ( $transaction_id,$out_trade_no, $out_refund_no, $total_fee, $refund_fee, $sid );
                  
	          if (strtoupper($refund_id ['return_msg']) == 'OK' 
	              && strtoupper($refund_id ['return_code']) == 'SUCCESS'
	              && strtoupper($refund_id ['result_code']) == 'SUCCESS') {
	            $save_info ['refund_id'] = $refund_id ['refund_id'];
	            $save_info ['refund_fee'] = $refund_id ['refund_fee'];
	            $save_info ['status'] = 7;
	            $this->_mod->where ( array ('id' => $itemOrder ['id'],'orderId' => $orderid ) )->save ( $save_info );
	            //订单管理日志
	            $this->addadminlogs($_SESSION['admin']['username'], $orderid, 'refund','微信退款');
	            $ordersLog ['orderId'] = $orderid;
	            $ordersLog ['logTime'] = time ();
	            $ordersLog ['logUserId'] = $_SESSION ['admin'] ['id'];
	            $ordersLog ['logType'] = 0;
	            $ordersLog ['logContent'] = '微信退款';
	            $ordersLog['logAccount']=$_SESSION['admin']['username'];
	            M ( 'orders_log' )->add ( $ordersLog );
	            //Log::write ( $this->_mod->getLastSql () );
	            
	            //查询退款状态
	//             $refundStatus = $weixinrefund->wexinRefundQuery($transaction_id, $out_trade_no, $out_refund_no, $refund_id, $sid);
	           
	            IS_AJAX && $this->ajaxReturn ( 1, L ( 'operation_success' ), '', 'edit' );
	            $this->success ( L ( 'operation_success' ) );
	          } else {
                    $ordersLog ['orderId'] = $orderid;
	            $ordersLog ['logTime'] = time ();
	            $ordersLog ['logUserId'] = $_SESSION ['admin'] ['id'];
	            $ordersLog ['logType'] = 0;
	            $ordersLog ['logContent'] = '微信退款失败：' . $refund_id['err_code'].'-'.$refund_id['err_code_des'];
	            $ordersLog['logAccount']=$_SESSION['admin']['username'];
	            M ( 'orders_log' )->add ( $ordersLog );
		    $errinfo = '退款失败，请检查商户平台余额是否充足';
	            IS_AJAX && $this->ajaxReturn ( 0, $errinfo );
	            $this->error ( $errinfo ); //L ( 'operation_failure' )
	          }
	        }
	      }
	      }
	    }else {
	    		$id = $this->_get('id','intval');
	    		$item = $this->_mod->where(array('id'=>$id))->find();
	    		//dump($item);
	    		$orderId = $item['orderId'];
	    		$orderslogs = M('orders_log')->where(array('orderId'=>$orderId))->select();
	    		$this->assign('orderslogs',$orderslogs);
	    		$this->assign('order_detail',$order_detail);//订单商品信息
	    		$this->assign('info', $item); // 订单详细信息
	    			
	    		$transaction_id = $item['orderId'];
	    		$out_refund_no = date("Ymdhis").$tansaction;
	    		$total_fee = $item['order_sumPrice'];
	    		$refund_fee = $_POST['refundprice'];
	    		$op_user_id =  $item['uid'];
	    		$supportmetho= $item['supportmetho'];
	    			
	    		$this->assign('operatorId',$op_user_id);
	    		$response = $this->fetch();
	    		$this->ajaxReturn(1, '', $response);
	    	}
	    	
    	
    }
    /**
     * 发货
     */
    public function fahuo(){
		//Log::write("fahuo");
    	$mod = D($this->_name);
        if (IS_POST&&$this->_post('orderId','trim')) {
	    	if (false === $data = $mod->create()) {
	        	IS_AJAX && $this->ajaxReturn(0, $mod->getError());
	                $this->error($mod->getError());
	            }
	            if (method_exists($this, '_before_insert')) {
	                $data = $this->_before_insert($data);
	            }
	            if($_POST['delivery']=='0'){
	            	$date['userfree']=0;
	            }else{
	            	$date['userfree']=$_POST['delivery'];
	            	$date['freecode']=$_POST['deliverycode'];
	            	$date['fahuoaddress']=$data['address'];
	            }
	            $date['fahuo_time']=time();
	            $date['status']=3;
	            if($mod->where('orderId='.$data['orderId'])->data($date)->save()){
	                //管理员操作日志
	                $this->addadminlogs($_SESSION['admin']['username'], $data['orderId'], 'fahuo','商家发货');
	                
	            	$ordersLog['orderId'] = $data['orderId'];
	            	$ordersLog['logTime'] = time();
	            	$ordersLog['logUserId'] = $_SESSION['admin']['id'];
	            	$ordersLog['logType'] = 0;
	            	$ordersLog['logContent'] = '商家已发货';
	            	M('orders_log')->add($ordersLog);
	            	
	                IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'add');
	                $this->success(L('operation_success'));
	            } else {
	                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
	              	$this->error(L('operation_failure'));
	          	}
        } else {
            $this->assign('open_validator', true);
            if (IS_AJAX) {
            	if(count(M('address')->where('status=1')->find())==0){
            		$this->ajaxReturn(1, '', '请先添加默认收货地址！');
            	}
             	$id= $this->_get('id','trim');//订单号ID
              	$info= $this->_mod->find($id);
              	$this->assign('info',$info);
             	$deliveryList=	M('delivery')->where('status=1')->order('ordid asc,id asc')->select();//快递方式
              	$this->assign('deliveryList',$deliveryList);
             	$addressList=M('address')->where('status=1')->order('ordid asc,id asc')->select();//快递方式
              	$this->assign('addressList',$addressList);
              	$response = $this->fetch();
              	$this->ajaxReturn(1, '', $response);
            } else {
            	$this->display();
            }
        }
    }
    
    public function ajax_getchilds() {
    	$id = $this->_get('id', 'intval');
    	$type = $this->_get('type', 'intval', null);
    	$map = array('pid'=>$id);
    	if (!is_null($type)) {
    		$map['type'] = $type;
    	}
    	$return = $this->_mod->field('id,name')->where($map)->select();
    	if ($return) {
    		$this->ajaxReturn(1, L('operation_success'), $return);
    	} else {
    		$this->ajaxReturn(0, L('operation_failure'));
    	}
    }
    
    /**
     * 人工关闭
     */
    public function close(){
    	//Log::write("close");
    		$data['status'] =	10;
    		$status_info = M('item_order')->where('id ='.$this->_get('id','trim'))->save($data);
    		//管理员操作日志
    		$this->addadminlogs($_SESSION['admin']['username'], $status_info['orderId'], 'close','人工关闭订单');
    		$ordersLog['orderId'] = $status_info['orderId'];
    		$ordersLog['logTime'] = time();
    		$ordersLog['logUserId'] = $_SESSION['admin']['id'];
    		$ordersLog['logType'] = 0;
    		$ordersLog['logContent'] = '人工关闭订单';
    		M('orders_log')->add($ordersLog);
    		//Log::write(M('item_order')->getLastSql(), 'DEBUG');
    		if($status_info){
    			$this->success('订单关闭成功',U('item_order/index',array('status'=>5)));
    		}else{
    			$this->error('订单关闭失败',U('item_order/index',array('status'=>5)));
    		}
    }
    /**
     * 优惠还原
     */
    public function cpclose(){
		$id = $this->_get ( 'id', 'intval' );
		$order_infolist = M ( 'item_order' )->field ( 'coupon,couponprice, fee_youhui' )->where ( 'id=' . $id )->find ();
		// 判断是否使用优惠
		if ($order_infolist ['coupon'] == null) {
			$this->error ( '该用户没有使用优惠券', U ( 'item_order/refund_order', array (
					'status' => 6 
			) ) );
		} elseif ($order_infolist['fee_youhui'] == 1){
			$this->error ( '优惠券已经返还', U ( 'item_order/refund_order', array (
					'status' => 6
			) ) );
		}else {
			$random = substr ( $order_infolist ['coupon'], 0, 1 );
			
			// 判断优惠类型
			$map ['fee_youhui'] = 1;
			$map['id'] = $id;
			if ($random == 'Z') {
				$random = ltrim ( $order_infolist ['coupon'], 'Z' );
				$where['random'] = $random;
				$select = M ( 'discount' )->where ( $where);
				$random_list = $select->find ();
				if ($random_list) {
					$data ['valid'] = 0;
					$select->where($where)->save ( $data );
					M ( 'item_order' )->save($map);
					//管理员操作日志
					$this->addadminlogs($_SESSION['admin']['username'], $order_infolist['orderId'], 'cpclose','优惠券返还，折扣券'.$random);
					$ordersLog['orderId'] = $order_infolist['orderId'];
					$ordersLog['logTime'] = time();
					$ordersLog['logUserId'] = $_SESSION['admin']['id'];
					$ordersLog['logType'] = 0;
					$ordersLog['logContent'] = '优惠券返还，折扣券'.$random;
					M('orders_log')->add($ordersLog);
					//Log::write ( $select->getLastSql () );
					$this->success ( '优惠还原成功', U ( 'item_order/refund_order', array (
							'status' => 6 
					) ) );
				}
			} elseif ($random == 'M') {
				$random = ltrim ( $order_infolist ['coupon'], 'M' );
				$where['random'] = $random;
				$select = M ( 'full_cut' )->where ( $where );
				$random_list = $select->find ();
				if ($random_list) {
					$data ['valid'] = 0;
					$select->where($where)->save ( $data );
					M ( 'item_order' )->save($map);
					//管理员操作日志
					$this->addadminlogs($_SESSION['admin']['username'], $order_infolist['orderId'], 'cpclose','优惠券返还，满减券'.$random);
					$ordersLog['orderId'] = $order_infolist['orderId'];
					$ordersLog['logTime'] = time();
					$ordersLog['logUserId'] = $_SESSION['admin']['id'];
					$ordersLog['logType'] = 0;
					$ordersLog['logContent'] = '优惠券返还，满减券'.$random;
					M('orders_log')->add($ordersLog);
					//Log::write ( $select->getLastSql () );
					$this->success ( '优惠还原成功', U ( 'item_order/refund_order', array (
							'status' => 6 
					) ) );
				}
			} elseif ($random == 'B') {
				$random = ltrim ( $order_infolist ['coupon'], 'B' );
				$where['random'] = $random;
				$select = M ( 'gift' )->where ( $where );
				$random_list = $select->find ();
				if ($random_list) {
					$data ['surple'] = $random_list ['surple'] - $order_infolist ['couponrice']; // 还原余额
					$data ['gift'] = $random_list ['gift'] + $order_infolist ['couponrice']; // 还原金额
					$select->where($where)->save ( $data );
					M ( 'item_order' )->save($map);
					//管理员操作日志
					$this->addadminlogs($_SESSION['admin']['username'], $order_infolist['orderId'], 'cpclose','优惠券返还，代金券'.$random);
					$ordersLog['orderId'] = $order_infolist['orderId'];
					$ordersLog['logTime'] = time();
					$ordersLog['logUserId'] = $_SESSION['admin']['id'];
					$ordersLog['logType'] = 0;
					$ordersLog['logContent'] = '优惠券返还，代金券'.$random;
					M('orders_log')->add($ordersLog);
					
					//Log::write ( $select->getLastSql () );
					$this->success ( '优惠还原成功', U ( 'item_order/refund_order', array (
							'status' => 6 
					) ) );
				}
			} else {
				$this->error ( '优惠还原失败', U ( 'item_order/refund_order', array (
						'status' => 6
				) ) );
			}
			
		}
	}
    public function phpexcel(){
    	//Log::write('---phpExcel---');
    	/*=====
    	 这里写具体的数据调用与数据处理
    	 ======*/
    	Vendor ( "Excel.PHPExcel"); //导入thinkphp第三方类库
    	
    	//创建一个读Excel模版的对象
    	$objReader = PHPExcel_IOFactory::createReader ( Excel5 );
    	$objPHPExcel = $objReader->load (APP_NAME . '/' . 'ExcelTpl/'."template.xls"); //读取模板，模版放在根目录
    	//获取当前活动的表
    	$objActSheet = $objPHPExcel->getActiveSheet();
    	$objActSheet->setTitle('佳鲜农庄订单导出' );//设置excel标题    	
    	$objActSheet->setCellValue ( 'A1', '订单' );
    	$objActSheet->setCellValue ( 'A2', '信息导出' );
    	$objActSheet->setCellValue('F2','导出时间:'. date('Y-m-d H:i:s'));
    	
    	//现在开始输出列头了
    	$objActSheet->setCellValue('A3','商品名称');
    	$objActSheet->setCellValue('B3','商品数量');
    	
    	$objActSheet->setCellValue('C3','订单号');
    	$objActSheet->setCellValue('D3','状态');
    	$objActSheet->setCellValue('E3','会员名');
    	$objActSheet->setCellValue('F3','收货人');
    	$objActSheet->setCellValue('G3','收货人电话');
    	$objActSheet->setCellValue('H3','收货地址');
    	$objActSheet->setCellValue('I3','商品金额');
    	$objActSheet->setCellValue('J3','订单金额');
    	$objActSheet->setCellValue('K3','优惠金额');
    	$objActSheet->setCellValue('L3','支付单号');
    	$objActSheet->setCellValue('M3','配送时间');
    	$objActSheet->setCellValue('N3','退款单号');
    	$objActSheet->setCellValue('O3','退款金额');
    	$objActSheet->setCellValue('P3','下单时间');
    	$objActSheet->setCellValue('Q3','配送方式');
    	$objActSheet->setCellValue('R3','支付方式');
		$objActSheet->setCellValue('S3','所属门店');
		$objActSheet->setCellValue('T3','门店编号');
    	//具体有多少列，有多少就写多少，跟下面的填充数据对应上就可以
    	
    	//现在就开始填充数据了 （从数据库中）
    	if($_GET['status']){
    		$where['status']=$_GET['status'];//获取订单状态
    	}
    	if($_GET['orderId']){
    		$where['orderId']=$_GET['orderId'];//获取订单号
    	}
    	if ($_GET['userName']){
    		$where['userName']=$_GET['userName'];//获取用户名称
    	}
    	if($_GET['time_start']){
    		//Log::write($_GET['time_end']);
    		$where['add_time'] =array(
    				array('egt', strtotime($_GET['time_start'])),array('elt',(strtotime($_GET['time_end'])+(24 * 60 * 60 - 1)))//下单时间
    		);
    	}
    	if ($_GET['supportmetho']){
    		$where['supportmetho']=$_GET['supportmetho'];//支付方式
    	}
    	if($_GET['address_name']){
    		$where['address_name']=$_GET['address_name'];//收货人
    	}
    	if($_GET['mobile']){
    		$where['mobile']=$_GET['mobile'];//收货人电话
    	}    	
    	if($_GET['start_support_time']){
    		$where['support_time'] = array(
    				array('egt',strtotime($_GET['start_support_time'])),array('elt',(strtotime($_GET['end_support_time'])+(24 * 60 * 60 - 1)) )//付款时间
    		);
    	}
    	$where['uid'] = array('in', $this->getUidList());//对应店铺
    	
    	
    	$info = $this->_mod->where($where)->select ();
    	

    	$arr=array();
    	foreach ($info as $k => $v){
    	   
    	        $b=M('order_detail')->field('title,quantity')->where('orderId='.$v ['orderId'])->select();
    	        foreach ($b as $key=>$val){
    	            $arr[]=array_merge($v,array('detail'=>$val));
    	            $info=$arr;
    	        }
    	
    	   
    	}
    	
    	//Log::write($this->_mod->getLastSql(), 'DEBUG');
    	$baseRow = 4; //数据从N-1行开始往下输出 这里是避免头信息被覆盖   	
//     	$order_status=array(0=>'全部',1=>'待付款',2=>'待发货',3=>'待收货',4=>'完成',5=>'关闭',6=>'退款审核',7=>'退款中',8=>'退款失败',9=>'退款成功',10=>'人工关闭');  //zdc modified
    	if($info){
    		foreach($info as $r => $info){
    			switch ($info['status']){
    				case 0: $info['status']='全部';		break;
    				case 1: $info['status']='待付款';	break;
    				case 2: $info['status']='待发货';	break;
    				case 3: $info['status']='待收货';	break;
    				case 4: $info['status']='完成';		break;
    				case 5: $info['status']='关闭';		break;
    				case 6: $info['status']='退款审核';	break;
    				case 7: $info['status']='退款中';	break;
    				case 8: $info['status']='退款失败';	break;
    				case 9: $info['status']='退款成功';	break;
    				case 10: $info['status']='人工关闭';	break;
    				default:;
    			}
    			switch ($info['freetype']){
    				case 1: $info['freetype']='佳鲜配送';	break;
    				case 2: $info['freetype']='自提';		break;
    				case 3: $info['freetype']='物流';		break;
    				default:;
    			}
    			$row = $baseRow + $r;
    			
    			$objPHPExcel->getActiveSheet ()->setCellValue ( A . $row,
    			    ' '. $info ['detail']['title'], PHPExcel_Cell_DataType::TYPE_STRING2);//商品名称
    			$objPHPExcel->getActiveSheet ()->setCellValue ( B . $row,
    			    ' '. $info ['detail']['quantity'], PHPExcel_Cell_DataType::TYPE_STRING2);//商品数量
    			    
    			
    			$objPHPExcel->getActiveSheet ()->setCellValue ( C . $row, 
    			   ' '. $info ['orderId'], PHPExcel_Cell_DataType::TYPE_STRING2);//订单号
    			$objPHPExcel->getActiveSheet ()->setCellValue ( D . $row, 
    			    $info['status'] );//状态
    			$objPHPExcel->getActiveSheet ()->setCellValue ( E . $row, 
    			    $info ['userName'] );//用户名
    			$objPHPExcel->getActiveSheet ()->setCellValue ( F . $row,
    			    $info ['address_name'] );//收件人
    			$objPHPExcel->getActiveSheet ()->setCellValue ( G . $row,
    			    ' '. $info ['mobile'],  PHPExcel_Cell_DataType::TYPE_STRING2 );//联系方式
    			$objPHPExcel->getActiveSheet ()->setCellValue ( H . $row, $info ['address'] );//收货地址
    			
    			$objPHPExcel->getActiveSheet ()->setCellValue ( I . $row, 
    			    $info ['goods_sumPrice'] );//商品金额
    			$objPHPExcel->getActiveSheet ()->setCellValue ( J . $row, 
    			    $info ['order_sumPrice'] );//支付金额
    			$objPHPExcel->getActiveSheet ()->setCellValue ( K . $row, 
    			    $info['couponprice']);//优惠金额
    			$objPHPExcel->getActiveSheet ()->setCellValue ( L . $row, 
    			    ' '.$info ['transaction_id'], PHPExcel_Cell_DataType::TYPE_STRING2 );//支付单号
    			$objPHPExcel->getActiveSheet ()->setCellValue ( M . $row,
    			    $info ['distTime'] );//配送时间
    			$objPHPExcel->getActiveSheet ()->setCellValue ( N . $row, 
    			    $info['refund_id'], PHPExcel_Cell_DataType::TYPE_STRING2 );//退款单号
    			$objPHPExcel->getActiveSheet ()->setCellValue ( O . $row,
    			    $info ['refund_fee'] );//退款金额
    			$objPHPExcel->getActiveSheet ()->setCellValue ( P . $row, 
    			    date('Y:m:d H:i:s',$info ['add_time']) );//下单时间
    			$objPHPExcel->getActiveSheet ()->setCellValue ( Q . $row, $info['freetype']);//配送方式

				switch ($info['supportmetho']){
					case 3: $info['supportmetho']='微信支付';	break;
					case 4: $info['supportmetho']='会员卡支付';		break;
					default: $info['supportmetho']='微信支付';
				}
				$objPHPExcel->getActiveSheet ()->setCellValue ( R . $row, $info['supportmetho']);//支付方式
				
				$objPHPExcel->getActiveSheet ()->setCellValue ( S. $row, C('spconf_name_' . $info['uid']));//门店
				$objPHPExcel->getActiveSheet ()->setCellValue ( T. $row, ' ' .C('spconf_erpnum_' . $info['uid']));//门店编号
    		}
    	}
    	//导出
    	$filename='佳鲜农庄订单导出_' . date('YmdHi', time());
    	$filename = iconv('utf-8','gb2312', $filename);
    	
    	header('Content-type: application/vnd.ms-excel;charset=utf-8');
    	header('Content-Disposition: attachment;filename=' . $filename . '.xls');
    	header("Cache-Control: max-age=0");
    	$objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
    	$objWriter->save('php://output');
    	exit;
    }
    
    
    public function getSerialnumber(){
        $Mod = M('order_serialnumber');
        $date = date("Ymd");
        $info = $Mod->where('date = ' . $date)->find();
        if ($info) {
            $serial_number = $info['serial_number'];
            $data['serial_number'] = ($serial_number+1);
            $data['id']  = $info['id'];
            $Mod->save($data);
            return $this->addZero($serial_number+1);
        } else {
            $data['date'] = $date;
            $serial_number = '1';
            $data['serial_number'] = $serial_number;
            $Mod->add($data);
            return $this->addZero($serial_number);
        }
    }
    
    //不够5位前面加 0
    public function addZero($serial_number){
        $var=sprintf("%05d", $serial_number);//生成4位数，不足前面补0
        return $var;//结果为0002
    }
}