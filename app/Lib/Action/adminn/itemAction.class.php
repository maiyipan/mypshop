<?php
Vendor('pscws4.pscws4','','.class.php');
class itemAction extends backendAction {
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('item');
        $this->_cate_mod = D('item_cate');
        $this->_base_mod = D('item_base');
        $brandlist= $this->_brand=M('brandlist')->where('status=1')
        ->order('ordid asc,id asc')->select();
//         dump($brandlist);
        $this->assign('brandlist',$brandlist);
        
        //dump($brandlist);
    }

    public function _before_index() {
        //分类信息
        $res = $this->_cate_mod->field('id,name')->select();
        $cate_list = array();
        foreach ($res as $val) {
            $cate_list[$val['id']] = $val['name'];
        }
        $this->assign('cate_list', $cate_list);
        //默认排序
        $this->sort = 'ordid ASC,';
        $this->order ='add_time DESC';
        $where['uid'] = array('in', $this->getUidList());
        $this->list_relation = true;
        return $where;
    }

    protected function _search() {
    	
    	
		$map = array ();
		// 'status'=>1
		($time_start = $this->_request ( 'time_start', 'trim' )) && $map ['update_time'] [] = array (
				'egt',
				strtotime ( $time_start ) 
		);
		($time_end = $this->_request ( 'time_end', 'trim' )) && $map ['update_time'] [] = array (
				'elt',
				strtotime ( $time_end ) + (24 * 60 * 60 - 1) 
		);
		($price_min = $this->_request ( 'price_min', 'trim' )) && $map ['price'] [] = array (
				'egt',
				$price_min 
		);
		($price_max = $this->_request ( 'price_max', 'trim' )) && $map ['price'] [] = array (
				'elt',
				$price_max 
		);
		
		$cate_id = $this->_request ( 'cate_id', 'intval' );
		if ($cate_id) {
			$id_arr = $this->_cate_mod->get_child_ids ( $cate_id, true );
			$map ['base.cate_id'] = array (
					'IN',
					$id_arr 
			);
			$spid = $this->_cate_mod->where ( array (
					'id' => $cate_id 
			) )->getField ( 'spid' );
			if ($spid == 0) {
				$spid = $cate_id;
			} else {
				$spid .= $cate_id;
			}
		}
		$itype = $this->_request ( 'itype', 'trim' );
		if ($itype == 'unsale'){
			$map ['is_delete'] = 1;
			$map ['status'] = 0;
		}elseif($itype == 'pendding'){
			$map ['is_delete'] = 0;
		}else{
			$map ['is_delete'] = 1;
			$map ['status'] = 1;
		}
		$map ['uid'] =  array('in', $this->getUidList());// $this->shopId();
		($keyword = $this->_request ( 'keyword', 'trim' )) && $map ['base.title'] = array (
				'like',
				'%' . $keyword . '%' 
		);
		($goodsid = $this->_request ( 'goodsid', 'trim' )) && $map ['base.goodsId']= array (
				'eq',
				$goodsid
		);
		
		//加入推荐  最新的搜索
		$this->_request ( 'is_recomm', 'trim' ) && $map['is_recomm'] = $this->_request ( 'is_recomm', 'trim' );
		$this->_request ( 'is_new', 'trim' ) && $map['is_new'] = $this->_request ( 'is_new', 'trim' );

		
		//Log::write('goodsid---'.$goodsid);
		//Log::write('test---' . $time_start);
		$this->assign ( 'search', array (
				'time_start' => $time_start,
				'time_end' => $time_end,
				'price_min' => $price_min,
				'price_max' => $price_max,
				'status' => $status,
				'selected_ids' => $spid,
				'cate_id' => $cate_id,
				'itype' => $itype,
				'keyword' => $keyword ,
				'goodsid' => $goodsid
		) );
		return $map;
	}

    public function add() {
		if (IS_POST) {
			// 获取数据
			if (false === $data = $this->_mod->create ()) {
				$this->error ( $this->_mod->getError () );
			}
			if (! $data ['cate_id'] || ! trim ($data ['cate_id'] )) {
				$this->error ( '请选择商品分类' );
			}
			if ($_POST ['brand'] == '') {
				
				$this->error ( '请选择品牌' );
			}
			if (empty ( $_POST['img'] )) {
				$this->error ( '请上传商品图片' );
			}
			$data['is_delete'] = 1;
			$data['imgs'] = $_POST['imgs'];
			$data['uid'] = $this->shopId();
			$data['mbdscnt'] = $_POST['mbdscnt'];
			$data['com_remark'] = $_POST['comremark'];
			$item_id = $this->_mod->publish ( $data );
			! $item_id && $this->error ( L ( 'operation_failure' ) );
			
			$this->success ( L ( 'operation_success' ), U('item/index'));
		} else {
			$this->assign('goodsId', time().rand(10,99));
			$this->display ();
		}
	}
	public function edit() {
		if (IS_POST) {
			//获取数据
			//$item_base = D('item_base')->create();
			if (false === $data = $this->_mod->create()) {
				$this->error($this->_mod->getError());
			}
			/* if( !$item_base['cate_id']||!trim($item_base['cate_id']) ){
				$this->error('请选择商品分类');
			}
			if($_POST['brand']==''){
				$this->error('请选择品牌');
			} */ 
			$item_id = $data['id'];
			//标签
			$tags = $this->_post('tags', 'trim');
			if (!isset($tags) || empty($tags)) {
				$tag_list = D('tag')->get_tags_by_title($data['intro']);
			} else {
				$tag_list = explode(' ', $tags);
			}
			if ($tag_list) {
				$item_tag_arr = $tag_cache = array();
				$tag_mod = M('tag');
				foreach ($tag_list as $_tag_name) {
					$tag_id = $tag_mod->where(array('name'=>$_tag_name))->getField('id');
					!$tag_id && $tag_id = $tag_mod->add(array('name' => $_tag_name, 'uid'=>$this->shopId())); //标签入库
					$item_tag_arr[] = array('item_id'=>$item_id, 'tag_id'=>$tag_id);
					$tag_cache[$tag_id] = $_tag_name;
				}
				if ($item_tag_arr) {
					$item_tag = M('item_tag');
					//清除关系
					$item_tag->where(array('item_id'=>$item_id))->delete();
					//商品标签关联
					$item_tag->addAll($item_tag_arr);
					$data['tag_cache'] = serialize($tag_cache);
				}
			}
	
			//更新商品
			$this->_mod->where(array('id'=>$item_id))->save($data);
	
			$data['imgs'] = $_POST['imgs'];
			$baseid = $_POST['baseid'];
			unset($item_base['id']);
			$item_base['id'] = $baseid;
			/* M('item_base')->where(array('id'=>$baseid))->save($item_base); */
	
			//更新图片和相册  更新到基础信息表
			if (isset($data['imgs']) && $data['imgs']) {
				$item_img_mod = D('item_img');
				D('item_img')->where(array('item_id'=>$baseid))->delete();
				$i = 0;
				foreach ($data['imgs'] as $img) {
					$_img['item_id'] = $baseid;
					$_img['url'] = $img;
					$_img['add_time'] = time();
					$_img['ordid'] = ++$i;
					$_img['status'] = $img;
					$item_img_mod->add($_img);
				}
			}
	
			//附加属性
			$attr = $this->_post('attr', ',');
			if( $attr ){
				foreach( $attr['name'] as $key=>$val ){
					if( $val&&$attr['value'][$key] ){
						$atr['item_id'] = $item_id;
						$atr['attr_name'] = $val;
						$atr['attr_value'] = $attr['value'][$key];
						M('item_attr')->add($atr);
					}
				}
			}
			$itype = $this->_request ( 'itype', 'trim' );
			$this->success ( L ( 'operation_success' ), U ( 'item/index', array (
          'itype' => $itype 
      ) ) );
    } else {
      $this->list_relation = true;
      $id = $this->_get ( 'id', 'intval' );
      $item = $this->_mod->relation ( true )->where ( array (
          'id' => $id 
      ) )->find ();
      // 分类
      $spid = $this->_cate_mod->where ( array (
          'id' => $item ['baseid'] ['cate_id'] 
      ) )->getField ( 'spid' );
      if ($spid == 0) {
        $spid = $item ['baseid'] ['cate_id'];
      } else {
        $spid .= $item ['baseid'] ['cate_id'];
      }
      $this->assign ( 'selected_ids', $spid ); // 分类选中
      $tag_cache = unserialize ( $item ['tag_cache'] );
      $item ['tags'] = implode ( ' ', $tag_cache );
      $this->assign ( 'info', $item );
      // 相册
      $img_list = M ( 'item_img' )->where ( array (
          'item_id' => $item ['baseid'] ['id'] 
      ) )->order('ordid')->select ();
      $this->assign ( 'img_list', $img_list );
      $this->display ();
    }
  }
  function delete_album() {
    $album_mod = M ( 'item_img' );
    $album_id = $this->_get('album_id','intval');
        $album_img = $album_mod->where('id='.$album_id)->getField('url');
        if( $album_img ){
            $ext = array_pop(explode('.', $album_img));
            $album_min_img = C('pin_attach_path') . 'item/' . str_replace('.' . $ext, '_s.' . $ext, $album_img);
            is_file($album_min_img) && @unlink($album_min_img);
            $album_img = C('pin_attach_path') . 'item/' . $album_img;
            is_file($album_img) && @unlink($album_img);
            $album_mod->delete($album_id);
        }
        echo '1';
        exit;
    }

    function delete_attr() {
        $attr_mod = M('item_attr');
        $attr_id = $this->_get('attr_id','intval');
        $attr_mod->delete($attr_id);
        echo '1';
        exit;
    }

    /**
     * 商品审核
     */
    public function check() {
        //分类信息
        $res = $this->_cate_mod->field('id,name')->select();
        $cate_list = array();
        foreach ($res as $val) {
            $cate_list[$val['id']] = $val['name'];
        }
        $this->assign('cate_list', $cate_list);
        //商品信息
        //$map = $this->_search();
        $map=array();
        $map['status']=0;
        ($time_start = $this->_request('time_start', 'trim')) && $map['add_time'][] = array('egt', strtotime($time_start));
        ($time_end = $this->_request('time_end', 'trim')) && $map['add_time'][] = array('elt', strtotime($time_end)+(24*60*60-1));
        $cate_id = $this->_request('cate_id', 'intval');
        if ($cate_id) {
            $id_arr = $this->_cate_mod->get_child_ids($cate_id, true);
            $map['cate_id'] = array('IN', $id_arr);
            $spid = $this->_cate_mod->where(array('id'=>$cate_id))->getField('spid');
            if( $spid==0 ){
                $spid = $cate_id;
            }else{
                $spid .= $cate_id;
            }
        }
        ($keyword = $this->_request('keyword', 'trim')) && $map['title'] = array('like', '%'.$keyword.'%');
        $this->assign('search', array(
            'time_start' => $time_start,
            'time_end' => $time_end,
            'selected_ids' => $spid,
            'cate_id' => $cate_id,
            'keyword' => $keyword,
        ));
        //分页
        $count = $this->_mod->where($map)->count('id');
        $pager = new Page($count, 20);
        $select = $this->_mod->field('id,title,img,tag_cache,cate_id,uid,uname')->where($map)->order('id DESC');
        $select->limit($pager->firstRow.','.$pager->listRows);
        $page = $pager->show();
        $this->assign("page", $page);
        $list = $select->select();
        foreach ($list as $key=>$val) {
            $tag_list = unserialize($val['tag_cache']);
            $val['tags'] = implode(' ', $tag_list);
            $list[$key] = $val;
        }
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 审核操作
     */
    public function do_check() {
        $mod = D($this->_name);
        $pk = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        $datas['id']=array('in',$ids);
        $datas['status']=1;
        if ($datas) {
            if (false !== $mod->save($datas)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
            }
        } else {
            IS_AJAX && $this->ajaxReturn(0, L('illegal_parameters'));
        }

    }

    /**
     * ajax获取标签
     */
    public function ajax_gettags() {
        $title = $this->_get('title', 'trim');
        $tag_list = D('tag')->get_tags_by_title($title);
        $tags = implode(' ', $tag_list);
        $this->ajaxReturn(1, L('operation_success'), $tags);
    }

    public function delete_search() {
        $items_mod = D('item');
        $items_cate_mod = D('item_cate');
        $items_likes_mod = D('item_like');
        $items_pics_mod = D('item_img');
        $items_tags_mod = D('item_tag');
        $items_comments_mod = D('item_comment');

        if (isset($_REQUEST['dosubmit'])) {
            if ($_REQUEST['isok'] == "1") {
                //搜索
                $where = '1=1';
                $keyword = trim($_POST['keyword']);
                $cate_id = trim($_POST['cate_id']);
                $cate_id = trim($_POST['cate_id']);
                $time_start = trim($_POST['time_start']);
                $time_end = trim($_POST['time_end']);
                $status = trim($_POST['status']);
                $min_price = trim($_POST['min_price']);
                $max_price = trim($_POST['max_price']);
                $min_rates = trim($_POST['min_rates']);
                $max_rates = trim($_POST['max_rates']);

                if ($keyword != '') {
                    $where .= " AND title LIKE '%" . $keyword . "%'";
                }
                if ($cate_id != ''&&$cate_id!=0) {
                    $where .= " AND cate_id=" . $cate_id;
                }
                if ($time_start != '') {
                    $time_start_int = strtotime($time_start);
                    $where .= " AND add_time>='" . $time_start_int . "'";
                }
                if ($time_end != '') {
                    $time_end_int = strtotime($time_end);
                    $where .= " AND add_time<='" . $time_end_int . "'";
                }
                if ($status != '') {
                    $where .= " AND status=" . $status;
                }
                if ($min_price != '') {
                    $where .= " AND price>=" . $min_price;
                }
                if ($max_price != '') {
                    $where .= " AND price<=" . $max_price;
                }
                if ($min_rates != '') {
                    $where .= " AND rates>=" . $min_rates;
                }
                if ($max_rates != '') {
                    $where .= " AND rates<=" . $max_rates;
                }
                $ids_list = $items_mod->where($where)->select();
                $ids = "";
                foreach ($ids_list as $val) {
                    $ids .= $val['id'] . ",";
                }
                if ($ids != "") {
                    $ids = substr($ids, 0, -1);
                    $items_likes_mod->where("item_id in(" . $ids . ")")->delete();
                    $items_pics_mod->where("item_id in(" . $ids . ")")->delete();
                    $items_tags_mod->where("item_id in(" . $ids . ")")->delete();
                    $items_comments_mod->where("item_id in(" . $ids . ")")->delete();
                    M('album_item')->where("item_id in(" . $ids . ")")->delete();
                    M('item_attr')->where("item_id in(" . $ids . ")")->delete();

                }
                $items_mod->where($where)->delete();

                //更新商品分类的数量
                $items_nums = $items_mod->field('cate_id,count(id) as items')->group('cate_id')->select();
                foreach ($items_nums as $val) {
                    $items_cate_mod->save(array('id' => $val['cate_id'], 'items' => $val['items']));
                    M('album')->save(array('cate_id' => $val['cate_id'], 'items' => $val['items']));
                }

                $this->success('删除成功', U('item/delete_search'));
            } else {
                $this->success('确认是否要删除？', U('item/delete_search'));
            }
        } else {
            $res = $this->_cate_mod->field('id,name')->select();

            $cate_list = array();
            foreach ($res as $val) {
                $cate_list[$val['id']] = $val['name'];
            }
            //$this->assign('cate_list', $cate_list);
            $this->display();
        }
    }
    
 	public function delete(){
        $mod = D($this->_name);
        $pk = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
        	$where['id'] = array('IN', $ids);
        	$savedata['is_delete'] = 0;
        	$savedata['status'] = 0;
            if (false !== $mod->where($where)->save($savedata)) {
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'));
                $this->success(L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'));
            }
        } else {
            IS_AJAX && $this->ajaxReturn(0, L('illegal_parameters'));
            $this->error(L('illegal_parameters'));
        }
    }
      
    public function status(){
    	$ids = trim($this->_request('id'), ',');
    	if($ids){
    	    $where['id'] = array('IN', $ids);
    	    $flag = true;
    	    $status= $this->_request('status', 'intval');
    	    if($status == -1){
    	      $savedata['is_delete'] = 1;
    	      if (false !== $this->_mod->where($where)->save($savedata)) {
    	        $this->ajaxReturn(1, L('operation_success'));
    	      } else {
    	        $this->ajaxReturn(0, L('operation_failure'));
    	      }
    	    } else {
    	      $savedata['status'] = $status;
    	      $itemdata = $this->_mod->where($where)->field('id,price')->select();
    	      foreach ($itemdata as $key=>$val) {
    	        $map['id'] = $val['id'] ;
    	        if ($status == 1 ) { //上架
    	          if (null != $val['price']) {
    	            $savedata['id'] = $val['id'];
    	           
    	            $this->_mod->where($map)->save($savedata);
    	          } else {
    	            $flag = false;
    	          }
    	        } else { //下架
    	          $savedata['id'] = $val['id'];
    	          $this->_mod->where($map)->save($savedata);
    	        }
//     	        dump($this->_mod->getLastSql(), 'DEBUG');
    	     }
    	     
    	     if ($flag === true ){
    	       $this->ajaxReturn(1, L('operation_success'));
    	     } else {
    	       $this->ajaxReturn(1, '有些商品没有设置价格，已为你略过');
    	     }
    	     
    	    }
    	   
    	} else {
            $this->ajaxReturn(0, L('illegal_parameters'));
        }
    }
    /**
     * 预览
     * */
    public function pre() {
    	
    	$data=$this->item_qrcode();
    	$this->assign('data',$data);   	
    	if (IS_AJAX) {
    		$response = $this->fetch();
    		$this->ajaxReturn(1, '', $response);
    	} else {
    		$this->display();
    	}
    }
    /**
     * 二维码显示
     */
    public function item_qrcode(){
    	$shopid = $this->shopId();
//     	$id = 2811;
    	$id=$_GET['id'];
    	$tab=M('item');
    	$data=$tab->where('id='.$id)->find();
    	//Log::write($tab->getLastSql(), 'DEBUG');
    	$url="/item/details/id/".$id."/sid/".$data['uid'];
    	if($data['qrcode']!=null){
    		//Log::write("out");
    		$image=$data['qrcode'];
    		$url=$data['url'];
    		$image=array(
    				'qrcode'=>$image,
    				'url'=>$url,
    				'id'=>$id
    		);
    		return $image;
    	}else {
    		//Log::write("new");
    		$table="item";
    		$qrcode=new ItemQRcode();
    		$image=$qrcode->qrcode($url,$id,$table);   		
    		return $image;
    	}
    }
    /**
     * 下载
     * */
    public function down () {
    	$data_id = $this->item_qrcode();
    	$id=$data_id['id'];
    	$data = $this->_mod->where('id = '. $id)->find();
    	$nn = $data['qrcode'];//'8ac0231e6b10b3a56dfbdc1b2f86c2df_logo.png';
    	$nn=ltrim($nn,"item/qrcode/");
    	$image = $logo = C('pin_attach_path').'item/qrcode/' . $nn;
    	$filename=realpath($image);  //文件名
    	$date=date("Ymd-H:i:m");
    	$name = $data['name'] .'_'.$data['remark'];
    	Header( "Content-type:   application/octet-stream ");
    	Header( "Accept-Ranges:   bytes ");
    	Header( "Accept-Length: " .filesize($filename));
    	header( "Content-Disposition:   attachment;   filename= {$name}.png");
    	echo file_get_contents($filename);
    	readfile($filename);
    }
    public function ajax_CreatComRemark(){
    	$num = 10;
    	$title = $_POST['title'];
    	$pscws = new PSCWS4('utf8');
    	$pscws->set_dict(PIN_DATA_PATH.'/scws/dict.utf8.xdb');
    	$pscws->set_rule(PIN_DATA_PATH.'/scws/rules.utf8.ini');
    	$pscws->send_text($title);
    	$words = $pscws->get_tops($num);
    	$pscws->close();
    	foreach ($words as $val) {
    		$tags[] = $val['word'];
    	}
    	$comRemark = implode(' ', $tags);
	   	$this->assign("comremark",$comRemark);
	   	$this->ajaxReturn(1, L('operation_success'), $comRemark);
    }
    
    
    /**
     * 列表页面
     */
    public function index() {
    	$map = $this->_search();
    	$mod = D($this->_name);
    	!empty($mod) && $this->_list($mod, $map);
    	$this->display();
    }
    
    /**通用商品搜索的弹出框,其他的都会统一到这里*/
    public function search_dialog(){
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
    
    protected function _list($model, $map = array(), $sort_by='', $order_by='', $field_list='*', $pagesize=15)
    {
    	//排序
    	$mod_pk = $model->getPk();
    	if ($this->_request("sort", 'trim')) {
    		$sort = $this->_request("sort", 'trim');
    	} else if (!empty($sort_by)) {
    		$sort = $sort_by;
    	} else if ($this->sort) {
    		$sort = $this->sort;
    	} else {
    		$sort = $mod_pk;
    	}
    	if ($this->_request("order", 'trim')) {
    		$order = $this->_request("order", 'trim');
    	} else if (!empty($order_by)) {
    		$order = $order_by;
    	} else if ($this->order) {
    		$order = $this->order;
    	} else {
    		$order = 'DESC';
    	}
    	
    	$db_pre = C('DB_PREFIX');
    	unset($map['uid']);
    	$map[$db_pre.'item.uid'] = array('in', $this->getUidList()); 
    	$item = $db_pre . 'item';
    	
    	//如果需要分页
    	if ($pagesize) {
    		$count = $model->join($db_pre.'item_base base ON base.id = ' . $item . '.baseid')->where($map)->count($db_pre.'item.id');
    		$pager = new Page($count, $pagesize);
    	}
    	$field_list = $item.'.*, base.cate_id, base.brand,base.title,base.intro,base.prime_price as erp_price,base.img,base.info';
    	
    	$select = $model->field($field_list)
    	                ->join($db_pre.'item_base base ON base.id = ' . $item . '.baseid')
    	                ->where($map)->order($sort . ' ' . $order);
    	//$this->list_relation && $select->relation(true);
    	
    	if ($pagesize) {
    		$select->limit($pager->firstRow.','.$pager->listRows);
    		$page = $pager->show();
    		$this->assign("page", $page);
    	}
    	$list = $select->select();
    	//dump($select->getLastSql(), 'DEBUG');
    	//dump($list);
    	foreach ($list as $key=>$item) {
    		$list[$key]['tag_list'] = unserialize($item['tag_cache']);
    	}
    	$this->assign('list', $list);
    	$this->assign('list_table', true);
    }
    
}