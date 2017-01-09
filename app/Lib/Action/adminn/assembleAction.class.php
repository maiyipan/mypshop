<?php
class assembleAction extends backendAction {
	
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'assemble' );
	}
	
	public function index() {
		//$uid = $this->shopId();

		$where = array();
		$where['uid'] = array('in', $this->getUidList());
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		$select =  $this->_mod->where($where)->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		//Log::write($list['id']);
		$prefix = C(DB_PREFIX);
		foreach ($list as &$l){
			$map['assemble_id'] = $l['id'];
			$items  = M('assemble_item')->field($prefix.'assemble_item.main,i.id,b.title,i.price price')
				->join($prefix.'item i ON i.id='.$prefix.'assemble_item.item_id')
				->join($prefix.'item_base b ON i.baseid=b.id')
			->where($map)->select();
			//Log::write(M('assemble_item')->getLastSql(), 'DEBUG');
			$l['items'] = $items;
		}
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	
	public function add() {
		if (IS_POST) {
			$id = $this->_mod->max('id');
			//Log::write($this->_mod->getLastSql(), 'DEBUG');
			$id ++;
			$savedata['id'] = $id;
			//Log::write('id>>>'.$id);
			
			$savedata['uid'] = $this->shopId();
			$savedata['name'] = $this->_post ( 'name', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['original_price'] = $this->_post ( 'original_price' );
			$savedata['assemble_price'] = $this->_post ( 'assemble_price' );
			$savedata['online'] = $this->_post ( 'online' );
			$main = $this->_post ( 'main' );
			//Log::write('<><><><>'.$itemid);
			$_item['item_id'] = $itemid;
			$savedata['add_time'] = date("Y-m-d H:i:s");
			$savedata['url'] = C('pin_baseurl')."/item/promotionTeam/id/".$id."/sid/".$this->shopId();
			//Log::write('url>>>'.$savedata['url']);
			$res = $this->_mod->add($savedata); 
			//Log::write($res);
			if($res){
				$assemble_item = M('assemble_item');
				//Log::write('itemid>>'.$_POST['item_id']);
				foreach ($_POST['itemid'] as $itemid) {
					$_item['item_id'] = $itemid;
					$_item['assemble_id'] = $res;
					$_item['main'] = $main == $itemid ? 1 : 0;
					$assemble_item->add($_item);
				}
				$this->success ( L ( 'operation_success' ), U('assemble/index') );
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
			$savedata['online'] = $this->_post ( 'online', 'trim' );
			$savedata['start_time'] = $this->_post ( 'start_time' );
			$savedata['end_time'] = $this->_post ( 'end_time' );
			$savedata['original_price'] = $this->_post ( 'original_price' );
			$savedata['assemble_price'] = $this->_post ( 'assemble_price' );
			$main = $this->_post ( 'main' );
			$res =  $this->_mod->where(array('id'=>$id))->save($savedata);
			if(false !== $res){
				$assemble_item = M('assemble_item');
				$assemble_item->where(array('assemble_id'=>$id))->delete();
				foreach ($_POST['itemid'] as $itemid) {
					$_item['item_id'] = $itemid;
					$_item['assemble_id'] = $id;
					$_item['main'] = $main == $itemid ? 1 : 0;
					$assemble_item->add($_item);
				}
				
				$this->success ( L ( 'operation_success' ), U('assemble/index') );
			}else{
				$this->error( L ( 'operation_failure' ) );
			}
		} else {
			$id = $this->_get('id', 'intval');
			$info = $this->_mod->find($id);
			if(empty($info)){
				$this->error ( L ( 'illegal_parameters' ), U('assemble/index') );
			}
			$map['assemble_id'] = $id;
			$prefix = C(DB_PREFIX);
			$items  = M('assemble_item')->field($prefix.'assemble_item.main,i.id,i.price as price,b.goodsId,b.title,b.img')
			->join($prefix.'item i ON i.id='.$prefix.'assemble_item.item_id')
			->join($prefix.'item_base as b ON i.baseid=b.id')
			->where($map)->select();
			$info['items'] = $items;
			
			$this->assign('info', $info);
			$this->display ();
		}
	}
	
	public function delete(){
		$ids = $this->_request('id');
		if ($ids) {
			if (false !== $this->_mod->delete($ids)) {
				 M('assemble_item')->where(array('assemble_id'=>$ids))->delete();
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
			$keyword = $this->_request ( 'keyword', 'trim' );
			$where['_string'] = " status = 1 and title like '%$keyword%' or ".$prefix."item.goodsId = '$keyword'";
			$list = M('item')->field($prefix.'item.id, price ,b.goodsId,b.title,b.img')
					->join($prefix.'item_base as b ON '.$prefix.'item.baseid=b.id')
					->where($where)->limit('0,20')->select();
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