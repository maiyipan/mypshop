<?php
class marketAction extends frontendAction {
	
	public function _initialize() {
		parent::_initialize ();
	}
	/**
	 * 限时抢购
	 */
	public function limitbuy() {
			/*
		 * $where = array();
		 * $where['type'] = 1;
		 * $where['uid'] = $this->shopId;
		 * $where['start_time'][] = array('elt', date('Y-m-d'));
		 * $where['end_time'][] = array('egt', date('Y-m-d'));
		 *
		 * $count = D ( 'promotion')->where($where)->count();
		 * $pager = new Page($count, $pagesize);
		 * $page = $pager->show();
		 * $this->assign("page", $page);
		 * $db_pre = C('DB_PREFIX');
		 * $ai_table = $db_pre . 'promotion';
		 * unset($where['uid']);
		 * $where['i.uid'] = $this->shopId;
		 * $select = D ( 'promotion' )->field($ai_table.'.*, i.*')
		 * ->join($db_pre.'item i ON i.id=' . $ai_table . '.condition')->where($where)->limit($pager->firstRow.','.$pager->listRows);
		 * $list = $select->select();
		 * //dump($list);
		 * $this->assign('list', $list);
		 */
		$this->display ();
	}
	/**
	 * 精品推荐
	 */
	public function recommend() {
		/* $map['uid'] = $this->shopId;
		$map ['is_recomm'] = 1;
		$map ['status'] = 1;
		$map ['is_delete'] = 1;
		$itemList =  M ( 'item' )->where ( $map )->select (); */
		
		//是否限购商品
// 		$where['type'] = 1;
// 		$where['start_time'][] = array('elt', date('Y-m-d'));
// 		$where['end_time'][] = array('egt', date('Y-m-d'));
// 		$where['uid'] =  $this->shopId;
// 		$db_pre = C('DB_PREFIX');
// 		$ai_table = $db_pre . 'promotion';
// 		$limitlist =  D ( 'promotion' )->field('condition,award_value,start_time,end_time')->where($where)->select();
		
		//dump(D ( 'promotion' )->getLastSql(), 'DEBUG');
		//exit();
		/* foreach($itemList as &$item){
			foreach($limitlist as $limit){
				if($item['id'] == $limit['condition']){
					$item['limit'] = $limit;
				}
			}
		} */
		
		//dump($itemList);
		/*zdc added for like function*/
	//	$likeVal =  $_POST['favorCheckInput‘];
		
		
		
		//$likeVal =  $_POST['item'];
	
		
		//$this->assign ( 'itemList', $itemList );
		$this->assign('typeid', 4);
		$this->display('item:index');
	}
	/**
	 * 一路领鲜
	 */
	public function lingxian() {
		/* $map['uid'] = $this->shopId;
		$map ['is_new'] = 1;
		$map ['status'] = 1;
		$map ['is_delete'] = 1;
		$itemList =  M ( 'item' )->where ( $map )->select ();
		
		//是否限购商品
		$where['type'] = 1;
		$where['start_time'][] = array('elt', date('Y-m-d'));
		$where['end_time'][] = array('egt', date('Y-m-d'));
		$where['uid'] =  $this->shopId;
		$db_pre = C('DB_PREFIX');
		$ai_table = $db_pre . 'promotion';
		$limitlist =  D ( 'promotion' )->field('condition,award_value,start_time,end_time')->where($where)->select();
		foreach($itemList as &$item){
			foreach($limitlist as $limit){
				if($item['id'] == $limit['condition']){
					$item['limit'] = $limit;
				}
			}
		}
		
		$this->assign ( 'itemList', $itemList ); */
		$this->assign('typeid', 2);
		$this->display('item:index');
	}
	/**
	 * 企业团购
	 */
	public function enterpriceInfo() {
		if (IS_POST) {
			$username = $this->_post ( 'username', 'trim' );
			$tel = $this->_post ( 'tel', 'trim' );
			$companyName = $this->_post ( 'companyName', 'trim' );
			$companyNameAddress = $this->_post ( 'companyNameAddress', 'trim' );
			
			// echo 'username = '.$username."<br>";
			// echo 'tel = '.$tel."<br>";
			// echo 'company name = '.$companyName."<br>";
			// echo 'company address = '.$companyNameAddress."<br>";
			
			$enterpriceInfoData ['name'] = $username;
			$enterpriceInfoData ['tel'] = $tel;
			$enterpriceInfoData ['company_name'] = $companyName;
			$enterpriceInfoData ['company_name_address'] = $companyNameAddress;
			$enterpriceInfoData ['createtime'] = date ( 'y-m-d h:i:s', time () );
			$enterpriceInfoData ['shopid'] = $this->shopId;
			$companyModel = M ( 'company' );
			$result = $companyModel->add ( $enterpriceInfoData );
			
			$this->success('登记成功，佳鲜会和您联系！',U('index/index',array('sid'=>$this->shopId)));
		} else {
			$this->display();
		}
	}
	
//推广页面
	public function promotionList() {
		//判断当前推广的类型
		$id = $this->_get ( 'id', 'intval' );
		!$id && $this->_404();
		$info = D('shop_expand')->find($id);
		!$info && $this->_404();
		$type = $info['type'];
		if($type == 2){//链接
			header("Location: ".$info['link']);
			exit;
		}else{//活动页
			//获取分类广告
			$adverts = M('shop_expand_advert')->where(array('expand_id'=>$id))->select();
			$this->assign('adverts', $adverts);
			
			$this->assign('typeid', 7);
			$this->assign('id', $id);
			$this->display();;
		}
	}
	
	public function promotion_ajax() {
		
		$id = $this->_get('id', 'trim');
		$sort = $this->_get('sortId', 'trim'); //排序
		//获取商品
		switch ($sort) {
            case '0':
                $order =  'ordid  ASC, ';
                break;
            case '1':
                $order =  ' price DESC ,';
                break;
            case '2':
                $order = 'price ASC,';
                break;
            case '3':
                $order =  'add_time DESC,';
                break;
        }
        $order = ' (goods_stock>0) DESC, '.$order . ' buy_Num ASC ';
			
		$spage_size = 6;// C('pin_wall_spage_size'); //每次加载个数
		$p = $this->_get('p', 'intval', 1); //页码
			
		//条件
		$where['i.expand_id'] = $id;
		$where['weixin_item.status'] = 1;
		$where['weixin_item.uid'] = array('eq',$this->shopId);
	
		$item_mod = D('item');
			
		//总数
		//$count = $item_mod->where($where)->count('id');
		$db_pre = C('DB_PREFIX');
		$item = $db_pre . 'item';
			
		$count = $item_mod->relation(true)
		->join('LEFT JOIN weixin_shop_expand_item i ON i.item_id = weixin_item.id')
		->join('LEFT JOIN weixin_item_like ulike ON ulike.id = weixin_item.id')
		->join($db_pre . 'item_base base ON base.id = ' . $item . '.baseid')
		->join($db_pre . 'promotion prom ON prom.condition = ' . $item . '.id')
		->where($where)->count($item.'.id');
		//工多少页
		$pageSize = $count / $spage_size;
			
		$start = ($p -1)*$spage_size;
		//maiyipan 优化商品列表价格显示base.prime_price=>weixin_item.prime_price 2016-2-26
		$field == '' && $field = 'distinct(weixin_item.id),weixin_item.*,weixin_item.prime_price,
			                     base.cate_id,base.brand,base.title,
			                       base.intro,base.img,base.info,
								   ulike.userid as likeflag,
			                         prom.condition as prom';
		$item_list = $item_mod->relation(true)
		->join('LEFT JOIN weixin_shop_expand_item i ON i.item_id = weixin_item.id')
		->join('LEFT JOIN weixin_item_like ulike ON ulike.id = weixin_item.id')
		->join($db_pre . 'item_base base ON base.id = ' . $item . '.baseid')
		->join($db_pre . 'promotion prom ON prom.condition = ' . $item . '.id')
		->field($field)->where($where)->order($order)
		->limit($start.','.$spage_size) ->  select();
			
		// 		dump($item_list);
		////Log::write($item_mod->getLastSql(), 'DEBUG', 'DEBUG');
		//exit();
		foreach ($item_list as $key=>$val) {
			//解析评论
			isset($val['comments_cache']) && $item_list[$key]['comment_list'] = unserialize($val['comments_cache']);
		}
		$this->assign('item_list', $item_list);
		$resp = $this->fetch('public:waterfall');
		$data = array(
				'isfull' => 1,
				'html' => $resp
		);
		//数据查询完
		$count < $start + $spage_size && $data['isfull'] = 0;
		$this->ajaxReturn(1, '', $data);
	}
	
	//推广页面
	public function promotionListOld() {
		//判断当前推广的类型
		$id = $this->_get ( 'id', 'intval' );
		!$id && $this->_404();
		$info = D('shop_expand')->find($id);
		!$info && $this->_404();
		$type = $info['type'];
		if($type == 2){//链接
			header("Location: ".$info['link']);
			exit;
		}else{//活动页
			//获取分类广告
			$adverts = M('shop_expand_advert')->where(array('expand_id'=>$id))->select();
			$this->assign('adverts', $adverts);
			//获取分类商品
			$db_pre = C('DB_PREFIX');
			$ai_table = $db_pre . 'shop_expand_item';
			$items =  M('shop_expand_item')->field($ai_table.'.*,
					    i.id, b.img,b.title,i.goods_stock,b.originplace,i.price as price')
						    ->join($db_pre.'item i ON i.id=' . $ai_table . '.item_id')
						    ->join($db_pre.'item_base b ON i.baseid=b.id')
						    ->where(array('expand_id'=>$id))->select();
				
			//查看满减商品
			$fullcutwhere['type'] = 0;
			$fullcutwhere['is_close'] = 0;
			$fullcutwhere['start_time'][] = array('elt', date('Y-m-d'));
			$fullcutwhere['end_time'][] = array('egt', date('Y-m-d'));
			foreach ($items as &$item){
				$fullcutwhere['condition'] = $item['id'];;
				$fullcut = D ( 'promotion' )->where($fullcutwhere)->order('id desc')->find();
				if($fullcut){
					$item['fullcut'] = 1;
				}
			}
			$this->assign('item_list', $items);
				
				
			if (IS_AJAX) {
				$resp = $this->fetch('public:waterfall');
				$data = array(
						'isfull' => 1,
						'html' => $resp
				);
				//数据查询完
				$this->ajaxReturn(1, '', $data);
			} else {
				$this->display();
			}
			//$this->display('item:index');
		}
	}
	
	public function limit_ajax() {
		$tag = $this->_get('tag', 'trim'); //标签
		$sort = $this->_get('sortId', 'trim'); //排序
		//Log::write('sort by >>' . $sort);
		switch ($sort) {
            case '0':
                $order =  'i.buy_num  DESC, ';
                break;
            case '1':
                $order =  ' award_value DESC ,';
                break;
            case '2':
                $order = 'award_value ASC,';
                break;
            case '3':
                $order =  'i.add_time DESC,';
                break;
        }
        $order = ' (goods_stock>0) DESC, '.$order . ' i.ordid DESC,id DESC ';
		$where = array();
		// dump($where);
		$tag && $where['intro'] = array('like', '%' . $tag . '%');
		
		//$this->wall_item($where, $order);
		
		$spage_size = 5;// C('pin_wall_spage_size'); //每次加载个数
		$p = $this->_get('p', 'intval', 1); //页码
		
		$where = array(); 
		$where['type'] = 1;
		$where['uid'] = $this->shopId;
		$where['start_time'][] = array('elt', date('Y-m-d H:i:s'));
		$where['end_time'][] = array('egt', date('Y-m-d H:i:s'));
		
		$count =  D ( 'promotion')->where($where)->count();
		$start = ($p - 1)*$spage_size;
		$db_pre = C('DB_PREFIX');
		$ai_table = $db_pre . 'promotion';
		unset($where['uid']);
		$where[$ai_table.'.uid'] =  $this->shopId;
		$select =  D ( 'promotion' )->field($ai_table.'.*,i.comments, 
		    i.price as price,b.originplace,b.goodsId,b.title,b.img')
					->join($db_pre.'item i ON i.id=' . $ai_table . '.condition' 
					    . ' and i.status = 1' )
					->join($db_pre.'item_base b ON i.baseid=b.id')
					->where($where)->order($order)->limit($start.','.$spage_size);
		$list = $select->select();
		////Log::write(D ( 'promotion' )->getLastSql(), 'DEBUG', 'DEBUG');
		//dump($list);
		$this->assign('list', $list);
		$resp = $this->fetch('public:limitbuyList');
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
	
	function likefun_ajax(){
		$likeval = $_POST['likeval'];
		$goodid = $_POST['goodid'];
		
		//$map['id'] = $goodid;
		$likeModel = M('item_like');
		
		if($likeval == 1){
			$data['uid'] =  $this->shopId;
			$data['userid']= $this->visitor->info['id'];
			$data['id'] = $goodid;
			$data['add_time'] = date("Y-m-d H:i:s");
			$insertRet = $likeModel->add($data,null,true);
				
		}else{
			
			$map['id'] = $goodid;
			$deleteRet = $likeModel->where($map)->delete();
			
		}
		
	//	dump($likeval);
		$this->ajaxReturn(1,L('operation_success'),$goodid);
		
	}
	
}