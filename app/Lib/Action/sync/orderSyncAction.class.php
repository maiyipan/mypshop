<?php

/**
 * 同步菜单
 * */
class orderSyncAction extends Action {
	
	public function index () {
		echo 'test';
	}
	public function syncOrder() {	
		//查询未同步的订单
		$shopid = '0003';
		$store_id = 10051;
		
		
		$SHEET_ID_PREFIX = 'WX';
		//Log::write('query orders...','INFO');
		$item_order_mod = M('item_order');
	    //$orders = $item_order_mod->where('status = 2 and sync_status = 0')->select();
		$prefix = C(DB_PREFIX);
		$orders = $item_order_mod->table($prefix."item_order as ord")
			->join($prefix."shop as sho on ord.uid = sho.uid")
		          ->where('ord.status in(2,3,4) and sync_status = 0')
		                ->field("ord.*,sho.erpnum")->select();
	    $order_detail_mod = M('order_detail');

	    $ErpOrderMod = M('eshore_order','dbusrinwx.','DB_CONFIG1');
	    $ErpOrderItem = M('eshore_order_item','dbusrinwx.','DB_CONFIG1');
	    $ErpPayMethod = M('eshore_paymethod_order','dbusrinwx.','DB_CONFIG1');
	    $ErpStdUpnote = M('std_upnote','dbusrinwx.','DB_CONFIG1');
        
	    // 同步
	    foreach ($orders as $order) {
	    	$idlist[] = $order['id'];
	    	
	    	//Log::write('update ERP, orderid is ' . $order['orderId'],'INFO');
	    	$data['SHEET_ID'] = $SHEET_ID_PREFIX . $order['orderId']; //$orderId;
	    	$data['ORDERS_ID'] = $order['orderId'];
	    	$data['USERS_ID'] = $order['userId'];
	    	$data['CREATE_STAFF'] = '';
	    	$data['AMOUNT'] = $order['order_sumPrice'];
	    	$data['TOTAL_PROD'] = $order['goods_sumPrice'];
	    	$data['TOTAL_SHIPPING'] = $order['freeprice']; //运费
	    	$data['TOTAL_ADJUSTMENT'] = $order['youhui'] ;//$order['total_adjustment'] +  $order['couponprice']; //折扣金额
	    	$data['TOTAL_POINT'] = ''; //使用积分
	    	$data['STORE_ID'] = $store_id; //归属商店编号
	    	
	    	$date1 =  date ("Y-m-d h:i:s" ,$order['add_time']);
	    	$date1 = '"' . $date1 . '"';
	    	$type = '"yyyy-MM-dd HH24:mi:ss"';
	    	
	    	$date_tmp = array(
	    			'exp',
	    			'to_date(' . $date1 . ',' . $type . ')'
	    	);
	    	
	    	$data['CREATE_DATE'] =  $date_tmp ;//$orders['add_time'];
	    	$data['RW_FLAG'] = 0; // 读写标识
	    	$data['SHIP_TYPE'] = 2;//$order['freetype']; // 快递类型 1 快递总仓 2 自提
	    	$freetype = $order['freetype'] == '1' ? '佳鲜配送' :'自提';
	    	$data['REQUEST_COMMENT'] = $data['note'] . '-' . $freetype .'-送货时间：' . $data['distTime']; //送货要求 
	    	$data['SHOPID'] = $order['erpnum']; //自提店门号  订单103快递 1 时为 自提时为 门店号，后面修改 $shopid;
	    	$data['RECEIVER'] =  $order['address_name']; // 收件人姓名
	    	$data['PHONE'] = $order['mobile'];
	    	$data['ADDRESS'] = $order['address'];
	    	$data['SHIPCODE'] = $order['shipcode'];// $this->createShipcode($shopid); //提货验证码  $order['erpnum'] .
	    	
	    	//$dataList[] = $data;
	    	
	    	
	    	//支付关系
	    	
	    	$dataSupport['SHEET_ID'] = $SHEET_ID_PREFIX . $order['orderId'];
	    	$dataSupport['paymethod_id'] = $order['support_id'];
	    	$dataSupport['orders_id'] = $order['orderId'];
	    	$dataSupport['order_num'] = $order['support_id']; //流水号
	    	$dataSupport['pay_type'] = $order['supportmetho']; //支付类型
	    	$dataSupport['pay_money'] = $order['order_sumPrice']; //支付金额 
	    	$date2 =  date ("Y-m-d h:i:s" ,$order['support_time']);
	    	$date2 = '"' . $date2 . '"';
	    	$type = '"yyyy-MM-dd HH24:mi:ss"';
	    	$date_supp = array(
	    			'exp',
	    			'to_date(' . $date2 . ',' . $type . ')'
	    	);
	    	$dataSupport['pay_time'] = $date_supp;//$order['support_time']; //支付时间
	    	$dataSupport['rw_flag'] = 0;//$order['orderId'];
	    	
	    	$ErpPayMethod->data($dataSupport)->add();
	    	
	    	//同步商品列表
	    	//查询商品列表
	    	$order_details = $order_detail_mod->where('orderId = ' . $order['orderId'] ) ->select();
	    	$orderitems_id = 1;
	    	
	    	$count = count($order_details) ;//$order['couponprice']
	    	$averagePri = ( $order['youhui'] ) / $count;   //$order['total_adjustment'] + $order['couponprice'] +
	    	foreach ($order_details as $detail) {
	    		$dataDetail['SHEET_ID']= $SHEET_ID_PREFIX. $detail['orderId'];
	    		$dataDetail['ORDERS_ID']= $detail['orderId'];
	    		$dataDetail['ORDERITEMS_ID']= $orderitems_id++;
	    		//Log::write('goodsid--' . $detail['goodsId']);
	    		$dataDetail['GOODS_ID']= $detail['goodsId']; 
	    		$dataDetail['BARCODEID']= $detail['barcodeid'];//商品条形码
	    		$dataDetail['GOODS_TYPE']= '1';//$detail['orderId'];  
	    		
	    		$dataDetail['QUANTITY']= $detail['quantity'];
	    		
	    		if ($detail['coupon_type'] == 10) {   //优惠类型(10:限时、11:满减、12:组合) 
	    			$item_price =  $detail['oldprice'] ;  //当为限时抢购时商品的价格要以原价计算
	    		} else {
	    			$item_price =  $detail['price'] ;
	    		}
	    		//Log::write("-item_price--" . $item_price);
	    		$dataDetail['PRICE']=$item_price;
	    		$dataDetail['TOTAL_PROD']= $item_price * $detail['quantity'] ;//订单项总金额  = PRICE × QUANTITY
	    		$dataDetail['TOTAL_ADJUSTMENT']=  $averagePri + $detail['couponval'];// $detail['quantity'] * ($detail['price']*(1- $order['adjustment'])) + $averagePri;//$detail['itemId'];
	    		$dataDetail['POINT']= '';//$detail['itemId'];
	    		$dataDetail['TOTAL_POINT']= '';//$detail['itemId'];
	    		$dataDetail['TOTAL_POINT_ADJUSTMENT']= '';//$detail['itemId'];
	    		$dataDetail['RW_FLAG']= 0;//$detail['itemId'];
	    		$dataDetail['STORE_ID']= $store_id;//$detail['itemId'];
	    		$ErpOrderItem->add($dataDetail);
	    		//$dataDetailList[] = $dataDetail;
	    		
	    		$data['TOTAL_ADJUSTMENT'] += $detail['couponval'];
	    	}
	    	
	    	$result = $ErpOrderMod->data($data)->add();
	    	
	    	//写入一天指令 
	    	$seq = array(
	    			'exp',
	    			'seq_upnote.nextval'
	    	);
	    	$upnote['ID'] = $seq;
	    	$upnote['SHEETID'] = $SHEET_ID_PREFIX . $order['orderId']; 
	    	$upnote['SHEETTYPE'] = '103';
	    	$upnote['SENDSHOP'] = 'D001'; //发送机构编码
	    	$upnote['RECEIVESHOP'] = 'C001'; //接收机构编码
	    	$date_upnote = array(
	    			'exp',
	    			'sysdate'
	    	);
	    	$upnote['NOTETIME'] = $date_upnote; //发送日期
	    	$upnote['DCID'] = 'C001'; // 所属物流中心编码
	    	$upnote['TABLENAMES'] = 'ESHORE_ORDER'; // 表名
	    	$upnote['NOTE'] = '新增订单'; // 备注
	    	$upnote['TOTALROWS'] = '1'; // 数据行的总行数，用于进行数据检查
	    	$upnote['HANDLESTATUS'] = '0'; // 接收处理状态(0:未接收 ; 1:已接收)
	    	//$upnote['HANDLEMSG'] = ''; // 接收异常信息
	    	//$upnote['HANDLETIME'] = ''; // 接收处理时间
	    	$ErpStdUpnote->add($upnote);
	    	
	    	
	    	if ($result) {
	    		//Log::write('update success, change sync status','INFO');
	    		$ord['id'] = $order['id'];
	    		$ord['sync_status'] = '1';
	    		$item_order_mod->save($ord);
	    	}
	    	
	    } 
	}
	
	
	public function createShipcode($shopid) {
		//$shopid . 
		$tmp = $shopid . strtotime(date ("Y-m-d h:i:s")); //.'-'.rand(1,2000);
		//echo $tmp;
		return $tmp;
	}
	
	public function testDeltet(){
		$ErpOrderItem = M('eshore_order_item','dbusrinwx.','DB_CONFIG1');
		$ErpOrderItem->where('sheet_id = ' . '201506261015381651')->delete();
		echo  $ErpOrderItem->getLastSql();
	}
	
	
	
	public function  testQuery() {
		
		$y = 10;
		$x = 8; //取出的数据
		echo  $x % $y;
		if ($x % $y == $x ) {
			echo '已全部取出';
		} else {
			echo '还需要取数据';
		}
		
	}
	
	public function  testRand(){
		
		/* for ($i = 0; $i < 1000; $i++){
			srand(microtime(true) * 1000);
			echo '-' .  rand(1, 25).PHP_EOL;
			echo '=' .  rand(1, 25).PHP_EOL;
			echo '</br>';
		} */
		/* for ($i = 0; $i < 1000; $i++){
		$order_sn = date('ymd').substr(time(),-5).substr(microtime(),2,5);
		echo $order_sn;
		echo '</br>';
		} */
		for ($i = 0; $i < 1000; $i++){
			//$dingdanhao = date ( "Y-m-dH-i-s" );
			//$dingdanhao = str_replace ( "-", "", $dingdanhao );
			$dingdanhao = rand ( 1000, 2000 );
			echo $dingdanhao;
			echo '</br>';
		}
	}
	
	
	public  function  test(){
		$score = 10.01;
		//echo intval($score);
		if (intval($score) == 0) {
			echo 'd';
		} else {
			echo 'ddd';
		}
	}
	
}