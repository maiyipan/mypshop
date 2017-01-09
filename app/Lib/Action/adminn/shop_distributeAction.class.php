<?php
class shop_distributeAction extends backendAction {
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'shop_distribute' );
	}
	
	public function _before_insert($data = '') {
		 //检测分类是否存在
       /*  if($this->_mod->name_exists($data['name'], $data['pid'])){
            $this->ajaxReturn(0, '地区名已经存在');
        } */
        //生成spid
        $data['spid'] = $this->_mod->get_spid($data['pid']);
        $data['uid'] = $this->shopId();
		return $data;
	}
	
	protected function _before_update($data = '') {
		if ($this->_mod->name_exists($data['name'], $data['pid'], $data['id'])) {
			$this->ajaxReturn(0, '地区名已经存在');
		}
		$item_cate = $this->_mod->field('pid')->where(array('id'=>$data['id']))->find();
		if ($data['pid'] != $item_cate['pid']) {
			//不能把自己放到自己或者自己的子目录们下面
			$wp_spid_arr = $this->_mod->get_child_ids($data['id'], true);
			if (in_array($data['pid'], $wp_spid_arr)) {
				$this->ajaxReturn(0, L('cannot_move_to_child'));
			}
			//重新生成spid
			$data['spid'] = $this->_mod->get_spid($data['pid']);
		}
		return $data;
	}
	
	
	public function _before_add() {
		$pid = $this->_get('pid', 'intval', 0);
		if ($pid) {
			$spid = $this->_mod->where(array('id'=>$pid))->getField('spid');
			$spid = $spid ? $spid.$pid : $pid;
			$this->assign('spid', $spid);
		}
	}
	
	
	/**
	 * 获取紧接着的下一级分类ID
	 */
	public function ajax_getchilds() {
		$id = $this->_get('id', 'intval');
		$type = $this->_get('type', 'intval', null);
		$map = array('pid'=>$id, 'uid'=>$this->shopId());
		$return = $this->_mod->field('id,name')->where($map)->select();
		if ($return) {
			$this->ajaxReturn(1, L('operation_success'), $return);
		} else {
			$this->ajaxReturn(0, L('operation_failure'));
		}
	}
	
	public function index() {
		$tree = new Tree();
		$tree->icon = array('│ ','├─ ','└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		$where['uid'] = $this->shopId();
		$result = $this->_mod->where($where)->select();
		$array = array();
		foreach($result as $r) {
			$r['str_manage'] = '<a href="javascript:;" class="J_showdialog button button1" data-uri="'.U('shop_distribute/add',array('pid'=>$r['id'])).'" data-title="添加子地区" data-id="add" data-width="400" data-height="100">添加子地区</a> |
                                <a href="javascript:;" class="J_showdialog button button2" data-uri="'.U('shop_distribute/edit',array('id'=>$r['id'])).'" data-title="'.L('edit').' - '. $r['name'] .'" data-id="edit" data-width="400" data-height="100">'.L('edit').'</a> |
                                <a href="javascript:;" class="J_confirmurl button button3" data-acttype="ajax" data-uri="'.U('shop_distribute/delete',array('id'=>$r['id'])).'" data-msg="'.sprintf(L('confirm_delete_one'),$r['name']).'">'.L('delete').'</a>';
	
			$r['parentid_node'] = ($r['pid'])? ' class="child-of-node-'.$r['pid'].'"' : '';
			$array[] = $r;
		}
		$str  = "<tr id='node-\$id' \$parentid_node>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td align='center'>\$id</td>
                <td>\$spacer<span data-tdtype='edit' data-field='name' data-id='\$id' class='tdedit'  style='color:\$fcolor'>\$name</span></td>
                <td align='center'>\$str_manage</td>
                </tr>";
		$tree->init($array);
		$list = $tree->get_tree(0, $str);
		$this->assign('list', $list);
		$big_menu = array(
				'title' => L('添加配送地区'),
				'iframe' => U('shop_distribute/add'),
				'id' => 'add',
				'width' => '400',
				'height' => '100'
		);
		$this->assign('big_menu', $big_menu);
		$this->assign('list_table', true);
		$this->display();
	}
	
	public function distribute(){
		$big_menu = array(
				'title' => L('添加配送地区'),
				'iframe' => U('shop_distribute/add'),
				'id' => 'add',
				'width' => '500',
				'height' => '150'
		);
		$this->assign('big_menu', $big_menu);
		
		$uid = $this->shopId();
		$list = $this->_mod->where(array('uid'=>$uid))->select();
	
		$temp = array();
		foreach ($list as $key => &$val) {
			if($temp[$val['pid']]){
				$children = $temp[$val['pid']];
			}else{
				$children = array();
			}
			$val['checked'] = (in_array($val['id'], $priv_ids))? 'checked' : '';
			$children[] = $val;
			$temp[$val['pid']] = $children;
		}
		$data = array();
		foreach ($list as &$val) {
			if($val['pid'] == 0){
				$children1 = $temp[$val['id']];
				foreach($children1 as &$childrenVal){
					$childrenVal['child'] = $temp[$childrenVal['id']];
				}
				$val['child'] = $children1;
				$data[] = $val;
			}
		}
		$result = array();
		foreach($data as $d){
			$area1 = $d['name'];
			$result[] = array('id'=>$d['id'],'area1'=>$area1);
			if(!empty($d['child'])){
				foreach($d['child'] as $dd){
					$area2 = $dd['name'];
					$result[] = array('id'=>$dd['id'],'area1'=>$area1,'area2'=>$area2);
					if(!empty($dd['child'])){
						foreach($dd['child'] as $ddd){
							$area3 = $ddd['name'];
							$result[] = array('id'=>$ddd['id'],'area1'=>$area1,'area2'=>$area2,'area3'=>$area3);
						}
					}
				}
			}
		}
		$this->assign('list', $result);
		$this->display();
	}
	
}