<?php
class promotionAction extends backendAction {
	
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'promotion' );
	}
	/**
	 * 满减促销
	 */
	public function fullcut() {
		
		$where['uid'] = array('in',$this->getUidList());
		$where['type'] = 0;
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		
		$select =  $this->_mod->where($where)->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	
	public function fullcut_add() {
		//Log::write('full_cut');
		if (IS_POST) {
			$id = $this->_mod->max('id');
			$id ++;
			$savedata['id'] = $id;
			//Log::write('id>>>'.$id);
			$savedata['uid'] = $this->shopId();
			$savedata['name'] = $this->_post ( 'name', 'trim' );
			$savedata['is_close'] = $this->_post ( 'is_close', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['intro'] = $this->_post ( 'intro' );
			$savedata['condition'] = $this->_post ( 'condition' );
			$savedata['award_type'] = $this->_post ( 'award_type' );
			$savedata['award_value'] = $this->_post ( 'award_value' );
			$savedata['reserve'] = $this->_post ( 'reserve' );
			$savedata['type'] = 0;
			$savedata['add_time'] = time();
			$savedata['url'] = C('pin_baseurl')."/item/details/id/".$this->_post ( 'condition' )."/sid/".$this->shopId();
			//Log::write('>>>'.$savedata['url']);
			$res = $this->_mod->add($savedata);
			if($res){
				$this->success ( L ( 'operation_success' ), U('promotion/fullcut') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$this->display ();
		}
	}
	
	public function fullcut_edit() {
		if (IS_POST) {
			$id = $this->_post ( 'id', 'trim' );
			
			$savedata['name'] = $this->_post ( 'name', 'trim' );
			$savedata['is_close'] = $this->_post ( 'is_close', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['intro'] = $this->_post ( 'intro' );
			$savedata['condition'] = $this->_post ( 'condition' );
			$savedata['award_type'] = $this->_post ( 'award_type' );
			$savedata['award_value'] = $this->_post ( 'award_value' );
			$savedata['reserve'] = $this->_post ( 'reserve' );
			
			$res =  $this->_mod->where(array('id'=>$id))->save($savedata);
			if(false !== $res){
				$this->success ( L ( 'operation_success' ), U('promotion/fullcut') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$id = $this->_get('id', 'intval');
			$info = $this->_mod->find($id);
			if(empty($info)){
				$this->error ( L ( 'illegal_parameters' ), U('promotion/fullcut') );
			}
			$prefix = C(DB_PREFIX);
			$item = M('item')->field($prefix.'item.id,price,b.goodsId,b.title,b.img')
				->join($prefix.'item_base b ON '.$prefix.'item.baseid=b.id')
				->where($prefix.'item.id='.$info['condition'])->find();
			$this->assign('item', $item);
			$this->assign('info', $info);
			$this->display ();
		}
	}
	
	public function fullcut_delete(){
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
	
	public function limitbuy() {
		$where['uid'] = array('in', $this->getUidList());
		$where['type'] = 1;
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		$db_pre = C('DB_PREFIX');
		$ai_table = $db_pre . 'promotion';
		unset($where['uid']);
		$where['i.uid'] = array('in', $this->getUidList());;
		$select =  $this->_mod->field($ai_table.'.*,price,b.goodsId,b.title,b.img')
				->join($db_pre.'item i ON i.id=' . $ai_table . '.condition')
				->join($db_pre.'item_base b ON i.baseid=b.id')
				->where($where)->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	
	public function limitbuy_add() {
		//exit();
		//Log::write('limitbuy_add');
		if (IS_POST) {
			$id = $this->_mod->max('id');
			$savedata['uid'] = $this->shopId();
			$savedata['name'] = $this->_post ( 'name', 'trim' );
			$savedata['is_close'] = $this->_post ( 'is_close', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['intro'] = $this->_post ( 'intro' );
			$savedata['type'] = 1;
			$savedata['award_type'] = 0;
			$savedata['add_time'] = time();
			$savedata['url'] = C('pin_baseurl')."/market/limitbuy/sid/".$this->shopId();
			//Log::write('>>>'.$savedata['url']);
			$items = $this->_post('items', ',');
			if( $items ){
				foreach( $items['itemid'] as $key=>$val ){
					$savedata['condition'] = $val;
					$savedata['award_value'] = $items['limitbuy_price'][$key];
					$this->_mod->add($savedata);
					//Log::write($this->_mod->getLastSql(), 'DEBUG');
				}
				$this->success ( L ( 'operation_success' ), U('promotion/limitbuy') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$this->display ();
		}
	}
	
	public function limitbuy_edit() {
		if (IS_POST) {
			$id = $this->_post ( 'id', 'trim' );
				
			$savedata['name'] = $this->_post ( 'name', 'trim' );
			$savedata['is_close'] = $this->_post ( 'is_close', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['intro'] = $this->_post ( 'intro' );
			$savedata['condition'] = $this->_post ( 'condition' );
			$savedata['award_value'] = $this->_post ( 'award_value' );
				
			$res =  $this->_mod->where(array('id'=>$id))->save($savedata);
			if(false !== $res){
				$this->success ( L ( 'operation_success' ), U('promotion/limitbuy') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$id = $this->_get('id', 'intval');
			$info = $this->_mod->find($id);
			if(empty($info)){
				$this->error ( L ( 'illegal_parameters' ), U('promotion/limitbuy'));
			}
			$prefix = C(DB_PREFIX);
			$item = M('item')->field($prefix.'item.id,price,b.goodsId,b.title,b.img')
				->join($prefix.'item_base b ON '.$prefix.'item.baseid=b.id')
				->where($prefix.'item.id='.$info['condition'])->find();
			$this->assign('item', $item);
			$this->assign('info', $info);
			$this->display ();
		}
	}
	
	public function limitbuy_delete(){
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

	public function search_items(){
		if (IS_POST) {
			$prefix = C(DB_PREFIX);
			$uid = $this->shopId();
			$where[$prefix.'item.uid'] = array('in', $this->getUidList());
			$where['_string'] = " status = 1";
			$keyword = $this->_request ( 'keyword', 'trim' );
			if($keyword){
				$where['_string'] .= " and title like '%$keyword%' or ".$prefix."item.goodsId = '$keyword'";
			}
			$list = M('item')->field($prefix.'item.id,price,b.goodsId,b.title,b.img')
					->join($prefix.'item_base as b ON '.$prefix.'item.baseid=b.id')
					->where($where)->limit('0,20')->select();
			////Log::write(M('item')->getLastSql(), 'DEBUG', 'DEBUG');
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
	/**
	 * 预览
	 **/
	public function pre(){
		if (IS_AJAX) {
			//Log::write($_GET['id']);
			$data = $this->_mod->field('id,url')->where(array('id'=>$_GET['id']))->find();
			//Log::write($this->_mod->getLastSql(), 'DEBUG');
			//Log::write('data>>>>'.$data['url']);
			$qrcode=new ItemQRcode();
			$image=$qrcode->qrcodeNoSave($data['url']);
			//Log::write('image>>>'.$image);
			$image = explode('/data/upload/',$image);
			$data_info['image'] = $image[1];
			$this->_mod->where(array('id'=>$_GET['id']))->save($data_info);
			//Log::write($image[1]);
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
		$image_info = $this->_mod->where(array('id'=>$_GET['id']))->find();
		//Log::write($this->_mod->getLastSql(), 'DEBUG');
	
		$image = $logo = C('pin_attach_path').$image_info['image'];
		//Log::write('image>>>'.$image);
		$filename=realpath($image);  //文件名
	
		$name = date("Ymd-H:i:m");
		Header( "Content-type:   application/octet-stream ");
		Header( "Accept-Ranges:   bytes ");
		Header( "Accept-Length: " .filesize($filename));
		header( "Content-Disposition:   attachment;   filename= {$name}.png");
		echo file_get_contents($filename);
		readfile($filename);
	}
}