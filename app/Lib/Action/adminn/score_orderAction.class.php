<?php

class score_orderAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('score_order');
        $this->_cate_mod = D('score_item_cate');
    }

    protected function _search() {
        $map = array();
        ($time_start = $this->_request('time_start', 'trim')) && $map['add_time'][] = array('egt', strtotime($time_start));
        ($time_end = $this->_request('time_end', 'trim')) && $map['add_time'][] = array('elt', strtotime($time_end)+(24*60*60-1));
        ($username = $this->_request('username', 'trim')) && $map['username'] = array('like', '%'.$username.'%');
        ($consignee = $this->_request('consignee', 'trim')) && $map['consignee'] = array('like', '%'.$consignee.'%');
        ($mobile = $this->_request('mobile', 'trim')) && $map['mobile'] = array('like', '%'.$mobile.'%');
        ($orderId = $this->_request('orderId', 'trim')) && $map['order_sn'] = array('like', '%'.$orderId.'%');
        $this->assign('search', array(
            'time_start' => $time_start,
            'time_end' => $time_end,
            'consignee' => $consignee,
        	'mobile' => $mobile,
        	'username' => $username,
        	'orderId' => $orderId,
        ));
        return $map;
    }

    public function _before_index(){
        $data ['uid'] = $this->shopId();
        return $data;
    }

    public function _before_update($data){
        $data['status']=1;
        return $data;
    }
    
    public function status(){
    	$orderId= $this->_request('orderId', 'trim');
    	$status= $this->_request('status', 'intval');
    	if(!$orderId || !$status){
    		$this->ajaxReturn(0, L('illegal_parameters'));
    	}
    	 
    	if($status==4){
    		$data['status']=$status;
    		if($this->_mod->where('order_sn='.$orderId)->save($data)){
    			$ordersLog['orderId'] = $orderId;
    			$ordersLog['logTime'] = time();
    			$ordersLog['logUserId'] = $_SESSION['admin']['id'];
    			$ordersLog['logType'] = 0;
    			$ordersLog['logContent'] = '商家确认已收货，订单完成';
    			M('orders_log')->add($ordersLog);
    			$this->ajaxReturn(1, L('operation_success'));
    		}else{
    			$this->ajaxReturn(0, L('operation_failure'));
    		}
    	}else{
    		$data['status']=$status;
    		if($this->_mod->where('order_sn='.$orderId)->save($data)){
    			$ordersLog['orderId'] = $orderId;
    			$ordersLog['logTime'] = time();
    			$ordersLog['logUserId'] = $_SESSION['admin']['id'];
    			$ordersLog['logType'] = 0;
    			if($status==2){
    				$ordersLog['logContent'] = '商家已受理订单';
    			}
    			if($status==3){
    				$ordersLog['logContent'] = '商家已发货';
    			}
    			M('orders_log')->add($ordersLog);
    			$this->ajaxReturn(1, L('operation_success'));
    		}else{
    			$this->ajaxReturn(0, L('operation_failure'));
    		}
    	}
    }
    
    public function fahuo(){
    	//Log::write("fahuo");
    	$mod = D('score_order');
    	if (IS_POST&&$this->_post('order_sn','trim')) {
    		if($_POST['delivery']=='0'){
    			$data['userfree']=0;
    		}else{
    			$data['userfree']=$_POST['delivery'];
    			$data['freecode']=$_POST['deliverycode'];
    			$data['fahuoaddress']=$_POST['address'];
    		}
    		$data['fahuo_time']=time();
    		$data['status']=3;
    		$res = $mod->where('order_sn='.$_POST['order_sn'])->data($data)->save();
    		if($res){
    			$ordersLog['orderId'] = $_POST['order_sn'];
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
    
    public function details() {
    	$id = $this->_get('id','intval');
    	$item = $this->_mod->where(array('id'=>$id))->find();
    	 
    	if($item['fahuoaddress']){
    		$fahuoaddress=M('address')->find($item['fahuoaddress']);
    		$this->assign('fahuoaddress',$fahuoaddress);//发货地址
    	}
    	$orderId = $item['order_sn'];
    	$orderslogs = M('orders_log')->where(array('orderId'=>$orderId))->select();
    	$this->assign('orderslogs',$orderslogs);
    	
    	$scoreItem=M('score_item')->where('id='.$item['item_id'])->find();
    	$this->assign('scoreItem',$scoreItem);//订单商品信息
    	
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
    
    
    	//$wayBills =object_to_array ($billInfo)['data'][0]['wayBills'];
    
    	$this->assign('waybills',$wayBills);
    	//      dump($wayBills);
    	//         dump (object_to_array());
    
    
    	$this->display();
    }
}