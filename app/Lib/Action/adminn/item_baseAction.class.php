<?php
class item_baseAction extends backendAction {
		public function _initialize() {
			parent::_initialize();
			$this->_mod = D('item_base');
			$this->_cate_mod = D('item_cate');
			$brandlist= $this->_brand=M('brandlist')->where('status=1')->order('ordid asc,id asc')->select();
			//dump($brandlist);
			$this->assign('brandlist',$brandlist);
		}
		public function _before_index(){
		
			$res = $this->_cate_mod->field('id,name')->select();
			$cate_list = array();
			foreach ($res as $val) {
				$cate_list[$val['id']] = $val['name'];
			}
			$this->assign('cate_list', $cate_list);
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
				$map ['cate_id'] = array (
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
			($keyword = $this->_request ( 'keyword', 'trim' )) && $map ['title'] = array (
					'like',
					'%' . $keyword . '%'
			);
			($goodsid = $this->_request ( 'goodsid', 'trim' )) && $map ['goodsId']= array ('eq',$goodsid);
			$this->assign ( 'search', array (
					'time_start' => $time_start,
					'time_end' => $time_end,
					'price_min' => $price_min,
					'price_max' => $price_max,
					'status' => $status,
					'selected_ids' => $spid,
					'cate_id' => $cate_id,
					'itype' => $itype,
					'keyword' => $keyword,
					'goodsid' => $goodsid
			) );
			return $map;
			
		}
	public function edit() {
		if (IS_POST) {
			
			//获取数据
			$item_base = D('item_base')->create();
			if (false === $data = $this->_mod->create()) {
				$this->error($this->_mod->getError());
			}
			if( !$item_base['cate_id']||!trim($item_base['cate_id']) ){
				$this->error('请选择商品分类');
			}
			if($_POST['brand']==''){
				$this->error('请选择品牌');
			}
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
					!$tag_id && $tag_id = $tag_mod->add(array('name' => $_tag_name)); //标签入库
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
			M('item_base')->where(array('id'=>$baseid))->save($item_base);
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
			$this->success(L('operation_success'), U('item_base/index'));
		} else {
			 $id = $this->_get('id','intval');
            $item = $this->_mod->where(array('id'=>$id))->find();
			//分类
			$spid = $this->_cate_mod->where(array('id'=>$item['cate_id']))->getField('spid');
			if( $spid==0 ){
				$spid = $item['cate_id'];
			}else{
				$spid .= $item['cate_id'];
			}
			$this->assign('selected_ids',$spid); //分类选中
			$tag_cache = unserialize($item['tag_cache']);
			$item['tags'] = implode(' ', $tag_cache);
			$this->assign('info', $item);
			//相册
			$img_list = M('item_img')->where(array('item_id'=>$item['id']))->select();
			$this->assign('img_list', $img_list);
			$this->display();
		}
	}
}