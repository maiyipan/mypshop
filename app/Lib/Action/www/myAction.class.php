<?php
class myAction extends userbaseAction {
	/**
	 * 首页
	 */
	public function index() {
		$this->weixinSDK();
		$promo_code = M('promo_code')->field('random')->where(array('userId'=>$this->visitor->info['id'],'uid'=>$this->shopId))->find();
		$this->assign('promo_code',$promo_code);
		$this->assign ( 'currentid', 5);
		//需要重新加载个人信息
		$user = M ( 'user' )->where ( array ('id' => $this->visitor->info ['id'] ) )->find();
		$card = M('card')->where(array('id'=>$this->visitor->info['id']))->find();
		$this->assign('card',$card);
		$this->assign('visitor', $user);
		$this->display ();
	}
	/**
	 * >评价
	 */
	public function publishEvaluation(){

		$itemMap['userId'] = $this->visitor->info['id'];
		$itemMap['status']  = 4;
// 		$itemMap['uid'] =  $this->shopId;
		$orderModel = M('item_order');
		$orderResult = $orderModel->where ( $itemMap )->select ();
		$order_detail = M ( 'order_detail' );
		$commentModel = M ( 'item_comment' );
		
		foreach ( $orderResult as $key => $val ) {
			$order_details = $order_detail->where ( "orderId='" . $val ['orderId'] . "'" )->order ( 'flag desc' )->select ();
			foreach ( $order_details as $val ) {
				$items = array (
						'title' => $val ['title'],
						'img' => $val ['img'],
						'price' => $val ['price'],
						'quantity' => $val ['quantity'],
						'itemId' => $val ['itemId'],'flag' => $val['flag'],'info' =>$val['info'] );
				$orderResult [$key] ['items'] [] = $items;
			}
		}
		$map['status'] = 4;
		$commentResult = $commentModel->where($where)->select();
		$this->assign ( 'status', $status );
		$this->assign('item_orders',$orderResult);
		$this->display ();

}
	/**
	 * 优惠券  
	 */
	public function coupon(){
		$uid=$this->visitor->info['id'];
		$phone= M('user')->field('mobile_num')->where('id='.$uid)->limit(1)->find();
		$phone=$phone['mobile_num'];
		$time=date("Y-m-d",time());
		//可用优惠券
		$discoutlist=M('coupons_code')->join('LEFT JOIN weixin_discount ON weixin_discount.random = weixin_coupons_code.random')->where('uid = ' ."'$uid'" .'  and type= ' . "'Z'" . ' and expiretime >= ' ."'$time'" .' and valid=0' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();
		$full_cutlist=M('coupons_code')->join('LEFT JOIN weixin_full_cut ON weixin_full_cut.random = weixin_coupons_code.random')->where('uid = ' ."'$uid'" .'  and type= ' . "'M'" . ' and expiretime >= ' ."'$time'" .' and valid=0' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();
		$giftlist=M('coupons_code')->join('LEFT JOIN weixin_gift ON weixin_gift.random = weixin_coupons_code.random')->where('uid = ' ."'$uid'" .'  and type= ' . "'B'" . ' and expiretime >= ' ."'$time'" .' and valid=0' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();		
		//Log::write(M('coupons_code')->getLastSql(), 'DEBUG');
		$count=count($discoutlist)+count($full_cutlist)+count($giftlist);
		$this->assign('count',$count);
		$this->assign('full_cut',$full_cutlist);
		$this->assign('gift',$giftlist);
		$this->assign('cart',$discoutlist);
		//不可用优惠券
		$nodiscoutlist=M('coupons_code')->join('LEFT JOIN weixin_discount ON weixin_discount.random = weixin_coupons_code.random')->where('uid = ' ."'$uid'" .'  and type= ' . "'Z'" . ' and  ( expiretime < ' ."'$time'" .' or valid=1 )' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();
		$nofull_cutlist=M('coupons_code')->join('LEFT JOIN weixin_full_cut ON weixin_full_cut.random = weixin_coupons_code.random')->where('uid = ' ."'$uid'" .'  and type= ' . "'M'" . ' and ( expiretime < ' ."'$time'" .' or valid=1 )' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();
		$nogiftlist=M('coupons_code')->join('LEFT JOIN weixin_gift ON weixin_gift.random = weixin_coupons_code.random')->where('uid = ' ."'$uid'" .'  and type= ' . "'B'" . ' and  ( expiretime < ' ."'$time'" .' or valid=1 ) ' . '  and  weixin_coupons_code.shopid = '."'$this->shopId'")->select();
		//Log::write(M('coupons_code')->getLastSql(), 'DEBUG');
		$nocount=count($nodiscoutlist)+count($nofull_cutlist)+count($nogiftlist);
		$this->assign('nocount',$nocount);
		$this->assign('nofull_cut',$nofull_cutlist);
		$this->assign('nogift',$nogiftlist);
		$this->assign('nocart',$nodiscoutlist);
		
		$list=M('coupons_url')->order('urlid desc')->limit(1)->find();
		
		$this->assign('list',$list);
		$this->display ();
	}
	/** 
	 * 优惠码兑换优惠券
	 *  */
	public function Coupon_exchange(){
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
	/**
	 * 优惠券详情
	 * @author maiyipan
	 */
	public function coupon_details(){
		$random = $this->_request('type','trim').$this->_request('random','trim');
		if($this->_request('type','trim') == Z){
			$list = M('discount')->where(array('random'=>$this->_request('random','trim')))->find();
			$list['title'] = $list['discount'].'折优惠';
		}
		if($this->_request('type','trim') == M){
			$list = M('full_cut')->where(array('random'=>$this->_request('random','trim')))->find();
			$list['title'] = '满'.$list['full'].'减'.$list['cut'];
		}
		if($this->_request('type','trim') == B){
			$list = M('gift')->where(array('random'=>$this->_request('random','trim')))->find();
			$list['title'] = $list['gift'].'代金券';
		}
		$shop_name = M('shop')->where(array('uid'=>$this->shopId))->find();
		$list['random'] = $random;//优惠券编码
		$list['shop_name'] = $shop_name['name']; 
		$this->assign('list',$list);		
		$this->display();
	}
	/** 
	 *获取优惠券 
	 *@author maiyipan
	 **/
	public function pulldown(){
		
		if(!empty($_GET['discount'])){
			$where = array(
					'urlid'=>$_GET['urlid'],					//优惠券urlid
					'userid'=>$this->visitor->info['id'],//用户id号
					'discount'=>$_GET['discount'],
					'begintime' => $_GET['begintime'],			//活动开始时间
					'expiretime' =>$_GET['expiretime'] 		//活动结束时间
					);
			//Log::write('urlid>>>'.$_GET['urlid']);
		}
		if(!empty($_GET['full'])||!empty($_GET['cut'])){
			$where = array(
					'urlid'=>$_GET['urlid'],					//优惠券urlid
					'userid'=>$this->visitor->info['id'],//用户id号
					'full'=>$_GET['full'],
					'cut'=>$_GET['cut'],
					'begintime' =>$_GET['begintime'] ,			//活动开始时间
					'expiretime' =>$_GET['expiretime'] ,		//活动结束时间
			);
		}
		if(!empty($_GET['gift'])){
			$where = array(
					'urlid'=>$_GET['urlid'],					//优惠券urlid
					'userid'=>$this->visitor->info['id'],//用户id号
					'gift'=>$_GET['gift'],
					'begintime' => $_GET['begintime'],			//活动开始时间
					'expiretime' => $_GET['expiretime'],		//活动结束时间
			);
		}
		$userinfo = M('coupons_card')->where($where)->find();
		//Log::write('>>>>'.M('coupons_card')->getLastSql(), 'DEBUG');
		if(!empty($userinfo)){
			$this->error('您已经领取过优惠券',U('index/index',array('sid'=>$shopid)));
		}else {
				if(!empty($_GET['discount'])){
					$where_arr = array('discount'=>$_GET['discount'],'urlid'=>$_GET['urlid']); 
					$cardID  = M('coupons_card')->where($where_arr)->max('cardid');
					//Log::write(M('coupons_card')->getLastSql(), 'DEBUG');
				}
				if(!empty($_GET['full'])||!empty($_GET['cut'])){
					$cardID  = M('coupons_card')->where('full='.$_GET['full'].' and cut='.$_GET['cut'])->max('cardid');
					//Log::write(M('coupons_card')->getLastSql(), 'DEBUG');
				}
				if(!empty($_GET['gift'])){
					$cardID  = M('coupons_card')->where('gift='.$_GET['gift'])->max('cardid');
					//Log::write(M('coupons_card')->getLastSql(), 'DEBUG');
				}
				$userinfo= $this->visitor->info['id'] ; // 用户id
				$discount= $_GET['discount'];			//折扣
				$full    = $_GET['full'];				//满
				$cut     = $_GET['cut'];				//减
				$gift    = $_GET['gift'];				//礼金
				$share	 = $_GET['share'];
				$cardID += $_GET['id'];
				$begintime =$_GET['begintime'];
				$expiretime = $_GET['expiretime'];
				$urlid = $_GET['urlid'];
				$this->coupon_card($cardID, $userinfo, $discount, $full, $cut, $gift, $begintime, $expiretime, $share,$urlid);
		}
	}
	/**
	 * 优惠券生成
	 * @param unknown $cardID
	 * @param unknown $userinfo
	 * @param unknown $discount
	 * @param unknown $full
	 * @param unknown $cut
	 * @param unknown $gift
	 * @param unknown $begintime
	 * @param unknown $expiretime
	 * @param unknown $share
	 * @param unknown $random
	 */
	public function coupon_card($cardID,$userinfo,$discount,$full,$cut,$gift,$begintime,$expiretime,$share,$urlid){
		$num="1";
		$infos = M('coupons_card')->select();
		$exclude_codes_array = array();
		if($infos){
			$x = 0;
			foreach ($infos as $r => $infos){
				$exclude_codes_array[$x] = $infos['random'];
				$x ++;
			}
		}
		$random = $this->generate_promotion_code($num,$exclude_codes_array,8);
		//Log::write('<<<<urlid>>>'.$urlid);
		$item_info = M('coupons_url')->field('itemtypename,typeid')-> where('urlid='.$urlid)->find();//获取指定商品标识
		if($cardID < $share || $cardID == $share){
			for ($i = 0;$i<$num;$i++){
				$dataList[]=array(
						'urlid'=>$urlid,
						'cardid'=>$cardID,
						'userid'=>$userinfo,
						'shopid'=>$this->shopId,
						'discount'=>$discount,				//type:折扣
						'full'=>$full,						//type:满减
						'cut'=>$cut,						//type:满减
						'gift'=>$gift,						//type:礼金
						'surplus'=>$gift,
						'begintime' => $begintime,			//活动开始时间
						'expiretime' => $expiretime,		//活动结束时间
						'share'=>$share,					//活动优惠券总数量
						'random' => "H".$random [$i],		//优惠券码
						'createtime'=>date("Y-m-d H-m-s") , //创建时间
						//指定商品字段
						'itemtypename'=>$item_info['itemtypename'], //是否指定商品1是0否
						'typeid'=>$item_info['typeid']		  //指定商品编码
						
				);
			}
			M('coupons_card')->addAll($dataList);
			if($discount != null){
				$code_list =array(
						'type'=>Z,
						'random'=>"H".$random[0],					//兑换码
						'uid'=>$userinfo,					//领取人员
						'receive_time'=>date("Y-m-d H:i:s"),//领取时间
						'shopid'=>$this->shopId
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
						'shopid'=>$this->shopId
				);
				M('coupons_code')->add($code_list);
				M('full_cut')->addAll($dataList);
			}elseif ($gift != null){
				$code_list =array(
						'type'=>B,
						'random'=>"H".$random[0],					//兑换码
						'uid'=>$userinfo,						//领取人员
						'receive_time'=>date("Y-m-d H:i:s"),//领取时间
						'shopid'=>$this->shopId
				);
				M('coupons_code')->add($code_list);
				M('gift')->addAll($dataList);
			}	
			
			$this->success('优惠券领取成功',U('index/index',array('sid'=>$shopid)));
		}else {
			
			$this->error('优惠券已经领取完毕',U('index/index',array('sid'=>$shopid)));
		}		
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
	
	/**
	 * 申请会员卡
	 */
	public function applyMerbership(){
		$this->display ();
	}
	/**
	 * 激活会员卡
	 */
	public function activateMenbership(){
		$this->display ();
	}
	/**
	 * 收藏
	 */
	public function favorable(){
		$likeMap ['uid'] = $this->shopId;
		$likeMap ['userid'] = $this->visitor->info ['id'];
		$likeItemModel = M ( 'item_like' );
		$retLikeId = $likeItemModel->where ( $likeMap )->field ( 'id' )->select ();
		if ($retLikeId == null) {
			$flag = 0;
		} else {
			$mapGoodsCfg = array_column ( $retLikeId, 'id' );
			$mapGoods ['id'] = array (
					'in',
					$mapGoodsCfg 
			);
			$goodIdModel = D ( 'item' );
			$retGoodsId = $goodIdModel->relation(true)->where ( $mapGoods )->select ();
			$flag = 1;
			$this->assign ( 'item_list', $retGoodsId );
		}
		$this->assign ( 'flag', $flag );
		$this->display ();
	}
	/**
	 * 收货地址
	 */
	public function address(){
		$address_list = M ( 'user_address' )->where ( array ('uid' => $this->visitor->info ['id'],'shopid'=>$this->shopId ) )->order('moren desc')->select ();
		//Log::write(M ( 'user_address' )->getLastSql(), 'DEBUG');
		$this->assign ( 'address_list', $address_list );
		if ($_SESSION ['isjiesuan'] != null ) {
			$this->assign('isjiesuan', '0');
		} else {
			$this->assign('isjiesuan', '1');
		}
		$this->display ();
	}
	/**  
	 * 新增、编辑地址
	 */
	public function editAddress(){
		if (IS_POST) {
			$consignee = $this->_post ( 'consignee', 'trim' );
			$address = $this->_post ( 'address', 'trim' );
			$mobile = $this->_post ( 'mobile', 'trim' );
			$sheng = $this->_post ( 'sheng', 'trim' );
			$str = explode(" ",$sheng);
			$sheng = $str[0];
			$shi = $str[1];
			$qu = $str[2];
			$id = $this->_post ( 'id', 'intval' );
			if ($id) {
				$result = M ( 'user_address' )
					->where ( array ('id' => $id, 'uid' => $this->visitor->info ['id'] ) )
					->save ( array ('consignee' => $consignee, 'address' => $address, 
							'mobile' => $mobile, 'sheng' => $sheng, 'shi' => $shi, 'qu' => $qu ,'shopid'=>$this->shopId ) );
				if (false !== $result) {
					$this->ajaxReturn(1, L ( 'add_address_success' ));
				} else {
					$this->ajaxReturn(0, L ( 'add_address_failed' ));
				}
			} else {
				$uid= $this->visitor->info['id'];
				$shopid = $this->shopId;
				$id=M('user_address')->where(array('uid'=>$uid,'shopid'=>$shopid))->select();
				if($id){
					//如果不是第一地址，创建非默认地址
					$result = M ( 'user_address' )->add ( array ('uid' => $this->visitor->info ['id'], 'consignee' => $consignee,
							'address' => $address, 'zip' => $zip, 'mobile' => $mobile, 'sheng' => $sheng, 'shi' => $shi, 'qu' => $qu ,'shopid'=>$this->shopId ,'moren'=>0) );
				}else {
					//第一地址创建为默认地址
					$result = M ( 'user_address' )->add ( array ('uid' => $this->visitor->info ['id'], 'consignee' => $consignee,
							'address' => $address, 'zip' => $zip, 'mobile' => $mobile, 'sheng' => $sheng, 'shi' => $shi, 'qu' => $qu ,'shopid'=>$this->shopId ,'moren'=>1) );
				}
				
				if (false !== $result) {
					$this->ajaxReturn(1, L ( 'add_address_success' ));
				} else {
					$this->ajaxReturn(0, L ( 'add_address_failed' ));
				}
			}
			exit;
		}else{
			$id = $this->_get ( 'id', 'intval' );
			$address = M ( 'user_address' )->where ( array ('id' => $id ) )->find ();
			$this->assign ( 'info', $address );
			$this->display ();
		}
	}
	/**
	 * 删除地址
	 */
	public function deleteAddress(){
		$id = $this->_get ( 'id', 'intval' );
		$ret = M ( 'user_address' )->where ( array ('id' => $id) )->delete ();
		$save =M ( 'user_address' )->where(array('uid'=>$this->visitor->info['id'],'shopid'=>$this->shopId))->limit(1)->save(array('moren'=>1));
		if (false !== $ret) {
			$this->ajaxReturn(1, L ( 'add_address_success' ));
		} else {
			$this->ajaxReturn(0, L ( 'add_address_failed' ));
		}
	}
	/**
	 * 默认地址
	 */
	public function morenAddress(){
		if (IS_POST) {
			$id = $this->_post ( 'id', 'intval' );
			if ($id) {
				M('user_address')->where(array('uid' => $this->visitor->info ['id']))->save (array('moren' =>0));
				$result = M('user_address')->where(array('id' => $id))->save (array('moren' =>1));
				if (false !== $result) {
					$this->ajaxReturn(1, L ( 'add_address_success' ));
				} else {
					$this->ajaxReturn(0, L ( 'add_address_failed' ));
				}
				$this->ajaxReturn(0, L ( 'add_address_failed' ));
			} 
			exit;
		}else{
			$id = $this->_get ( 'id', 'intval' );
			$address = M ( 'user_address' )->where ( array ('id' => $id,'moren' => 1 ) )->find();
			$this->ajaxReturn(1, L ( 'add_address_success' ), $address);
		}
	}
	/**
	 * 在线咨询
	 * */
	public function onlinechat(){

		$map['uid'] = $this->shopId;

		$shopModel = M('shop');
		$result = $shopModel->where($map)->find();
		
		$phoneData['tel'] = $result['tel'];
		//$this->assign ( 'address_list', $address_list );
		//echo 'online'."<br>"; / ok
		//dump($phoneData);
		$this->assign('phonedata',$phoneData);
		$this->display ();
	}
	/**
	 * 关于佳鲜
	 * */
	public function aboutikshop(){

		$settingModel = M('setting');
		
		$map['name'] = 'about_jx';
		
		$result = $settingModel->where($map)->select();
		$this->assign('data',$result);
		$this->display ();
		
	}
	public function postComment(){
		$sid = $this->shopId;
		$shopModel = D('shop');
		$pid =  $shopModel->getUidForP($sid); //获取子店对应的父店对应的id，即对应的appid等
		$wxInterface = new WeixinInterface ( $pid );
		$accessTokenData = $wxInterface->getAccess_token();
		$itemId = $this->_post ( 'itemId', 'intval' ); // 商品ID
		$orderId = $this->_post ( 'orderId', 'trim' ); 
		$content = $this->_post ( 'content', 'trim' );
		$point = $this->_post ( 'point', 'intval' );
		$serverIds = $this->_post ( 'serverIds', 'trim' );
		
		foreach(explode(',',$serverIds) as $serverId){
			$accessToken = $accessTokenData['access_token'];'c5eYJhbVxbMRUKp47FUakRo8kdO6q1dbPQLl_Ehszw7XK8f9IM1Pbg2P65VNmkC88YKQ9nql6ewjVMOWVMmj5-_DvE6BCqJi1e4e5GSvipsJSXeAFABKP'; 
			$php_path = APP_PATH;
			$imgPath = 'comment/'.uniqid().".jpg";
			$filename = './'.C('pin_attach_path' ).$imgPath;
			$ch=curl_init();
			//Log::write('acctoken:' .  $accessToken . '----sid---' . $serverId);
			$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$serverId";
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
			$wxImg=curl_exec($ch);
			curl_close($ch);
			$fp2=@fopen($filename,'a');
			fwrite($fp2,$wxImg);
			fclose($fp2);
			$img[] = $imgPath;
		}	
		
		$orderItem = M('order_detail')->where(array('orderId'=>$orderId,'itemId'=>$itemId))->find();
		
		$data ['userid'] = $this->visitor->info ['id'];
		$data ['username'] = $this->visitor->info ['username'];
		$data ['uid'] = $this->shopId;
		$data ['point'] = $point;
		$data ['images'] = implode(',',$img);
		$data ['serverIds'] = $serverIds;
		$data ['info'] = $content;
		$data ['title'] = $orderItem ['title'];
		$data ['item_id'] = $itemId;
		$data ['orderId'] = $orderId;
		$data ['add_time'] = time();
		$ret = M('item_comment')->add($data);
		if($ret){
			M('item')->where(array('id'=>$itemId))->setInc('comments',1);
			$this->ajaxReturn ( 1, '评价成功' );
		}
		$this->ajaxReturn ( 0, '评价失败' );
	}
	public function commentHandle(){
		$commentGoodsModel = M ( 'item_comment' );
		if (IS_POST) {
			$i = 1;
			$getOrderId ['orderId'] = $_POST ['orderId'];
			// dump($num);
			$orderItemModel = M ( 'order_detail' );
			$orderItemResult = $orderItemModel->where ( $getOrderId )->select ();
			$itemModel = M ( 'item' );
			foreach ( $orderItemResult as $val ) {
				$data ['userid'] = $this->visitor->info ['id'];
				$data ['username'] = $this->visitor->info ['username'];
				$data ['uid'] = $this->shopId;
				$data ['images'] = $val ['img'];
				$data ['info'] = $_POST ["commentText$i"];
				$data ['title'] = $val ['title'];
				$data ['item_id'] = $val ['itemId'];
				$data ['orderId'] = $getOrderId ['orderId'];
				// $data['point'] = $num;
				$orderitemMap ['id'] = $val ['id'];
				$orderitemMap ['orderId'] = $getOrderId ['orderId'];
				
				$orderItemVal ['flag'] = 0;
				$orderItemupdate = $orderItemModel->where ( $orderitemMap )->save ( $orderItemVal );
				$commentGoodsModel->add ( $data );
				
				$itemSelect ['goodsId'] = $val ['goodsId'];
				
				$itemResult = $itemModel->where ( $itemSelect )->select ();
				
				$itemData ['comments'] = $itemResult [0] ['comments'] + 1;
				$itemModel->where ( $itemSelect )->save ( $itemData );
				$i ++;
			}  
			   
			$commentResult = $commentGoodsModel->where ( $getOrderId )->select ();
			$flag = 0;

			$urlLink = '/my/orderCommentDetial/orderId/'.$getOrderId ['orderId'];
			$this->success('评价成功',  U($urlLink));
			
		} else {
			$this->weixinSDK();
			$orderId = $_GET ['orderId'];
			$prefix = C(DB_PREFIX);
			//获取订单详情列表
			$sql = "SELECT a.*, b.id as bId, b.info ,b.images,b.add_time
					FROM ".$prefix."order_detail a 
					LEFT JOIN ".$prefix."item_comment b ON a.orderId = b.orderId AND a.itemId = b.item_id
					WHERE a.orderId = $orderId";
			$list = M('')->query($sql);
			foreach ($list as &$l){
				if($l['images']){
					$l['images'] =  explode(',',$l['images']);;
				}
			}
// 			$orderItemModel = M ( 'order_detail' );
// 			$orderItemResult = $orderItemModel->where ( $orderitemMap )->select ();
// 			$flag = 1;
// 			$this->assign ( 'list', $list );
// 			$this->assign ( 'flag', $flag );
// 			$this->assign ( 'item_list', $orderItemResult );
			// $this->ajaxReturn(1,L('operation_success'),2);
			$this->assign ( 'list', $list );
			$this->assign ( 'orderId', $orderId );
			$this->display ();
		}

	}
	
	public function orderCommentDetial(){
		$map ['orderId'] = $_GET ['orderId'];
		$commentGoodsModel = M ( 'item_comment' );
		$commentResult = $commentGoodsModel->where ( $map )->select ();
		// dump($commentResult);
		$this->assign ( 'item_list', $commentResult );
		$this->display ();
	}
	/**
	 * 优惠券领取
	 */
	public function receive(){
		if(IS_AJAX){
			$offset = $this->_get('set','intval',1);	//页数
			$page   = 6;						//每次加载条数
			$offset = ($offset - 1)*$page;
			//Log::write('offse>>'.$offset.'pages>>'.$page);
			$count = M('coupons_url')->where(array('shopid'=>$this->shopId,'expiretime'=>array('egt',date('Y-m-d'))))->count();
			$list_info = M('coupons_url')->where(array('shopid'=>$this->shopId,'expiretime'=>array('egt',date('Y-m-d'))))->limit($offset,$page)->select();
			//Log::write(M('coupons_url')->getLastSql(), 'DEBUG');
			foreach ($list_info as $key=>$val){
				$urlid_info = M('coupons_card')->field('urlid')-> where(array('shopid'=>$this->shopId,'userid'=>$this->visitor->info['id'],'urlid'=>$val['urlid']))->find();
				$list_info[$key]['list'] = $urlid_info['urlid'];
				
				//Log::write(M('coupons_card')->getLastSql(), 'DEBUG');
				//Log::write('list'.$list_info[$key]['list']);
			}
			$this->assign('list',$list_info);
			$resp = $this->fetch('public:receivelist');
			
			if(count($list_info) < 6){
				$data = array(
						'html' => $resp,
						'isfull'=>0,
				);
			}else {
				$data = array(
						'isfull' => 1,
						'html' => $resp
				);
			}
			$this->ajaxReturn(1, '', $data);
		}else {
			$this->display();
		}
	
	
	}
	/**
	 * 优惠券领取详情页
	 */
	public function receivecoupon(){
		$list = M('coupons_url')->where(array('urlid'=>$this->_get('urlid','trim')))->find();
		//Log::write(M('coupons_url')->getLastSql(), 'DEBUG');
		$shop = M('shop')->field('name')->where(array('uid'=>$list['shopid']))->find();
		//Log::write(M('shop')->getLastSql(), 'DEBUG');
		//Log::write($shop['name']);
		$list['shop_name'] = $shop['name'];//店铺名称
		
		$this->assign('list',$list);
		$this->display();
	}
}
