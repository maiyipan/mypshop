<?php
class promo_codeAction extends backendAction{
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'promo_code' );
	}
	public function  index(){
		$where = $this->_search();
		$whereshop['uid'] = $where['uid'] = array('in',$this->getUidList());
		$shopname = M('shop')->field('uid,name')->where($whereshop)->select();
		$shopnamearr= array();
		foreach ($shopname as $key=>$value){
			$shopnamearr[$value['uid']]=$value['name'];
		}
		$this->assign('shopname',$shopnamearr);
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		$select =  $this->_mod->where($where)->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		if($this->getUidList() == $this->shopId()){
			$this->assign('shopstatus',0);
		}else {
			$this->assign('shopstatus',1);
		}
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	public function edit(){
		if(IS_POST){
			$shop_prom  = M('shop_promo');
			$promo_code = $shop_prom->where(array('shopid'=>$this->shopId()))->find();
			$data['shopid'] = $this->shopId();
			$data['status'] = $this->_post('status','trim');
			$data['money']	= $this->_post('money','trim');
			$data['time']	= $this->_post('time','trim');
			$data['gift']	= $this->_post('gift','trim');
			$data['gift_time'] = $this->_post('gift','trim');
			$shopname = M('shop')-> field('name')->where('uid = '.$this->shopId())->find();
			$data['shopname'] = $shopname['name'];
			if(empty($promo_code)){
					$shop_prom->add($data);//如果不存在就新建
					$this->success(L ( 'operation_success' ));
			}else {
					$data['id'] = $promo_code['id'];
					$shop_prom->save($data);//如果穿在则修改
					$this->success(L ( 'operation_success' ));
			}
		}else {
			$where['shopid'] = array('in', $this->getUidList());
			$info = M('shop_promo')->where($where)->find();
			$this->assign('info',$info);
			$this->display();
		}
	}
	
	public function _search(){
		$map = array();
		($begintime_min = $this->_request('begintime_min', 'trim')) && $map['begintime'][] = array('egt', $begintime_min) ;
		($begintime_max = $this->_request('begintime_max', 'trim'))  && $map['begintime'][] = array('elt', $begintime_max) ;
		($expiretime_min = $this->_request('expiretime_min', 'trim')) && $map['expiretime'][] = array('egt', $expiretime_min) ;
		($expiretime_max = $this->_request('expiretime_max', 'trim')) && $map['expiretime'][] = array('elt', $expiretime_max) ;
	
		$random = $this->_request('random', 'trim');
		
		$random && $map['random'] = array('like','%'. $random .'%');
		$shopname = $this->_request('shopname', 'trim');
		
		$shopname && $map['shopname'] = array('like','%'. $shopname .'%');
		$this->assign('search', array(
				'begintime_min' => $begintime_min,
				'begintime_max' => $begintime_max,
				'expiretime_min' => $expiretime_min,
				'expiretime_max' => $expiretime_max,
				'random' => $random,
				'shopname'=>$shopname,
		));
		return $map;
	}
	/**
	 * 店铺开启状态
	 */
	public function shop_edit(){
		$where = $this->_search();
		$whereshop['uid']= $where['shopid'] = array('in', $this->getUidList());
		$shopname = M('shop')->field('uid,name')->where($whereshop)->select();
		$shopnamearr= array();
		foreach ($shopname as $key=>$value){
			$shopnamearr[$value['uid']]=$value['name'];
		}
		$this->assign('shoplist',$shopnamearr);
		$list_info = M('shop_promo')->where($where)->select();
		$this->assign('list',$list_info);
		$this->display();
	}
}