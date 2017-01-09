<?php
// 本类由系统自动生成，仅供测试用途
class ShopcartAction extends frontendAction {
	  var $cart;
	  public function _initialize() {
        parent::_initialize();
        import('Think.ORG.Cart');// 导入分页类
    	$this->cart=new Cart($this->shopId);
    	$this->cart->setCartKey($this->shopId);
		$this->assign('limit',$_SESSION[$this->cartKey][$id]['num']);    	  
    }
      
    public function index(){
		$sumPrice = 0.00;
		$youhui = 0.00;
		//Log::write('index cart key >>' . $this->cart->cartKey);
// 		dump($_SESSION[$this->cart->cartKey]);
		//满减信息
		$fullgiveModel = new FullGive();
		$fullgivewhere['uid'] = $this->shopId;
		$fullgivewhere['is_close'] = 0;
// 		$fullgivewhere['award_type'] = 1;
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
	 	foreach ($_SESSION[$this->cart->cartKey] as $item) {
   			/* if( $item['id'] == 0)
   			{
   				unset($_SESSION[$this->cart->cartKey][$item['id']]);
   				continue;
   			} */
			//Log::write('status--' . $item['status'] , 'DEBUG');
			if($item['status'] == 0){
				//组合商品
				if($item['types'] == 1){
					$assemblewhere['id'] = $item['id'];
					$assemblewhere['start_time'][] = array('elt', date('Y-m-d'));
					$assemblewhere['end_time'][] = array('egt', date('Y-m-d'));
					$assemble = M('assemble')->where($assemblewhere)->find();
					if($assemble){
						$item['oldprice'] =  $assemble['original_price'];
						$item['price'] = $assemble['assemble_price'];
						$item['title'] = $assemble['name'];
						$itemSumPrice = $item['num'] * $assemble['assemble_price'];
					}else{
						$item['disable'] = 1;
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
						$itemSumPrice = $item['num'] * $limitbuy['award_value'];
						$item['oldprice'] =  $item['price'];
					}else{
						$itemSumPrice = $item['num'] * $item['price'];
						$fullgiveModel->add($item['id'], $item['price'], $item['num'], $item['cate_id']);
						//查看满减商品
// 						$fullcutwhere['type'] = 0;
// 						$fullcutwhere['condition'] = $item['id'];;
// 						$fullcutwhere['is_close'] = 0;
// 						$fullcutwhere['start_time'][] = array('elt', date('Y-m-d'));
// 						$fullcutwhere['end_time'][] = array('egt', date('Y-m-d'));
// 						$fullcut = D ( 'promotion' )->where($fullcutwhere)->order('id desc')->find();
// 						if($fullcut && $fullcut['reserve'] <= $itemSumPrice){
// 							$item['award_type'] =  $fullcut['award_type'];
// 							$item['reserve'] =  $fullcut['reserve'];
// 							if($fullcut['award_type'] == 1){
// 								$youhui = $youhui + $fullcut['award_value'];
// 								$itemSumPrice = $itemSumPrice - $fullcut['award_value'];
// 								$item['youhui'] =  $fullcut['award_value'];
// 							}else if($fullcut['award_type'] == 2){
// 								$item['youhui'] =  $fullcut['award_value'];
// 							}
// 						}
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
		
		$this->assign('assembles', $assembles );
		$this->assign('cartKey', $this->cart->cartKey);
		$this->assign('item', $_SESSION[$this->cart->cartKey]);
		$this->assign('sumPrice', sprintf ( "%.2f", $sumPrice ));
		$this->assign('youhui', $youhui );
		
		$this->assign ( 'currentid', 4);
		$this->display ();
    }
	public function editshopcart(){
		import ( 'Think.ORG.Cart' ); // 导入分页类
		$cart =$this->cart;
		$item_info = array();
		foreach ($_SESSION[$this->cart->cartKey] as $item) {
			//组合商品
			if($item['types'] == 1){
				$prefix = C(DB_PREFIX);
				//获取组合商品列表
				$assembleItems  = M('assemble_item')->field('i.comments,i.id,b.size,b.unitName, i.price as price,b.originplace,b.goodsId,b.title,b.img')
					->join($prefix.'item i ON i.id='.$prefix.'assemble_item.item_id')
					->join($prefix.'item_base b ON i.baseid=b.id')
					->where(array('assemble_id' => $item['id']))->select();
				$assembles[$item['id']] = $assembleItems;
			}
// 			$item_info[$item['id']] = D ( 'item' )->field('limit_buy')->relation(true)->find ( $item['id'] );
// 			//Log::write('item_info' .$item_info['limit_buy']);
		}
// 		dump($item_info);
// 		dump($_SESSION[$this->cart->cartKey]);
		$this->assign ( 'item', $_SESSION[$this->cart->cartKey] );
		$this->assign('assembles', $assembles );
		$this->assign ( 'sumPrice', $cart->getPrice () );
		$this->assign ( 'currentid', 4);
    	$this->display();
    }
    
    public function add_cart()//添加进购物车
    {
    	
    	$shopId=$this->shopId;
    	import ( 'Think.ORG.Cart' ); // 导入分页类
    	$cart =$this->cart;
//     	$cart->clear();
    	$goodId = $this->_post ( 'goodId', 'intval' ); // 商品ID
    	$quantity = $this->_post ( 'quantity', 'intval' ); // 购买数量
//     	$item = M ( 'item' )->field ( 'id,title,img,price,goods_stock,goodsId, barcodeid, mbdscnt' )->find ( $goodId );
    	$item = D ( 'item' )->relation(true)->find ( $goodId );
//    	dump($item);
//     	dump($item['baseid']['title']);
    	if (! is_array ( $item )) {
    		$data = array (
    				'status' => 0,
    				'msg' => '不存在该商品',
    				'count' => $cart->getCnt (),
    				'totalCnt' => $cart->getNum(),
				     'currentNUm' => $cart->getOneItemNum($goodId),
    				'sumPrice' => $cart->getPrice ()
    		);
    	} elseif ($item ['goods_stock'] < $quantity) {
    		$data = array (
    				'status' => 0,
    				'msg' => '没有足够的库存',
    				'count' => $cart->getCnt (),
    				'totalCnt' => $cart->getNum(),
				    'currentNUm' => $cart->getOneItemNum($goodId),
    				'sumPrice' => $cart->getPrice ()
    		);
    	} else {
    		//查询是否限购商品
    		$where['type'] = 1;
    		$where['condition'] = $goodId;
    		$where['start_time'][] = array('elt', date('Y-m-d H:i:s'));
    		$where['end_time'][] = array('egt', date('Y-m-d H:i:s'));
    		$promotionitem =  D ( 'promotion' )->where($where)->find();
    		//Log::write(D ( 'promotion' )->getLastSql(), 'DEBUG');
    		$item ['oldprice'] = $item ['price'];
    		if($promotionitem){
    			$item ['price'] = $promotionitem ['award_value'];
    		}
    		//Log::write('item_price---' .$item['price']);
    		//dump ($item);
    		$result = $cart->addItem ( $item ['id'], $item ['baseid']['title'], $item ['price'], 
    			                         $quantity, $item ['baseid']['img'], $item['goodsId'] , 
    				                       $item['baseid']['barcodeId'],$item ['oldprice'],$item ['baseid']['size'],
    				                          0,$item ['mbdscnt'],$shopId, $item['limit_buy'], $item ['baseid']['cate_id']);
    		//Log::write('result---' .$result);
    		if ($result == 1){ // 购物车存在该商品
    			$data = array (
    					'result' => $result,
    					'status' => 1,
    					'count' => $cart->getCnt (),
    					'totalCnt' => $cart->getNum(),
    					'sumPrice' => $cart->getPrice (),
						'currentNUm' => $cart->getOneItemNum($goodId),
    					'msg' => '该商品数量已增加'
    			);
    		} else if($result == 2){
    			$data = array (
	    			'result' => $result,
	    			'status' => 1,
	    			'count' => $cart->getCnt (),
	    			'totalCnt' => $cart->getNum(),
	    			'sumPrice' => $cart->getPrice (),
					'currentNUm' => $cart->getOneItemNum($goodId),
	    			'msg' => '该商品是限购商品'
    			);
    		}
    		else {
    			$data = array (
    					'result' => $result,
    					'status' => 1,
    					'count' => $cart->getCnt (),
    					'totalCnt' => $cart->getNum(),
    					'sumPrice' => $cart->getPrice (),
					    'currentNUm' => $cart->getOneItemNum($goodId),
    					'msg' => '商品已成功添加到购物车'
    			);
    		}
    	}
    
    	// $data=array('status'=>2);
    	echo json_encode ( $data );
    }
    
    public function addassemble()//添加组合商品
    {
    	import ( 'Think.ORG.Cart' ); // 导入分页类
		$cart =$this->cart;
		
		$itemId = $this->_post ( 'itemId', 'intval' ); // 商品ID
		$quantity = $this->_post ( 'quantity', 'intval' ); // 购买数量
		$assemblewhere['id'] = $itemId;
		$assemblewhere['start_time'][] = array('elt', date('Y-m-d'));
		$assemblewhere['end_time'][] = array('egt', date('Y-m-d'));
		$assemble = M('assemble')->where($assemblewhere)->find();
		if (! is_array ( $assemble )) {
			$data = array (
					'status' => 0,
					'msg' => '不存在该组合商品',
					'count' => $cart->getCnt (),
					'totalCnt' => $cart->getNum(),
					'sumPrice' => $cart->getPrice () 
			);
		} 
		//检查商品库存
		$assembles = M('assemble_item')->where(array('assemble_id'=>$itemId))->select();
		$original_price = 0.0;
		foreach($assembles as $assembled){
			$item_id = $assembled['item_id'];
			$item = D ( 'item' )->relation(true)->find ( $item_id );
// 			$item = M ( 'item' )->field ( 'id,title,img,price,goods_stock,goodsId, barcodeid, mbdscnt' )->find ( $item_id );
			if ($item ['goods_stock'] < $quantity) {
				$data = array (
						'status' => 0,
						'msg' => $item ['title'].'没有足够的库存',
						'count' => $cart->getCnt (),
						'totalCnt' => $cart->getNum(),
						'sumPrice' => $cart->getPrice ()
				);
				echo json_encode ( $data );
				exit;
			}
			$original_price = $original_price + $item['price'];
		}
		$result = $cart->addItem ( $itemId, $assemble ['name'], $assemble ['assemble_price'], $quantity, '', '' , '',$original_price,'',1,0,$this->shopId);
		if ($result == 1){ // 购物车存在该商品

			$data = array (
					'result' => $result,
					'status' => 1,
					'count' => $cart->getCnt (),
					'totalCnt' => $cart->getNum(),
					'sumPrice' => $cart->getPrice (),
					'msg' => '该商品数量已增加' 
			);
		} else {
			$data = array (
					'result' => $result,
					'status' => 1,
					'count' => $cart->getCnt (),
					'totalCnt' => $cart->getNum(),
					'sumPrice' => $cart->getPrice (),
					'msg' => '商品已成功添加到购物车' 
			);
		}
		
		// $data=array('status'=>2);
		echo json_encode ( $data );
    }
    
    public function remove_cart_item()//删除购物车商品
    {
    	import ( 'Think.ORG.Cart' ); // 导入购物车类
		$cart =$this->cart;
		
		$goodIds = $this->_post ( 'itemId' ); // 商品ID
		//Log::write ( 'goodid--' . $goodIds );
		$arr = explode(",",$goodIds);
		foreach ($arr as $key=>$val) {
			//Log::write('val>>' .  $val);
			$cart->delItem ( $val );
		}
		$data = array (
				'status' => 1 ,
				'sumPrice' => $cart->getPrice ()
		);
		echo json_encode ( $data );
    }
    
    public function change_quantity()
    {
    	import ( 'Think.ORG.Cart' ); // 导入购物车类
		$cart =$this->cart;
		
		$itemId = $this->_post ( 'itemId', 'intval' ); // 商品ID
		$quantity = $this->_post ( 'quantity', 'intval' ); // 购买数量
		
		$item = M ( 'item' )->field ( 'goods_stock, limit_buy' )->find ( $itemId );
		if ($item ['goods_stock'] < $quantity) {
			$data = array (
					'status' => 0,
					'msg' => '该商品的库存不足' 
			);
		} else {
			$cart->modNum ( $itemId, $quantity, $item['limit_buy'] );
			$data = array (
					'status' => 1,
					'item' => $cart->getItem ( $itemId ),
					'sumPrice' => $cart->getPrice () 
			);
		}
    	
    
    	echo json_encode($data);
    }
    
    
    public function cancle_item(){
    	import ( 'Think.ORG.Cart' ); // 导入购物车类
		$cart =$this->cart;
		$itemId = $this->_post ( 'itemId', 'intval' ); // 商品ID
		//Log::write ( 'itemId--' . $itemId );
		$cart->change_item_status ( $itemId );
		$data = array (
				'status' => 1,
				//'item' => $cart->getItem ( $itemId ),
				'sumPrice' => $cart->getPrice () 
		);
		echo json_encode ( $data );
    }
}