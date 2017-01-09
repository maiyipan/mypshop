<?php
class marketAction extends backendAction {
	
	public function _initialize() {
		parent::_initialize ();
	}
	/**
	 * 优惠券列表
	 */
	public function ticket() {
		$where = $this->search();
		$count =  M('coupons_url')->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		
		$select =  M('coupons_url')->where($where)->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	/**
	 * 优惠券url生成
	 * */
	public function url_create(){
		if(IS_POST){
			$sid = $this->shopId();
			$type = $_POST['type'];
			$title=$_POST['title'];
			$sub_title=$_POST['sub_title'];
			$explain=$_POST['explain'];
			$discount=$_POST['discount'];
			$full = $_POST['full'];
			$cut = $_POST['cut'];
			$gift =$_POST['gift'];
			$begintime=$_POST['start_time'];
			$expiretime=$_POST['end_time'];
			$share=$_POST['name'];
			//指定商品			
			$commend = $this->_post ( 'commend' );  //获取类型标签，通用或指定
			$itemid= $this->_post('itemid');		//指定商品id号添加
			$count=count($itemid);
			
			$urlid = M('coupons_url')->max('urlid');
			$urlid ++;
			//Log::write(M('coupons_url')->getLastSql(), 'DEBUG');
			//Log::write('urlid>>>'.$urlid);
			if($type==1){
				$url = C('pin_baseurl')."/my/pulldown/id/1/urlid/".$urlid."/discount/".$discount."/begintime/".$begintime."/expiretime/".$expiretime."/share/".$share."/sid/".$sid;
				$coupons= "折扣";
			}elseif ($type == 2){
				$url = C('pin_baseurl')."/my/pulldown/id/1/urlid/".$urlid."/full/".$full."/cut/".$cut."/begintime/".$begintime."/expiretime/".$expiretime."/share/".$share."/sid/".$sid;
				$coupons= "满减";
			}elseif ($type == 3){
				$url = C('pin_baseurl')."/my/pulldown/id/1/urlid/".$urlid."/gift/".$gift."/begintime/".$begintime."/expiretime/".$expiretime."/share/".$share."/sid/".$sid;
				$coupons= "代金";
			}
			if($commend=="指定商品"){
				$commend=1;
			}else {
				$commend=0;
			}
			$typeid='D'.date('ymdhms');
			$data	= array(
							'urlid'=>$urlid,
							'title'=>$title,			//大标题
							'shopid'=>	$sid,
							'sub_title'=>$sub_title,	//小标题
							'explain'=>$explain,		//内容解释
							'coupons' => $coupons,
							'begintime' => $begintime,
							'expiretime' => $expiretime,
							'share'=>	$share,
							'url'=>	$url,
							'aurl'=>C('pin_baseurl')."/my/receivecoupon/urlid/".$urlid."/sid/".$sid,
							//指定商品字段
							'itemtypename'=>$commend, //是否指定商品1是0否
							'typeid'=>$typeid		  //指定商品编码
							);
			M('coupons_url')->add($data);
			//优惠券指定特定商品
			for ($j= 0;$j<$count;$j++){
				$data_itme []=array(
						'typeid'=>$typeid,
						'itemid'=>$itemid[$j],
				);
			}
			if($commend=1){
				M('coupons')->addAll($data_itme);
			}
			//Log::write(M('coupons_url')->getLastSql(), 'DEBUG');
			$this->success ( '创建优惠券成功。',U('market/ticket'));
		}else {
			$this->display();
		}
	}

	public function url_delete() {
		
		$ids = $this->_request('urlid');
		if ($ids) {
			$delet_info = M('coupons_url')->where(array('urlid'=>$this->_request('urlid')))->delete();
			if ($delet_info) {
				$this->ajaxReturn(1, L('operation_success'));
			} else {
				$this->ajaxReturn(0, L('operation_failure'));
			}
		} else {
			$this->ajaxReturn(0, L('illegal_parameters'));
		}
	}
	
	public function pre(){
		if (IS_AJAX) {
			//Log::write($_GET['urlid']);
			$data =M('coupons_url')->field('urlid,aurl')->where(array('urlid'=>$_GET['urlid']))->find();
			//Log::write(M('coupons_url')->getLastSql(), 'DEBUG');
			//Log::write('data>>>>'.$data['aurl']);
			$qrcode=new ItemQRcode();
			$image=$qrcode->qrcodeNoSave($data['aurl']);
			//Log::write('image>>>'.$image);
			$image = explode('/data/upload/',$image);
			$data_info['image'] = $image[1];
			M('coupons_url')->where(array('urlid'=>$_GET['urlid']))->save($data_info);
			//Log::write(M('coupons_url')->getLastSql(), 'DEBUG');
			$data['image'] = $image[1];
				
			$this->assign('data',$data);
			$response = $this->fetch();
			$this->ajaxReturn(1, '', $response);
		} else {
			$this->display();
		}
	}
   /**
	* 下载
	* */
	public function down () {
		$image_info = M('coupons_url')->where(array('urlid'=>$_GET['urlid']))->find();
		$image = $image = $logo = C('pin_attach_path').$image_info['image'];
		//Log::write($image);
		$filename=realpath($image);  //文件名
		
		$name = date("Ymd-H:i:m");
		Header( "Content-type:   application/octet-stream ");
		Header( "Accept-Ranges:   bytes ");
		Header( "Accept-Length: " .filesize($filename));
		header( "Content-Disposition:   attachment;   filename= {$name}.png");
		echo file_get_contents($filename);
		readfile($filename);
	}
	/**
	 * 
	 * {@inheritDoc}
	 * @see backendAction::_search()
	 */
	private function search() {
	
		//Log::write("_search----dis");
		$map = array();
		($begintime_min = $this->_request('begintime_min', 'trim')) && $map['begintime'][] = array('egt', $begintime_min) ;
		($begintime_max = $this->_request('begintime_max', 'trim'))  && $map['begintime'][] = array('elt', $begintime_max) ;
		($expiretime_min = $this->_request('expiretime_min', 'trim')) && $map['expiretime'][] = array('egt', $expiretime_min) ;
		($expiretime_max = $this->_request('expiretime_max', 'trim')) && $map['expiretime'][] = array('elt', $expiretime_max) ;
	
		$title = $this->_request('title', 'trim');
		$title && $map['title'] = array('like','%'. $title .'%');
		$map['shopid'] = array('in',$this->getUidList());
		$this->assign('search', array(
				'begintime_min' => $begintime_min,
				'begintime_max' => $begintime_max,
				'expiretime_min' => $expiretime_min,
				'expiretime_max' => $expiretime_max,
				'title' => $title,
		));
		return $map;
	}
}