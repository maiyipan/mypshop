<?php
class indexAction extends userbaseAction {
	public function _initialize() {
		parent::_initialize ();
		/* if (! $this->visitor->is_login && in_array ( ACTION_NAME, array (
				'share_item',
				'fetch_item',
				'publish_item',
				'like',
				'unlike',
				'delete',
				'comment' 
		) )) {
			IS_AJAX && $this->ajaxReturn ( 0, L ( 'login_please' ) );
			$this->redirect ( 'index/index' );
		} */
		
	}
	/**
	 * 默认门店页面显示
	 */
	 public function storeList(){
                               	 $this->display();
	 		}
	 public function choiceAddress(){ //登陆
	   		 $this->assign('list',$_SESSION['tudelist']);
         	 $this->display();
         }
	/**
     * 首页 
     */
	public function index() {
		$ip = $_SERVER ["REMOTE_ADDR"];
		if ($ip == '127.0.0.1') {
			$_SESSION['isgetloca'] = '0';
		}
		//begin yzjun  2016年02月29日18:06:53
		 //默认门店判断
		$user_default_shop = M('user_shop')->where('usid = ' .$this->visitor->info['id'].' and isdefault=1')->find();
		//Log::write(M('user_shop')->getLastSql(), 'DEBUG');
		//Log::write('user_shop>>>'.$user_default_shop['shopid'].'>>ifchoose>>'.$_SESSION['ifchoose']);
		$shop_Info = M('shop')->field('uid')->where('pid = 1 or pid = 0')->select();
 		//存在默认店铺
		if(!empty($user_default_shop)){
			//Log::write('111');
			if($user_default_shop['shopid'] != $this->shopId){
				//Log::write('no_shopid');
				//判断定向标识是否匹配
				if($_SESSION['ifchoose'] == 1){
					foreach ($shop_Info as $k=>$v){
						if($v['uid'] == $this->shopId){
							$this->redirect('index/index', array('sid'=> $user_default_shop['shopid']));
						}
					}
					goto end;
				}
				session('ifchoose',0);
				//Log::write('sid>>>'.$user_default_shop['shopid']);
				//重定向到选择店铺
				$this->redirect('index/index', array('sid'=> $user_default_shop['shopid']));
			}else {
				//Log::write('shop');
				//判断定向标识是否匹配
				if($_SESSION['ifchoose'] == 0){
					goto end;
				}
			}
		}else {
			if($_SESSION['ifchoose'] == 1){
				foreach ($shop_Info as $k=>$v){
					if($v['uid'] == $this->shopId){
						$this->redirect('index/storeList', array('sid'=> $this->shopId));
					}
				}
				goto end;
			}
			//Log::write('000');
			$this->redirect('index/storeList', array('sid'=> $this->shopId));
		}
		end:;
		//Log::write('end');   
		//Log::write('shopId-----'.$this->shopId);
		//首页广告
		$map['uid'] = $this->shopId;
		$nowtime = time();
		$map['start_time'][] = array('elt', ($nowtime));
		$map['end_time'][] =   array('egt', ($nowtime));
		$map['status'] = 1;
		$ad = M ( 'ad' );
		$ads = $ad->field ( 'url,content,desc' )->where ( $map )->order ( 'ordid asc' )->select ();
		$this->assign ( 'ad', $ads );
		//热搜
		$searchHot = $this->getSearchHot ();
		$this->assign ( 'searchHot', $searchHot );
		// 历史搜索
		$searchHis = $this->getSearchHis ();
		$this->assign ( 'searchHis', $searchHis );
		
		$this->assign ( 'currentid', 1 );
		
		//获取推广
		$this->activity();
		
		//横幅推广
		$this->banners();
		//店铺列表
		$this->shoplist();
		//店铺公告
		$this->getShopPost();
		
		$this->getGame();
		$this->getSection();
		$this->display ();
	}
	/** 
	 * 获取店铺列表
	*/
	public function ajax_shoploctionlist(){
		$shopModel = D('shop');
		$uid = $this->shopId;
		$pid = $shopModel->getIdByUid($uid);
		$where['uid'] = array(
				'in',$this->getShop()
		);
		$where['status'] = 1;
		($city_names = $this->_request ( 'city_names', 'trim' ))&&$where['city_names'] = array('like','%'.$city_names.'%');
		($name  = $this->_request ('name','trim'))&& $where['name'] = array('like' , '%' .$name. '%');
		$pageset = $_GET['page'] ;//= 1;//获取页数
		$page = 5; //每次加载条数
		$position=$shopModel->where('pid = ' . $pid)->field('id,uid,name,longitude,latitude,smallIcon,city_names,address')->where($where)->select();
		//Log::write($shopModel->getLastSql(), 'DEBUG');//记录查询数据库语句
		$user_default_shop = M('user_shop')->where('usid = ' .$this->visitor->info['id'].'  and isdefault=1')->find();
		$position_info = array();
		foreach ($position as $key=>$val){
			if($user_default_shop['shopid'] == $val['uid']){
				$isdefault = 1;
				//获取自己设置的默认店铺
				if (round ( $_SESSION ['tudelist'] [$val ['uid']] / 1000, 3 )  == 0) {
					$juli = '';
				} else {
					$juli = round ( $_SESSION ['tudelist'] [$val ['uid']] / 1000, 3 ) . 'km';
				}
				$myshoplist = array (
						'distance' => $juli,
						'address' => $val ['address'],
						'isdefault' => $isdefault,
						'id' => $val ['id'],
						'uid' => $val ['uid'],
						'name' => $val ['name'],
						'smallIcon' => $val ['smallIcon'],
						'url' => U ( 'index/index', array (
								'sid' => $val ['uid'] 
						) ) 
				);
			}else {
				$isdefault = 0;
			}
			
			$position_info[]= array(
					'distance' => ($_SESSION ['tudelist'] [$val ['uid']] / 1000),
					'address' => $val ['address'],
					'isdefault' => $isdefault,
					'id' => $val ['id'],
					'uid' => $val ['uid'],
					'name' => $val ['name'],
					'smallIcon' => $val ['smallIcon'],
					'url' => U ( 'index/index', array (
							'sid' => $val ['uid'] 
					) ) 
			);
		}
		//距离倒序
		foreach ($position_info as $key => $value ){
			$rating[$key] = $value['distance'];
			$name[$key] = $value['id'];
		}
		array_multisort($rating, $name, $position_info); 
		foreach ($position_info as $key => $values){
			if ( round($values['distance'],3) == 0) {
				$dis = '';
			} else {
				$dis = round($values['distance'],3).'km';
			}
			$position_info[$key]['distance'] =$dis;
		}
		if(empty($position)){
			$position_info =array();
		}else {
			//店铺列表
			$position_info = array($position_info[$pageset*$page - 5],$position_info[$pageset*$page - 4],$position_info[$pageset*$page - 3],$position_info[$pageset*$page - 2],$position_info[$pageset*$page - 1]);
		}
		//默认列表
		$data = array(
				'myshoplist'=>$myshoplist,
				'status' =>1,
				'loctionlist'=>$position_info,
				'loction'=>1,
				'msg'=>'加载成功'
		);
		//Log::write('pr>>>>'.$position_info[$pageset*$page - 5]['distance']);
		if(empty($position_info[1])){
			$data['loction'] = 0;//加载状态0为加载完毕，1可继续加载
		}
		session('ifchoose',1);
		echo json_encode($data);
	}
	/**
	 * 设置为默认店铺
	 */
	public function ajax_default_shop(){
		$usid = $this->visitor->info['id'];
		$shopid = $this->_post('uid','trim');
		$isdefault = $this->_post('isdefault','trim');
		if(isset($isdefault) && isset($shopid)){
			$user_shop = array(
					'usid'=>$usid,			//用户id
					'shopid'=>$shopid,		//店铺id
					'isdefault'=>$isdefault,//$isdefault=1;
					'islike'=>0,
					'last_time'=>date('Y-m-d H:i:s')
			);
			//默认门店判断
			$user_default_shop = M('user_shop')->where('usid = ' .$this->visitor->info['id'])->find();
			//存在默认店铺
			if(!empty($user_default_shop)){
				//存在默认门店更新数据
				$where = array('usid'=>$usid);
				$user_info =  M('user_shop')->where($where)->save($user_shop);
				$data='更新成功';
			}else {
				//不存在默认门店添加数据
				$user_shop['add_time']= date('Y-m-d H:i:s');
				$user_info =  M('user_shop')->add($user_shop);
				$data='添加成功';
			}
			if($user_info){
				$this->ajaxReturn(1,'设置成功',$data);
			}else {
				$this->ajaxReturn(0,'设置失败');
			}
		}else {
			$this->ajaxReturn(0,'缺少必备字段uid>>'.$shopid.'isdefault>>'.$isdefault);
		}
	}
	/**
	 * 获取前台地理位置
	 * */
	public function getloction(){
		//Log::write('定位');
		$shopModel = D ( 'shop' );
		$uid = $this->shopId;
		$pid = $shopModel->getIdByUid ( $uid );
		
		$lat1 = $_GET ['latitude'] ;//= 22.542032;
		$lng1 = $_GET ['longitude'] ;//= 113.9534;
		//Log::write ( 'at1--' . $lat1 );
		//Log::write ( 'lng1--' . $lng1 );
		$where ['uid'] = array (
				'in',
				$this->getShop () 
		);
		$where ['status'] = 1;
		
		$position = $shopModel->where ( 'pid = ' . $pid )->field ( 'id,uid,name,longitude,latitude,smallIcon' )->where ( $where )->select ();
		$arrayuid = array ();
		$arraykey = array ();
		foreach ( $position as $key => $val ) {
			$lng2 = $val ['longitude'];
			$lat2 = $val ['latitude'];
			$arraykey [$val ['uid']] = $this->getDistance ( $lat1, $lng1, $lat2, $lng2 );
			$arrayuid [] = $this->getDistance ( $lat1, $lng1, $lat2, $lng2 );
		}
		$length = count ( $arrayuid );
		for($i = 0; $i < $length; $i += 2) {
			if (isset ( $arrayuid [$i + 1] ) && $arrayuid [$i] > $arrayuid [$i + 1]) {
				$temp_data = $arrayuid [$i];
				$arrayuid [$i] = $arrayuid [$i + 1];
				$arrayuid [$i + 1] = $temp_data;
			}
		}
		//存入缓存
		session('tudelist',$arraykey);
		$min_data = $arrayuid [0];
		$max_data = $arrayuid [1];
		for($i = 2; $i < $length; $i += 2) {
			if ($min_data > $arrayuid [$i])
				$min_data = $arrayuid [$i];
		}
		for($i = 3; $i < $length; $i += 2) {
			if ($max_data < $arrayuid [$i])
				$max_data = $arrayuid [$i];
		}
		if ($length % 2 != 0 && $max_data < $arrayuid [$length - 1])
			$max_data = $arrayuid [$length - 1];
		//Log::write ( "min>>>" . $min_data . "max>>>" . $max_data );
		$keymin = array_search ( $min_data, $arraykey ); // $key = 2;
		//Log::write('>>keymin>>', $keymin);
		$keymax = array_search ( $max_data, $arraykey );
		$name = M ( 'shop' )->field ( 'name,uid,smallIcon' )->where ( array ('uid' => $keymin ) )->find ();
		$data = array (
				'status' => 1,
				'name' => $name ['name'],
				'smallIcon'=>$name['smallIcon'],
				'url' => U ( 'index/index', array (
						'sid' => $name ['uid']  ,'ifchoose'=>1
				) ) 
		);
		//Log::write ( "getloctionshopname>>" . $data ['url'] );
		echo json_encode ( $data );
	}
	public function getDistance($lat1, $lng1, $lat2, $lng2)
	{
		$earthRadius = 6378137; //approximate radius of earth in meters
	
		/*
		 Convert these degrees to radians
		 to work with the formula
		 */
	
		$lat1 = ($lat1 * pi() ) / 180;
		$lng1 = ($lng1 * pi() ) / 180;
	
		$lat2 = ($lat2 * pi() ) / 180;
		$lng2 = ($lng2 * pi() ) / 180;
	
		/*
		 Using the
		 Haversine formula
	
		 http://en.wikipedia.org/wiki/Haversine_formula
	
		 calculate the distance
		 */
	
		$calcLongitude = $lng2 - $lng1;
		$calcLatitude = $lat2 - $lat1;
		$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
		$stepTwo = 2 * asin(min(1, sqrt($stepOne)));
		$calculatedDistance = $earthRadius * $stepTwo;
	
		return round($calculatedDistance, 8);
	}
	public function getItem($where = array()) {
		foreach ( $where as $color ) {
			//Log::write ( "aa" . "  " . $color );
		}
		$where_init = array (
				'status' => '1' 
		);
		$where = array_merge ( $where_init, $where );
		//Log::write ( "aa" . "  " . $where );
		
		$item = M ( 'item' );
		$items = $item->where ( $where )->select ();
		//Log::write ( $item->getLastSql () );
		return $items;
	}
	public function ajaxLogin() {
		$user_name = $_POST ['user_name'];
		$password = $_POST ['password'];
		
		$user = M ( 'user' );
		$users = $user->where ( "username='" . $user_name . "' and password='" . md5 ( $password ) . "'" )->find ();
		if (is_array ( $users )) {
			$data = array (
					'status' => 1 
			);
			$_SESSION ['user_info'] = $users;
		} else {
			$data = array (
					'status' => 0 
			);
		}
		
		echo json_encode ( $data );
		exit ();
	}
	public function ajaxRegister() {
		$username = $_POST ['user_name'];
		$user = M ( 'user' );
		$count = $user->where ( "username='" . $username . "'" )->find ();
		if (is_array ( $count )) {
			echo 'false';
			// echo json_encode(array('user_nameData'=>true));
		} else {
			echo 'true';
			// echo json_encode(array('user_nameData'=>true));
		}
	}
	
	/**
	 * 商品搜索展示列表 || 所有商品展示列表
	 */
	public function itemList() {
		if (IS_POST) {
			$id = $this->_post ( 'id', 'trim' );
			
			$this->addSearchHis ( $id );
			$where ['title'] = array (
					'like',
					'%' . $id . '%' 
			);
			$where ['intro'] = array (
					'like',
					'%' . $id . '%' 
			);
			
			$where ['_logic'] = 'or';
			$map ['_complex'] = $where;
			$map ['status'] = array (
					'eq',
					'1' 
			);
			
			$item = M ( 'item' );
			$itemList = $item->where ( $map )->select ();
			//Log::write ( $item->getLastSql () );
			
			$this->assign ( 'itemList', $itemList );
			
			$hotSales = $this->hotSales();
			$this->assign ( 'hotList', $hotSales );
			$this->assign ( 'currentid', 2);
			$this->_config_seo ();
			$this->display ();
		} elseif (IS_GET) {
			$id = $this->_request ( 'id', 'trim' );
			$tuijian = $this->_request ( 'tuijian', 'trim' );
			$news = $this->_request ( 'news', 'trim' );
			
			$map = array ();
			if ($id) {
				$this->addSearchHis ( $id );
				$where ['title'] = array (
						'like',
						'%' . $id . '%' 
				);
				$where ['intro'] = array (
						'like',
						'%' . $id . '%' 
				);
				$where ['_logic'] = 'or';
				$map ['_complex'] = $where;
			}
			
			if ($tuijian) {
				$map ['tuijian'] = array (
						'eq',
						$tuijian 
				);
			}
			
			if ($news) {
				$map ['news'] = array (
						'eq',
						$news 
				);
			}
			$map ['status'] = array (
					'eq',
					'1' 
			);
			
			$item = M ( 'item' );
			$itemList = $item->where ( $map )->select ();
			//Log::write ( $item->getLastSql () );
			
			$this->assign ( 'itemList', $itemList );
			$this->assign ('currentid', 2);
			$this->_config_seo ();
			$this->display ();
		} else { // 热搜和历史搜索
			$searchHot = $this->getSearchHot ();
			$this->assign ( 'searchHot', $searchHot );
			$this->display ();
		}
	}
	
	/**
	 * 查询历史搜索
	 */
	public function getSearchHis() {
		return $_SESSION ['searchHis'];
	}
	
	/**
	 * 增加历史搜索
	 * 热搜处理
	 */
	function addSearchHis($keyword) {
		$search_history = M ( 'search_history' );
		$search_history_info = $search_history->where ( 'keyword=' . "'" . $keyword . "'" )->find ();
		if ($search_history_info) {
			$search_history->where ( 'keyword=' . "'" . $keyword . "'" )->setInc ( 'search_times', 1 );
		} else {
			$data ['keyword'] = $keyword;
			$data ['search_times'] = 1;
			$search_history->add ( $data );
		}
		
		if (isset ( $_SESSION ['searchHis'] [$keyword] )) {
			// $this->incNum($id,$num);
			return 1;
		}
		/*
		 * $item = array();
		 * $item['id'] = $id;
		 * $item['name'] = $name;
		 * $item['price'] = $price;
		 * $item['num'] = $num;
		 * $item['img'] = $img;
		 */
		$_SESSION ['searchHis'] [$keyword] = $keyword;
	}
	
	/**
	 * 查询热搜
	 */
	public function getSearchHot($where = array()) {
		$search_history = M ( 'search_history' );
		$search_historys = $search_history
		    ->order ( 'search_times desc' )
		          ->limit ( 6 )->select ();
		//Log::write ( $search_history->getLastSql () );
		return $search_historys;
	}
	
	/**
	 * 清除搜索历史
	 */
	public function deleteSearchHis() {
		$_SESSION ['searchHis'] = array ();
		$data = array ();
		$data ['status'] = '1';
		echo json_encode ( $data );
	}
	
	/**
	 * 查询热销商品
	 * */
	public function hotSales(){
		/* $item = M ( 'item' );
		$itemList = $item->where ('status=1 order by buy_num' )->limit ( 6 )->select ();
		//Log::write('hot>>' . $item->getLastSql(), 'DEBUG'); */
		
		$db_pre = C('DB_PREFIX');
		$item_tb = $db_pre . 'item';
		$item = D( 'item' );
		$map[$item_tb.'.uid'] = $this->shopId;
		$map[$item_tb.'.status'] = 1;
		$field == '' && $field = 'weixin_item.*, base.cate_id,
				          base.brand,base.title,base.intro,base.prime_price,base.img,base.info';
		$itemList = $item->join($db_pre.'item_base base ON base.id = ' . $item_tb . '.baseid')
		   ->field($field)->where ( $map )->order('buy_num desc')->limit ( 6 )->select ();
		return $itemList;
	}
	
	
	
	public function search() {
		/**
		 * 热搜*
		 */
		$searchHot = $this->getSearchHot ();
		$this->assign ( 'searchHot', $searchHot );
			
		/**
		 * 历史搜索
		*/
		$searchHis = $this->getSearchHis ();
		$this->assign ( 'searchHis', $searchHis );
		
		$this->assign ( 'currentid', 3);
		$this->display();
	}
	
	public function doSearch(){
		$keyword = $this->_request( 'keyword', 'trim' );
		if ($keyword != '') {
			$this->addSearchHis ( $keyword );
			$where ['base.title'] = array (
					'like',
					'%' . $keyword . '%'
			);
			$where ['base.intro'] = array (
					'like',
					'%' . $keyword . '%'
			);
			$where ['weixin_item.tag_cache'] = array (
					'like',
					'%' . $keyword . '%'
			);
			$where ['_logic'] = 'or';
			$map ['_complex'] = $where;
		}
		
		$map ['status'] = array (
				'eq',
				'1'
		);
		
		$db_pre = C('DB_PREFIX');
		$item_tb = $db_pre . 'item';
		$item = D( 'item' );
		$map[$item_tb.'.uid'] = $this->shopId;
		$field == '' && $field = 'weixin_item.*, base.cate_id, 
				          base.brand,base.title,base.intro,base.prime_price,base.img,base.info';
		$itemList = $item->
		              join($db_pre.'item_base base ON base.id = ' . $item_tb . '.baseid')
		                ->field($field)->where ( $map )->select ();
		//Log::write ( $item->getLastSql () );
		$this->assign ( 'item_list', $itemList );
		$hotSales = $this->hotSales();
		$this->assign ( 'hotList', $hotSales );
		$this->assign ( 'keyword', $keyword);
		$this->assign ( 'currentid', 3);
		//当前页数 第一页
		$this->assign('p', 1);
		$this->assign('typeid', 6);
		$this->display ();
	}
	
	
	/**
	 * 第一级分类
	 */
	public function sortFirst() {
		
		$shopId = $this->shopId;
// 		$map['uid'] = $shopId;
		$map['status'] = 1;
		$map['pid'] = 0;
		$map['is_index'] = 1;
		
		$list = M ('item_cate')->where($map)->order('ordid')->select();
		$this->assign ( 'list', $list);
		$this->assign ( 'currentid', 2);
		
		$this->display ();
	}
	/**
	 * 第二级分类 TODO
	 */
	public function sortSecond() {
		$id = $this->_get('id', 'intval');
		$fid = $this->_get('fid', 'intval');
		
		$map['status'] = 1;
		$map['pid'] = $fid;
		$leftlist = M ('item_cate')->where($map)->order('ordid')->select();
		$this->assign ( 'leftlist', $leftlist);
		
		if(empty($id)){
			$id = $leftlist[0]['id'];
		}
		
		$list = M ('item_cate')->where(array('pid'=>$id))->order('ordid')->select();
		$this->assign ( 'list', $list);
		$this->assign ( 'fid', $fid);
		$this->assign ( 'cid', $id);
// 		foreach ($list as &$item){
// 			$map['status'] = 1;
// 			$map['pid'] = $item['id'];
// 			$llist = M ('item_cate')->where($map)->select();
// 			foreach ($llist as $l){
// 				$list2[] = $l;
// 			}
// 			$item['children'] = $l;
// 		}
		$this->display ();
	}
	
	
	/**
	 * 分类推广展示
	 * 只显示有效时间内 状态有效 ， 活动为为三条的 推广
	 * */
	public function activity() {
		$this->list_relation = true;
		$date = date('Y-m-d');
		$map['start_time'][] = array('elt', strtotime($date));
		$map['end_time'][] = array('egt', strtotime($date));
		$map['status'] = '1';
		$map['uid'] = $this->shopId;
		$ac_mod = D ('activity');
		$data = $ac_mod->where($map)->order('ordid')->select();  //->relation(true)
		foreach ($data as $val=> $key) {
		  // dump($val . '--' .$key['id'] );
		   $list = M('shop_expand')->where(array('activity_id'=>$key['id'] ))->order('ismain')->select();
// 		   dump(M('shop_expand')->getLastSql(), 'DEBUG');
		   $key['list'] = $list;
		   //dump($key);
		   $data[$val] = $key;
		}
		//dump($data);
// 		dump($ac_mod->getLastSql(), 'DEBUG');
// 		exit();
		$this->assign('activityList', $data);
		
	}
	
	/**
	 * 横幅推广展示
	 * 只显示有效时间内 状态有效 ， 活动为为三条的 推广
	 * */
	public function banners() {
// 		$this->list_relation = true;
		$date = date('Y-m-d');
		$map['start_time'][] = array('elt', strtotime($date));
		$map['end_time'][] = array('egt', strtotime($date));
		$map['status'] = '1';
		$map['uid'] = $this->shopId;
		$ac_mod = D ('banners');
		$data = $ac_mod->where($map)->order('ordid asc')->select(); //->relation(true)
// 	dump($ac_mod->getLastSql(), 'DEBUG');
		// 		exit();
		$this->assign('bannersList', $data);
	}
	
	/**
	 * 店铺列表
	 * */
	public function  shoplist () {
		$shopModel = M('shop');
		$dir = U('index/index/sid/');
		$where['uid'] = array(
				'in',$this->getShop()
		);
		$where['status'] = 1;
		$shopInfo = $shopModel->where($where)->field('uid,name')->select();
		
		$_SESSION['shoplist'] = $shopInfo;
		$this->assign('shopid', $this->shopId);
		$this->assign('website',$dir);
		$this->assign ( 'shopinfo', $shopInfo );
	}
	
	/**
	 * 店铺公告
	 * */
	public function getShopPost() {
	    $sid = $this->getuid();
	    $where['uid'] = $sid;
	    $where['show_notice'] = 1;
	    $post = M('shop_configs')->where($where)->select();
	    $this->assign('postList', $post);
	    
	}
	
	/**
	 * 前端菜单
	 * */
	 public function getSection() {
	     $shopid = $this->shopId;
	     $where['uid'] = $this->getuid();
	     $where['status'] = 0;
	 	 $list = M('section')->where($where)->order('ordid')->select();
	 	 foreach ($list as $val) {
	 	     if ($val['type'] == 0){
	 	         switch ($val['cate']){
                    case 0:
                        $val['url'] = U('score/integration', array(
                            'sid' => $shopid
                        ));
                        break;
                    case 1:
                        $val['url'] = U('market/enterpriceInfo', array(
                            'sid' => $shopid
                        ));
                        break;
                    case 2: //tian tian you jiang 
                        $val['url'] = U('market/enterpriceInfo', array(
                            'sid' => $shopid
                        ));
                        break;
                    case 3:
                        $val['url'] = U('market/limitbuy', array(
                            'sid' => $shopid
                        ));
                        break;
                    case 4:
                        $val['url'] = U('market/recommend', array(
                            'sid' => $shopid
                        ));
                        break;
                    case 5:
                        $val['url'] = U('market/lingxian', array(
                            'sid' => $shopid
                        ));
                        break;
                }
	 	     }
	 	 }
	 	 $this->assign('section', $list);
	 }
	 
	 
	 public function getGame() {
	     //天天有奖
	     $nowtime = time();
	     $where['start_time'][] = array('elt', ($nowtime));
	     $where['end_time'][] =   array('egt', ($nowtime));
	     $where['status']=1;
	     $where['sid'] = $this->shopId;
	     $today=M('game')->field('url')->where($where)->order('')->find();
	     /* dump(M('game')->getLastSql(), 'DEBUG');
	     exit(); */
	     $this->assign('today',$today);
	     return $today['url'];
	     
	 }
}