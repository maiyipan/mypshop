<?php
class itemsetAction extends backendAction {

  //  public $list_relation = true;
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('itemset');
    }

    public function _search() {
        $map = array();
        ($start_time_min = $this->_request('start_time_min', 'trim')) && $map['start_time'][] = array('egt', strtotime($start_time_min));
        ($start_time_max = $this->_request('start_time_max', 'trim')) && $map['start_time'][] = array('elt', strtotime($start_time_max)+(24*60*60-1));
        ($end_time_min = $this->_request('end_time_min', 'trim')) && $map['end_time'][] = array('egt', strtotime($end_time_min));
        ($end_time_max = $this->_request('end_time_max', 'trim')) && $map['end_time'][] = array('elt', strtotime($end_time_max)+(24*60*60-1));
        $board_id = $this->_get('board_id', 'intval');
        $board_id && $map['board_id'] = $board_id;
        $style = $this->_request('style', 'trim');
        $style && $map['type'] = array('eq',$style);
        ($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%'.$keyword.'%');
        $this->assign('search', array(
            'start_time_min' => $start_time_min,
            'start_time_max' => $start_time_max,
            'end_time_min' => $end_time_min,
            'end_time_max' => $end_time_max,
            'board_id' => $board_id,
            'style'   => $style,
            'keyword' => $keyword,
        ));
        
        $map ['uid'] = $this->shopId();
        return $map;
    }
    
    public function index() {
    	$where['uid'] = array('in', $this->getUidList());
    	$where['category'] = 2;
    	$count =  $this->_mod->where($where)->count();
    	$pager = new Page($count, $pagesize);
    	$page = $pager->show();
    	$this->assign("page", $page);
    	$db_pre = C('DB_PREFIX');
    	$ai_table = $db_pre . 'shop_expand';
    	$list =  M('shop_expand')->where($where)->limit($pager->firstRow.','.$pager->listRows)->select();
    	$prefix = C(DB_PREFIX);
    	foreach ($list as &$l){
    		$where['expand_id'] = $l['id'];
    		$prefix = C(DB_PREFIX);
    		$items  = M('shop_expand_item')->field($prefix.'shop_expand_item.item_id,b.img,b.title,i.price original_price')
					  ->join($prefix.'item i ON i.id='.$prefix.'shop_expand_item.item_id')
					  ->join($prefix.'item_base b ON i.baseid=b.id')
					  ->where($where)->select();
    		$l['items'] = $items;
    	}
    	$this->assign('list', $list);
    	$this->assign('list_table', true);
    	$this->display();
    }

    public function add() {
    	if($_POST){
    		$savedata['uid'] = $this->shopId();
    		$savedata['name'] = $this->_post ( 'name', 'trim' );
    		$savedata['status'] = $this->_post ( 'status', 'trim' );
    		$savedata['category'] = 2;//商品集合分类
    		$savedata['type'] = 1;
    		$savedata['status'] = 1;
    		$savedata['img'] = $this->_post ( 'img', 'trim' );
    		$savedata['add_time'] = time();
    		$res = M('shop_expand')->add($savedata);
    		if($res){
	    		$itemids = $this->_post('itemid','intval');
	    		foreach($itemids as $itemid){
	    			$item['expand_id'] = $res;
	    			$item['item_id'] = $itemid;
	    			M('shop_expand_item')->add($item);
	    		}
    			$this->success ( L ( 'operation_success' ), U('itemset/index') );
    		}else{
    			$this->error( L ( 'operation_failure' ) );
    		}
    	}else{
    		$this->display();
    	}
    }

    public function edit() {
    	if($_POST){
    		$id = $this->_post('id','intval');
    		$info = M('shop_expand')->where(array('id'=>$id))->find();
    		if($info){
    			$savedata['name'] = $this->_post ( 'name', 'trim' );
    			$res = M('shop_expand')->where(array('id'=>$id))->save($savedata);
    			if(false !== $res){
    				M('shop_expand_item')->where(array('expand_id'=>$id))->delete();
    				$itemids = $this->_post('itemid','intval');
    				foreach($itemids as $itemid){
    					$item['expand_id'] = $id;
    					$item['item_id'] = $itemid;
    					M('shop_expand_item')->add($item);
    				}
    				$this->success ( L ( 'operation_success' ), U('itemset/index') );
    			}else{
    				$this->error( L ( 'operation_failure' ) );
    			}
    		}else{
    			$this->_404();
    		}
    	}else{
    		$id = $this->_get('id','intval');
    		$info = M('shop_expand')->where(array('id'=>$id))->find();
    		$this->assign('info', $info);
    		//获取商品
    		$where['expand_id'] = $id;
    		$prefix = C(DB_PREFIX);
    		$items  = M('shop_expand_item')->field($prefix.'shop_expand_item.item_id,b.img,b.title,i.price as price')
					  ->join($prefix.'item i ON i.id='.$prefix.'shop_expand_item.item_id')
					  ->join($prefix.'item_base b ON i.baseid=b.id')
					  ->where($where)->select();
    		$this->assign('items', $items);
    		$this->display();
    	}
    }
    
    public function delete(){
    	$ids = $this->_request('id');
    	if ($ids) {
    		if (false !== M('shop_expand')->delete($ids)) {
    			$where['expand_id'] = $ids;
    			M('shop_expand_item')->where($where)->delete();
    			$this->ajaxReturn(1, L('operation_success'));
    		} else {
    			$this->ajaxReturn(0, L('operation_failure'));
    		}
    	} else {
    		$this->ajaxReturn(0, L('illegal_parameters'));
    	}
    }

    //上传图片
    public function ajax_upload_img() {
        $type = $this->_get('type', 'trim', 'img');
        if (!empty($_FILES[$type]['name'])) {
            $dir = date('ym/');
            $result = $this->_upload($_FILES[$type], 'banners/'. $dir );
            if ($result['error']) {
                $this->ajaxReturn(0, $result['info']);
            } else {
                $savename = $dir . $result['info'][0]['savename'];
                $this->ajaxReturn(1, L('operation_success'), $savename);
            }
        } else {
            $this->ajaxReturn(0, L('illegal_parameters'));
        }
    }
    
    /**
    * 预览
    * */
    public function pre() {
    	$id = $this->_get('id');
    	$data=$this->item_qrcode($id);
    	$this->assign('data',$data);
    	if (IS_AJAX) {
    		$response = $this->fetch();
    		$this->ajaxReturn(1, '', $response);
    	} else {
    		$this->display();
    	}
    }
    
    public function item_qrcode($id){
    	//$url  = U('index/doSearch', array('keyword'=>$data['name']));
    	$url = C('pin_baseurl'). '/market/promotionList/id/'. $id . '/sid/'. $this->shopId();
    	$qrcode=new ItemQRcode($url,$id,$table);
    	$image=$qrcode->qrcodeNoSave($url);
    	$data = array(
    			'imgurl'=>$image,
    			'url' =>$url
    	);
    	return $data;
    }
}