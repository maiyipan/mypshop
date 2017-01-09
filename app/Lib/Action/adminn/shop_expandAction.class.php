<?php

class shop_expandAction extends backendAction
{

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('shop_expand');
    }
	
    public function _before_insert($data = '') {
    	$activity_id = $this->_post('activity_id');
    	$count = D('shop_expand')->where(array('uid'=>$this->shopId(), 'activity_id'=>$activity_id))->count();
    	if($count > 3){
    		$this->ajaxReturn(0, L('不能超过3个分类'));
    	}
    	$data ['uid'] = $this->shopId();
    	$data ['add_time'] = time();
    	return $data;
    }
    
    public function _before_index() {
    	$activity_id = $this-> _get('id', 'intval');
        $big_menu = array(
            'title' => L('添加分类推广'),
            'iframe' => U('shop_expand/add',array('activity_id'=>$activity_id)),
            'id' => 'add',
            'width' => '500',
            'height' => '150'
        );
        $this->assign('big_menu', $big_menu);
        
       // $data ['uid'] = $this->shopId();
        $data ['activity_id'] =$activity_id;
        return $data; 
    }

    
    protected function _search() {
        $map = array();
        ($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%'.$keyword.'%');
//         $map ['uid'] = $this->shopId();
        $map ['activity_id'] =$this-> _get('id', 'intval');
        $this->assign('search', array(
            'keyword' => $keyword,
        ));
        return $map;
    }

    public function ajax_check_name() {
        $name = $this->_get('name', 'trim');
        $id = $this->_get('id', 'intval');
        if (D('score_item_cate')->name_exists($name, $id)) {
            $this->ajaxReturn(0, L('该分类名称已存在'));
        } else {
            $this->ajaxReturn(1);
        }
    }
    
   /*  public function search_items(){
    	if (IS_POST) {
    		// 			$map ['uid'] = $this->shopId();
    		$keyword = $this->_request ( 'keyword', 'trim' );
    		$db_pre = C('DB_PREFIX');
    		unset($map['uid']);
    		$map[$db_pre.'item.uid'] = array('in', $this->getUidList());
    		$map ['base.title'] = array (
    				'like',
    				'%' . $keyword . '%'
    		);
    		$map ['base.goodsId'] = array (
    				'like',
    				'%' . $keyword . '%'
    		);
    		$map ['status'] = 1;
    		$item = $db_pre . 'item';
    		$field_list = $item.'.*, base.cate_id, base.brand,base.title,base.intro,base.prime_price,base.img,base.info';
    		$list = D('item')->field($field_list)
    		                   ->join($db_pre.'item_base base ON base.id = ' . $item . '.baseid')
    		                    ->where($map)->limit('0,20')->select();
    		//$list = D('item')->relation(true)->where($where)->limit('0,20')->select();
    		 foreach ($list as &$item){
    			$item['img'] = attach($item['img']);
    		} 
    		//dump($list);
    		$this->ajaxReturn(1, '', $list);
    	}else{
    		$map ['uid'] = $this->shopId();
    		$map ['is_delete'] = 1;
    		$map ['status'] = 1;
    		$list = D('item')->relation(true)->where($map)->select(); //->field('id,goodsId,title,price,goods_stock,img')
    		foreach ($list as &$item){
    			$item['img'] = attach($item['baseid']['img']);
    			$item['title'] = $item['baseid']['title'];
    		}
    		$this->assign('list', $list);
    		//dump($list);
    		if (IS_AJAX) {
    			$response = $this->fetch();
    			$this->ajaxReturn(1, '', $response);
    		} else {
    			$this->display();
    		}
    	}
    } */
    
    public function manage() {
    	$id = $this->_get('id','intval');
    	$info = $this->_mod->where(array('id'=>$id))->find();
    	$this->assign('info', $info);
    	$where['expand_id'] = $id;
    	//获取分类广告
    	$adverts = M('shop_expand_advert')->where($where)->select();
    	$this->assign('adverts', $adverts);
    	//获取分类商品
    	$prefix = C('DB_PREFIX');
    	$items  = M('shop_expand_item')->field($prefix.'shop_expand_item.item_id,b.img,b.title,i.price as price')
    			->join($prefix.'item i ON i.id='.$prefix.'shop_expand_item.item_id')
    			->join($prefix.'item_base b ON i.baseid=b.id')
    			->where($where)->select();
    	$this->assign('items', $items);
    	$this->display();
    }
    
    public function domanage() {
    	$expand_id = $this->_get('id','intval');
    	$itemids = $this->_post('itemid','intval');
    	M('shop_expand_item')->where(array('expand_id'=>$expand_id))->delete();
    	foreach($itemids as $itemid){
    		$item['expand_id'] = $expand_id;
    		$item['item_id'] = $itemid;
    		M('shop_expand_item')->add($item);
    	}
    	$advertslink = $this->_post('advertslink', ',');
    	foreach( $_FILES['advertsimg']['name'] as $key=>$val ){
    		if( $val ){
    			$file_imgs['name'] = $val;
    			$file_imgs['type'] = $_FILES['advertsimg']['type'][$key];
                $file_imgs['tmp_name'] = $_FILES['advertsimg']['tmp_name'][$key];
                $file_imgs['error'] = $_FILES['advertsimg']['error'][$key];
                $file_imgs['size'] = $_FILES['advertsimg']['size'][$key];
    			//上传图片
    			$date_dir = date('ym/'); //上传目录
    			$item_imgs = array(); //相册
    		
    			$result = $this->_upload($file_imgs, 'shop_expand/'.$date_dir);
    			if ($result['error']) {
    				$this->error($result['info']);
    			} else {
    				$imageurl = 'shop_expand/'.$date_dir . $result['info'][0]['savename'];
    			}
    			$advert['expand_id'] = $expand_id;
    			$advert['imageurl'] = $imageurl;
    			$advert['link'] = $advertslink[$key];
    			M('shop_expand_advert')->add($advert);
    		}		
    	}
    	$this->success ( L ( 'operation_success' ), U('shop_expand/manage',array('id'=>$expand_id)));
    }
    
    public function delete_item() {
    	$id = $this->_post('id','intval');
    	$itemid = $this->_post('itemid','intval');
    	$map['expand_id'] = $id;
    	$map['item_id'] = $itemid;
    	if (false !== M('shop_expand_item')->where($map)->delete()) {
    		$this->ajaxReturn(1, L('operation_success'));
    	} else {
    		$this->ajaxReturn(0, L('operation_failure'));
    	}
    }
    
   public function delete_advert() {
    	$mod = M('shop_expand_advert');
    	$id = $this->_post('id','intval');
    	if (false !== $mod->where('id='.$id)->delete()) {
    		$this->ajaxReturn(1, L('operation_success'));
    	} else {
    		$this->ajaxReturn(0, L('operation_failure'));
    	}
    }
    
    public function delete(){
    	$mod = D($this->_name);
    	
    	$num = D($this->_name)->count();
    	if($num <= 3){
    		 $this->ajaxReturn(0, '分类推广不能少于3个');
    		exit;
    	}
    	$pk = $mod->getPk();
    	$ids = trim($this->_request($pk), ',');
    	if ($ids) {
    		if (false !== $mod->delete($ids)) {
    			$this->ajaxReturn(1, L('operation_success'));
    		} else {
    			 $this->ajaxReturn(0, L('operation_failure'));
    		}
    	} else {
    		$this->ajaxReturn(0, L('illegal_parameters'));
    	}
    }
    
    public function ajax_upload_img() {
    	//上传图片
    	if (!empty($_FILES['img']['name'])) {
    		$result = $this->_upload($_FILES['img'], 'shop_expand', array(
    				'width' => C('pin_itemcate_img.width'),
    				'height' => C('pin_itemcate_img.height'),
    		)
    		);
    		if ($result['error']) {
    			$this->ajaxReturn(0, $result['info']);
    		} else {
    			$ext = array_pop(explode('.', $result['info'][0]['savename']));
//     			$data['img'] = str_replace('.' . $ext, '_thumb.' . $ext, $result['info'][0]['savename']);
    			$this->ajaxReturn(1, L('operation_success'), $result['info'][0]['savename']);
    		}
    	} else {
    		$this->ajaxReturn(0, L('illegal_parameters'));
    	}
    }
    
    
    public function _before_add($data) { 
    	$activity_id = $this-> _get('activity_id', 'intval');
		$this->assign('activity_id', $activity_id);
		return  $data;
    }
}