<?php
class limitbuyAction extends backendAction {
	
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'limitbuy' );
	}
	
	public function index() {
		$where['uid'] = array('in', $this->getUidList());
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		$db_pre = C('DB_PREFIX');
		$ai_table = $db_pre . 'limitbuy';
		$list =  $this->_mod->where($where)->limit($pager->firstRow.','.$pager->listRows)->select();
		$prefix = C(DB_PREFIX);
		foreach ($list as &$l){
			$map['limitbuy_id'] = $l['id'];
			$items  = M('limitbuy_item')->field($prefix.'limitbuy_item.limitbuy_price, i.id,b.title,i.price original_price')
			->join($prefix.'item i ON i.id='.$prefix.'limitbuy_item.item_id')
			->join($prefix.'item_base b ON i.baseid=b.id')
			->where($map)->order('id desc')->select();
			$l['items'] = $items;
		}
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	
	public function add() {
		if (IS_POST) {
			$savedata['uid'] = $this->shopId();
			$savedata['name'] = $this->_post ( 'name', 'trim' );
			$savedata['is_close'] = $this->_post ( 'is_close', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['intro'] = $this->_post ( 'intro' );
			$savedata['add_time'] = date("Y-m-d H:i:s");
			$res = $this->_mod->add($savedata);
			if($res){
				//添加商品
				$items = $this->_post('items', ',');
				if( $items ){
					foreach( $items['itemid'] as $key=>$val ){
						$item['limitbuy_id'] = $res;
						$item['item_id'] = $val;
						$item['original_price'] = $items['original_price'][$key];
						$item['limitbuy_price'] = $items['limitbuy_price'][$key];
						M('limitbuy_item')->add($item);
					}
				}
				$this->success ( L ( 'operation_success' ), U('limitbuy/index') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$this->display ();
		}
	}
	
	public function edit() {
		if (IS_POST) {
			$id = $this->_post ( 'id', 'trim' );
				
			$savedata['name'] = $this->_post ( 'name', 'trim' );
			$savedata['is_close'] = $this->_post ( 'is_close', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['intro'] = $this->_post ( 'intro' );
			$res =  $this->_mod->where(array('id'=>$id))->save($savedata);
			if(false !== $res){
				//删除之前的
				$map['limitbuy_id'] = $id;
				M('limitbuy_item')->where($map)->delete();
				//添加商品
				$items = $this->_post('items', ',');
				if( $items ){
					foreach( $items['itemid'] as $key=>$val ){
						$item['limitbuy_id'] = $id;
						$item['item_id'] = $val;
						$item['original_price'] = $items['original_price'][$key];
						$item['limitbuy_price'] = $items['limitbuy_price'][$key];
						M('limitbuy_item')->add($item);
					}
				}
				$this->success ( L ( 'operation_success' ), U('limitbuy/index') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$id = $this->_get('id', 'intval');
			$info = $this->_mod->find($id);
			if(empty($info)){
				$this->error ( L ( 'illegal_parameters' ), U('limitbuy/index'));
			}
			$prefix = C(DB_PREFIX);
			$map['limitbuy_id'] = $id;
			$items  = M('limitbuy_item')->field($prefix.'limitbuy_item.limitbuy_price, item_id,b.title,i.price original_price')
					  ->join($prefix.'item i ON i.id='.$prefix.'limitbuy_item.item_id')
					  ->join($prefix.'item_base b ON i.baseid=b.id')
					  ->where($map)->select();
			
			$this->assign('items', $items);
			$this->assign('info', $info);
			$this->display ();
		}
	}
	
	public function delete(){
		$ids = $this->_request('id');
		if ($ids) {
			if (false !== $this->_mod->delete($ids)) {
				$this->ajaxReturn(1, L('operation_success'));
			} else {
				$this->ajaxReturn(0, L('operation_failure'));
			}
		} else {
			$this->ajaxReturn(0, L('illegal_parameters'));
		}
	}
	
	public function items() {
		$id = $this->_get('id', 'intval');
		$info = $this->_mod->find($id);
		if(empty($info)){
			$this->error ( L ( 'illegal_parameters' ), U('limitbuy/index'));
		}
		$prefix = C(DB_PREFIX);
		$map['limitbuy_id'] = $id;
		$items = M('limitbuy_item')->field($prefix.'limitbuy_item.limitbuy_price, item_id,b.title,i.price original_price')
		->join($prefix.'item i ON i.id='.$prefix.'limitbuy_item.item_id')
		->join($prefix.'item_base b ON i.baseid=b.id')
		->where($map)->select();
		$this->assign('items', $items);
		$this->assign('info', $info);
		$this->display ();
	}
	
	public function delete_items(){
		$limitbuy_id = $this->_request('limitbuy_id');
		$ids = $this->_request('id');
		if ($limitbuy_id && $ids) {
			$where['limitbuy_id'] = $limitbuy_id;
		    $where['item_id']     =  array('IN', $ids);
			if (false !== M('limitbuy_item')->where($where)->delete()) {
				echo M('limitbuy_item')->_sql();
				$this->ajaxReturn(1, L('operation_success'));
			} else {
				$this->ajaxReturn(0, L('operation_failure'));
			}
		} else {
			$this->ajaxReturn(0, L('illegal_parameters'));
		}
	}
	

	public function search(){
		if (IS_POST) {
			$prefix = C(DB_PREFIX);
			$uid = $this->shopId();
			$where[$prefix.'item.uid'] = array('in', $this->getUidList());
			$where['_string'] = " status = 1";
			$keyword = $this->_request ( 'keyword', 'trim' );
			if($keyword){
				$where['_string'] .= " and title like '%$keyword%' or ".$prefix."item.goodsId = '$keyword'";
			}
			$list = M('item')->field($prefix.'item.id, price,b.goodsId,b.title,b.img')
					->join($prefix.'item_base as b ON '.$prefix.'item.baseid=b.id')
					->where($where)->limit('0,20')->select();
			//Log::write(M('item')->getLastSql(), 'DEBUG');
			foreach ($list as &$item){
				$item['img'] = attach($item['img']);
			}
			$this->ajaxReturn(1, '', $list);
		}else{
			if (IS_AJAX) {
				$response = $this->fetch();
				$this->ajaxReturn(1, '', $response);
			} else {
				$this->display();
			}
		}
	}
	
	
}