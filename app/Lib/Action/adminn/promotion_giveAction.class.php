<?php
class promotion_giveAction extends backendAction {
	
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'promotion_give' );
	}
	/**
	 * 满减促销
	 */
	public function fullcut() {
		
		$where['uid'] = array('in',$this->getUidList());
		$where['award_type'] = 1;
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		
		$select =  $this->_mod->where($where)->order('id desc')->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		foreach ($list as &$info){
			$good_typs = $info['good_type'];
			$good_value = $info['good_value'];
			if($good_typs == 1){
				$prefix = C(DB_PREFIX);
				$where1[$prefix.'item.id'] = array ( 'in', explode(',', $good_value) );;
				$items = M('item')->field($prefix.'item.id,price,b.goodsId,b.title,b.img')
							->join($prefix.'item_base b ON '.$prefix.'item.baseid=b.id')
							->where($where1)->select();
				$info['text'] = $items;
			}elseif ($good_typs == 2){
				$item_cate = D('item_cate')->where ( array ('id' => $good_value) )->find();
				$info['text'] = $item_cate['name'];
			}
		}
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
			
			$good_type = $this->_request ( 'good_type', 'intval' );
			$savedata['good_type'] = $good_type;
			if($good_type == 1){
				$savedata['good_value'] = implode(',',$this->_post ('itemid'));
			}elseif ($good_type == 2){
				$savedata['good_value'] = $this->_post ( 'cate_id' );
			}
			
			//检查当前数据的有效性
			$where['uid'] = $savedata['uid'];
			$where['is_close'] = 0;
			$where['start_time'] = array('elt', date('Y-m-d'));
			$where['end_time'] = array('egt', date('Y-m-d'));
			if ($good_type == 3){
				//$where['good_type'] = $good_type;
				$data = M ( 'promotion_give' )->where($where)->find();
				if($data){
					$this->error('要设置全场商品优惠，请先关闭其他所有的优惠');
				}
			}else if($good_type == 2){
				$where['good_type'] = 3;
				$data = M ( 'promotion_give' )->where($where)->find();
				if($data){
					$this->error('要设置分类优惠，请先关闭其他全场商品优惠');
				}
			}else if($good_type == 1){
				$where['good_type'] = 3;
				$data = M ( 'promotion_give' )->where($where)->find();
				if($data){
					$this->error('要设置商品优惠，请先关闭其他全场商品优惠');
				}
			}
			
			$savedata['award_type'] = 1;
			$savedata['reserve'] = 1;
			
			$savedata['condition'] = $this->_post ( 'condition' );
			$savedata['award_value'] = $this->_post ( 'award_value' );
			$savedata['add_time'] = time();
// 			$savedata['url'] = C('pin_baseurl')."/item/details/id/".$this->_post ( 'condition' )."/sid/".$this->shopId();
			//Log::write('>>>'.$savedata['url']);
			$res = M ( 'promotion_give' )->add($savedata);
			if($res){
				$this->success ( L ( 'operation_success' ), U('promotion_give/fullcut') );
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
			$savedata['award_value'] = $this->_post ( 'award_value' );
			
			$savedata['award_type'] = 1;
			$good_type = $this->_request ( 'good_type', 'intval' );
			$savedata['good_type'] = $good_type;
			if($good_type == 1){
				$savedata['good_value'] = implode(',',$this->_post ('itemid'));
			}elseif ($good_type == 2){
				$savedata['good_value'] = $this->_post ( 'cate_id' );
			}elseif ($good_type == 3){
			}
			
			$res =  $this->_mod->where(array('id'=>$id))->save($savedata);
			if(false !== $res){
				$this->success ( L ( 'operation_success' ), U('promotion_give/fullcut') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$id = $this->_get('id', 'intval');
			$info = $this->_mod->find($id);
			if(empty($info)){
				$this->error ( L ( 'illegal_parameters' ), U('promotion_give/fullcut') );
			}
			if($info['good_type'] == 1){
				$prefix = C(DB_PREFIX);
				$where[$prefix.'item.id'] = array ( 'in', explode(',', $info['good_value']) );;
				$items = M('item')->field($prefix.'item.id,price,b.goodsId,b.title,b.img')
							->join($prefix.'item_base b ON '.$prefix.'item.baseid=b.id')
							->where($where)->select();
				$this->assign('items', $items);
			}else if($info['good_type'] == 2){
				$cate_id = $info['good_value'];
				$spid = D('item_cate')->where ( array ('id' => $info['good_value']) )->getField ( 'spid' );
				if ($spid == 0) {
					$spid = $cate_id;
				} else {
					$spid .= $cate_id;
				}
				$this->assign('spid', $spid);
			}
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
	
	/**
	 * 满赠促销
	 */
	public function fullgive() {
	
		$where['uid'] = array('in',$this->getUidList());
		$where['_string'] = ' (award_type = 2 or award_type = 3)';
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
	
		$select =  $this->_mod->where($where)->order('id desc')->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		foreach ($list as &$info){
			if($info['award_type'] == 3){
				$coupons = M('coupons_url')->where('urlid='.$info['award_value'])->find();
				$info['award_value'] = $coupons['title'].'--'.$coupons['sub_title'];
			}
			$good_typs = $info['good_type'];
			$good_value = $info['good_value'];
			if($good_typs == 1){
				$prefix = C(DB_PREFIX);
				$where1[$prefix.'item.id'] = array ( 'in', explode(',', $good_value) );;
				$items = M('item')->field($prefix.'item.id,price,b.goodsId,b.title,b.img')
				->join($prefix.'item_base b ON '.$prefix.'item.baseid=b.id')
				->where($where1)->select();
				$info['text'] = $items;
			}elseif ($good_typs == 2){
				$item_cate = D('item_cate')->where ( array ('id' => $good_value) )->find();
				$info['text'] = $item_cate['name'];
			}
		}
		
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	
	public function fullgive_add() {
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
				
			$good_type = $this->_request ( 'good_type', 'intval' );
			$savedata['good_type'] = $good_type;
			if($good_type == 1){
				$savedata['good_value'] = implode(',',$this->_post ('itemid'));
			}elseif ($good_type == 2){
				$savedata['good_value'] = $this->_post ( 'cate_id' );
			}elseif ($good_type == 3){
			}
			
			//检查当前数据的有效性
			$where['uid'] = $savedata['uid'];
			$where['is_close'] = 0;
			$where['start_time'] = array('elt', date('Y-m-d'));
			$where['end_time'] = array('egt', date('Y-m-d'));
			if ($good_type == 3){
				$data = M ( 'promotion_give' )->where($where)->find();
				if($data){
					$this->error('要设置全场商品优惠，请先关闭其他所有的优惠');
				}
			}else if($good_type == 2){
				$where['good_type'] = 3;
				$data = M ( 'promotion_give' )->where($where)->find();
				if($data){
					$this->error('要设置分类优惠，请先关闭其他全场商品优惠');
				}
			}else if($good_type == 1){
				$where['good_type'] = 3;
				$data = M ( 'promotion_give' )->where($where)->find();
				if($data){
					$this->error('要设置商品优惠，请先关闭其他全场商品优惠');
				}
			}
			
			$savedata['award_type'] = $this->_request ( 'award_type', 'intval' );
			$savedata['reserve'] = $this->_request ( 'reserve', 'intval' );
				
			$savedata['condition'] = $this->_post ( 'condition' );
			$savedata['award_value'] = $this->_post ( 'award_value' );
			$savedata['add_time'] = time();
// 			$savedata['url'] = C('pin_baseurl')."/item/details/id/".$this->_post ( 'condition' )."/sid/".$this->shopId();
			//Log::write('>>>'.$savedata['url']);
			$res = M ( 'promotion_give' )->add($savedata);
			if($res){
				$this->success ( L ( 'operation_success' ), U('promotion_give/fullgive') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$this->display ();
		}
	}
	
	public function fullgive_edit() {
		if (IS_POST) {
			$id = $this->_post ( 'id', 'trim' );
				
			$savedata['name'] = $this->_post ( 'name', 'trim' );
			$savedata['is_close'] = $this->_post ( 'is_close', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['intro'] = $this->_post ( 'intro' );
			$savedata['condition'] = $this->_post ( 'condition' );
			$savedata['award_value'] = $this->_post ( 'award_value' );
				
			$good_type = $this->_request ( 'good_type', 'intval' );
			$savedata['good_type'] = $good_type;
			if($good_type == 1){
				$savedata['good_value'] = implode(',',$this->_post ('itemid'));
			}elseif ($good_type == 2){
				$savedata['good_value'] = $this->_post ( 'cate_id' );
			}elseif ($good_type == 3){
			}
			$savedata['award_type'] = $this->_request ('award_type', 'intval' );
			$savedata['reserve'] = $this->_request ('reserve', 'intval' );
				
			$res =  $this->_mod->where(array('id'=>$id))->save($savedata);
			if(false !== $res){
				$this->success ( L ( 'operation_success' ), U('promotion_give/fullgive') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$id = $this->_get('id', 'intval');
			$info = $this->_mod->find($id);
			if(empty($info)){
				$this->error ( L ( 'illegal_parameters' ), U('promotion_give/fullgive') );
			}
			if($info['good_type'] == 1){
				$prefix = C(DB_PREFIX);
				$where[$prefix.'item.id'] = array ( 'in', explode(',', $info['good_value']) );;
				$items = M('item')->field($prefix.'item.id,price,b.goodsId,b.title,b.img')
							->join($prefix.'item_base b ON '.$prefix.'item.baseid=b.id')
							->where($where)->select();
				$this->assign('items', $items);
			}elseif($info['good_type'] == 2){
				$cate_id = $info['good_value'];
				$spid = D('item_cate')->where ( array ('id' => $info['good_value']) )->getField ( 'spid' );
				if ($spid == 0) {
					$spid = $cate_id;
				} else {
					$spid .= $cate_id;
				}
				$this->assign('spid', $spid);
			}
			
			if($info['award_type'] == 3){
				$coupons = M('coupons_url')->where('urlid='.$info['award_value'])->find();
				$info['award_value_text'] = $coupons['title'].'--'.$coupons['sub_title'];
				$this->assign('$coupons', $coupons);
			}
			
			$this->assign('items', $items);
			$this->assign('info', $info);
			$this->display ();
		}
	}
	
	public function coupons(){
		if (IS_POST) {
			$prefix = C(DB_PREFIX);
			$uid = $this->shopId();
			$where['shopid'] = array('in', $this->getUidList());
			$where['expiretime'] = array('egt', date('Y-m-d'));
			$keyword = $this->_request ( 'keyword', 'trim' );
			if($keyword){
				$where['_string'] .= " (title like '%$keyword%' or sub_title like '%$keyword%')";
			}
			$list = M('coupons_url')->where($where)->select();
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
	
	public function fullgive_delete(){
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
			
			$cate_id = $this->_request ( 'cate_id', 'intval' );
			if ($cate_id) {
				$id_arr = D('item_cate')->get_child_ids ( $cate_id, true );
				$where ['b.cate_id'] = array ( 'in', $id_arr );
			}
			$where['_string'] = " status = 1";
			$keyword = $this->_request ( 'keyword', 'trim' );
			if($keyword){
				$where['_string'] .= " and title like '%$keyword%' or ".$prefix."item.goodsId = '$keyword'";
			}
			$list = M('item')->field($prefix.'item.id, price,b.goodsId,b.title,b.img')
					->join('join '.$prefix.'item_base as b ON '.$prefix.'item.baseid=b.id')
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