<?php
class itemAction extends frontendAction {
	public function _initialize() {
		parent::_initialize ();
	}
	
	/**
	 * 商品列表
	 */
	public function index() {
		
		$cate_id = $this->_get('cate_id', 'intval');
		$fid = M('item_cate')->field('pid')-> where(array('id'=>$cate_id))->find();
		$this->assign('fid',$fid['pid']);
		$this->assign('typeid', 5);
		$this->assign ( 'classId', $cate_id);

		$this->display ();
	}
	
	/**
	 * 商品详情
	 */
	public function details() {
		$id = $this->_get('id', 'intval');
		!$id && $this->_404();
		
		$sid = $this->shopId;
		$item_mod = D('item');
		$item_mod->where(array('id' => $id))->setInc('hits'); //点击量
		$item = $item_mod->relation(true)->where(array('id' => $id, 'status' => 1))->find();
		//dump($item);
		!$item && $this->_404();

		/**
		 * 
		 * 商品推荐
		 */		
		
		/*
		$remarkMap = $item['com_remark'];	
	    if ($remarkMap) {
	    	$remarkArray =  explode(' ',$remarkMap);
	    	foreach($remarkArray as $val){
	    		$remarkSignal[] = '%'.$val.'%';
	    	}
	    	//	dump($remarkSignal);
	    	$mapRemark['com_remark'] = array('like',$remarkSignal,'OR');
	    }
		$mapRemark['is_recomm'] = 1;
		$mapRemark['_logic'] = 'OR';
		$remarkGoods = $item_mod->where($mapRemark)->select();
		//Log::write($item_mod->getLastSql(), 'DEBUG', 'DEBUG');
	    //dump($remarkGoods);
		$this->assign('remarkGoods',$remarkGoods);
		*/
		
		//标签
        $item['tag_list'] = unserialize($item['tag_cache']);
        //可能还喜欢
        $item_tag_mod = M('item_tag');
        $db_pre = C('DB_PREFIX');
        $item_tag_table = $db_pre . 'item_tag';
        $maylike_list = array_slice($item['tag_list'], 0, 3, true);
        foreach ($maylike_list as $key => $val) {
            $maylike_list[$key] = array('name' => $val);
            $maylike_list[$key]['list'] = $item_tag_mod->field('distinct(i.id),i.price,' . $item_tag_table . '.tag_id' . ',base.title, base.img')
                                   ->join($db_pre . 'item i ON i.id = ' . $item_tag_table . '.item_id  and i.uid = "' . $sid . '"') //or   i.is_recomm = 1
                                   ->join($db_pre . 'item_base base ON base.id = ' . i . '.id')
                                   ->where(array($item_tag_table . '.tag_id' => $key, 'i.id' => array('neq', $id)))
                                   ->order('i.id DESC')->limit(9)->select();
        }
        //dump($item_tag_mod->getLastSql(), 'DEBUG');
        $this->assign('maylike_list', $maylike_list);
		//dump($maylike_list);
		//exit();
		/**
		 * ***品牌
		*/
		$brand=M('brandlist')->field('name')->find($item['brand']);
		$item['brand']=$brand['name'];
		
		//商品相册
		$img_list = M('item_img')->field('url')->where(array('item_id' => $item['baseid']['id']))->order('ordid')->select();
		
		//Log::write("root-".__ROOT__);
		//Log::write("pin-".$pin_attach_path);
		
		//获取主商品的组合商品信息
		$prefix = C(DB_PREFIX);
		$assembleItem = M('assemble_item')->where(array('item_id' => $id,'main'=>1))->order('assemble_id desc')->find();
		if(!empty($assembleItem)){
			$assembleItems  = M('assemble_item')->field('i.comments, i.price as price,b.originplace,b.goodsId,b.title,b.img')
			->join($prefix.'item i ON i.id='.$prefix.'assemble_item.item_id')
			->join($db_pre.'item_base b ON i.baseid=b.id')
			->where(array('assemble_id' => $assembleItem['assemble_id']))->select();
			$assemblewhere['id'] = $assembleItem['assemble_id'];
			$assemblewhere['start_time'][] = array('elt', date('Y-m-d'));
			$assemblewhere['end_time'][] = array('egt', date('Y-m-d'));
			$assemble = M('assemble')->where($assemblewhere)->find();
			if($assemble){
				$assemble['item'] = $assembleItems;
				$this->assign('assemble', $assemble);
			}
		} 
		// 获取满减信息
		$fullcutwhere['type'] = 0;
		$fullcutwhere['condition'] = $id;
		$fullcutwhere['is_close'] = 0;
		$fullcutwhere['start_time'][] = array('elt', date('Y-m-d'));
		$fullcutwhere['end_time'][] = array('egt', date('Y-m-d'));
		$fullcut = D ( 'promotion' )->where($fullcutwhere)->order('id desc')->find();
		$this->assign('fullcut', $fullcut);
		//查看商品是否限时抢购
		$where['type'] = 1;
		$where['condition'] = $id;
		$where['is_close'] = 0;
		$where['start_time'][] = array('elt', date('Y-m-d H:i:s'));
		$where['end_time'][] = array('egt', date('Y-m-d H:i:s'));
		$limitbuy=  D ( 'promotion' )->where($where)->order('id desc')->find();
// 		dump($limitbuy);
		$this->assign('limitbuy', $limitbuy);
		//加载前5条评论
		$item_comment_mod = M('item_comment');
		$pagesize = 5;
		$map = array('item_id' => $id,'status'=>0);
		$count = $item_comment_mod->where($map)->count('id');
		$pager = $this->_pager($count, $pagesize);
		$pager->path = 'comment_list'; 
		$pager_bar = $pager->fshow();
		$cmt_list = $item_comment_mod->where($map)->order('id DESC')->limit($pager->firstRow . ',' . $pager->listRows)->select();
		
		$res = M('brandlist')->field('id,name')->select();
		$brand_listt = array();
		foreach ($res as $val) {
			$brand_list[$val['id']] = $val['name'];
		}
		$this->assign('brand_list', $brand_list);
		
		$this->assign('item', $item);
		
		$this->assign('img_list', $img_list);
		$this->assign('cmt_list', $cmt_list);
		$this->assign('point', $this->getPoint($id));
		$this->display();
	}
	
	public function promotionTeam() {
		$id = $this->_get('id', 'intval');
		!$id && $this->_404();
		
		//获取主商品的组合商品信息
		$where['id'][] = $id;
		$where['start_time'][] = array('elt', date('Y-m-d'));
		$where['end_time'][] = array('egt', date('Y-m-d'));
		$assemble = M('assemble')->where($where)->find();
		if(empty($assemble)){
			$this->_404();
		}
		$prefix = C(DB_PREFIX);
		$assembleItems  = M('assemble_item')->field('i.comments,i.id, i.price as price,b.originplace,b.goodsId,b.title,b.img')
				->join($prefix.'item i ON i.id='.$prefix.'assemble_item.item_id')
				->join($prefix.'item_base b ON i.baseid=b.id')
				->where(array('assemble_id' => $id))->select();
		$this->assign('assemble', $assemble);
		$this->assign('assembleItems', $assembleItems);
		$this->display ();
	}
	/**
	 * 商品评论
	 */	
	public function userReviews() {
		$id = $this->_get('id', 'intval');
		!$id && $this->_404();
		
		$item_mod = D('item');
		$item = $item_mod->relation(true)->where(array('id' => $id, 'status' => 1))->find();
		$this->assign('item', $item);
		
		$prefix = C(DB_PREFIX);
		$where = array('item_id' => $id, $prefix.'item_comment.status'=>0);
		$count = M('item_comment')->join($prefix.'user ON '.$prefix.'user.id='.$prefix.'item_comment.userid')->join($prefix.'item ON '.$prefix.'item.id='.$prefix.'item_comment.item_id')
				->where($where)->count($prefix.'item_comment.id');
		$pager = new Page($count,20);
		$list  = M('item_comment')
		         ->field($prefix.'item_comment.*,'.$prefix.'user.username,'.$prefix.'user.headimgurl')
				     ->join($prefix.'user ON '.$prefix.'user.id='.$prefix.'item_comment.userid')
		                  ->where($where)
		                       ->order($prefix.'item_comment.id desc')
		                         ->limit($pager->firstRow.','.$pager->listRows)
		                ->select();
		foreach ($list as &$l){
			if($l['images']){
				$l['images'] =  explode(',',$l['images']);;
			}
		}
	
		$this->assign('list',$list);
        $this->assign('page',$pager->show());
		$this->display ();
	}
	/**
	 * 商品图文详情
	 */
	public function picTextDetail() {
		$id = $this->_get('id', 'intval');
		!$id && $this->_404();
		$item_mod = D('item');
		$item = $item_mod->relation(true)->where(array('id' => $id, 'status' => 1))->find();
		!$item && $this->_404();
		
		$item_attr = D('item_attr')->where(array('item_id'=>$id))->select();
		$this->assign('item_attr', $item_attr);
		$this->assign('item', $item);
		$this->display ();
	}
	
	/**
	 * 送至
	 */
	public function distribute(){
		if($_POST){
			$distribute_names = $itemid = $this->_post('distribute_names', 'trim');
			$_SESSION['distribute_names'] = $distribute_names;
			$data = array (
					'status' => 1,
					'msg' => '设置成功'
			);
			echo json_encode ( $data );
		}else{
			$itemid = $this->_get('itemid', 'intval');
			$this->assign('itemid', $itemid);
			$this->display ();
		}
	}
	
	public function index_ajax() {
        $tag = $this->_get('tag', 'trim'); //标签
        $keyword = $this->_get('keyword', 'trim');
        //Log::write ("tag".$tag);
        $sort = $this->_get('sortId', 'trim'); //排序
        $type = $this->_get('type', 'trim'); // 4 精品推荐 2 一路领鲜 
        $classID =$this->_get('classId', 'trim'); //$_GET['classId'];
        $fid = M('item_cate')->field('pid')-> where(array('id'=>$classID))->find();
        //Log::write('pid---'.$fid['pid']);
        if ($classID) {
//         	$where['base.cate_id']=$classID;
        	$where['base.cate_id'] = array(array('eq',$classID),array('eq',$fid['pid']),'or');
        }
        switch ($type) {
        	case '2': 
        	$where['is_new'] = array('eq', 1);
        	break;
        	
        	case '4':
        	$where['is_recomm'] = array('eq', 1);
        	break;
        } 
        
        switch ($sort) {
            case '0':
                $order =  'ordid  ASC, ';
                break;
            case '1':
                $order =  ' price  DESC,';
                break;
            case '2':
                $order = 'price ASC,';
                break;
            case '3':
                $order =  'add_time DESC,';
                break;
        }
        $order = ' (goods_stock>0) DESC, '.$order . ' buy_Num ASC ';
        
        $where['uid'] = array('eq',$this->shopId);
        
        $tag && $where['intro'] = array('like', '%' . $tag . '%');

        if ($keyword) {
        	$map ['base.title'] = array (
        			'like',
        			'%' . $keyword . '%'
        	);
        	$map ['base.intro'] = array (
        			'like',
        			'%' . $keyword . '%'
        	);
        	$map ['base.tag_cache'] = array (
        			'like',
        			'%' . $keyword . '%'
        	);
        	$map ['_logic'] = 'or';
        	
        	$where ['_complex'] = $map;
        }
//         dump($where);
        $this->wall_item($where, $order);
    }
    
    public function scan () {
        $code = $this->_get('code', 'trim');
        $url_info = preg_match('/^http[s]?:\/\/'.
        		'(([0-9]{1,3}\.){3}[0-9]{1,3}'. // IP形式的URL- 199.194.52.184
        		'|'. // 允许IP和DOMAIN（域名）
        		'([0-9a-z_!~*\'()-]+\.)*'. // 三级域验证- www.
        		'([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.'. // 二级域验证
        		'[a-z]{2,6})'.  // 顶级域验证.com or .museum
        		'(:[0-9]{1,4})?'.  // 端口- :80
        		'((\/\?)|'.  // 如果含有文件对文件部分进行校验
        		'(\/[0-9a-zA-Z_!~\*\'\(\)\.;\?:@&=\+\$,%#-\/]*)?)$/',
        		$code);
        //Log::write('url_info :' .$url_info);
        if($url_info == 1){
        	 $status = 1;
        	 $data = array('status' => 1,'url' => $code,'msg' => $msg);
        	 $this->ajaxReturn($status, '', $data);
        }
        //Log::write('scncode:' . $code);
        $code = explode(',', $code);
        // $url="/index.php/item/details/id/";C('pin_attach_path')
        $sid = $this->shopId;
        $where = array(
            'barcodeId' => $code[1]
        );
        $item_base = M('item_base');
        $baseid = $item_base->field('id')
            ->where($where)
            ->find();
        //Log::write('baseid>>>' . $baseid['id']);
        if ($baseid['id']) {
            $map['uid'] = $sid;
            $map['baseid'] = $baseid['id'];
            $item = M('item');
            $itemData = $item->where($map)->find();
            if ($itemData) {
                $status = 1;
                //Log::write('itemid>>>' . $itemData['id']);
                $url = U('item/details', array(
                    'id' => $itemData['id']
                )); // C ( 'pin_baseurl' ) . "/index.php/item/details/id/" . $itemData['id'];
                //Log::write('url :' .$url);
            } else {
                $status = 0;
                $msg = '暂未找到此商品';
            }
        } else {
            $status = 0;
            $msg = '查无此商品';
        }
        // dump($item->getLastSql(), 'DEBUG');
        
        $data = array(
            'status' => $status,
            'url' => $url,
            'msg' => $msg
        );
        
        $this->ajaxReturn($status, '', $data);
    }
    
    
    public function getPoint($item_id) {
    	$point = M('item_comment')
    	         ->field('sum(point)/count(id) as point')
    	          ->where('item_id = ' . $item_id)->find();
    	if ($point['point'] == null) { //$point == null
    		return 5;
    	} else {
    		return $point['point'];
    	}
    	
    }
    
}