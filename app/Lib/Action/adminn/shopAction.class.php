<?php
class shopAction extends backendAction {
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'shop' );
	}
	
	public function _before_index() {
		$this->assign ( 'big_menu', $big_menu );
		$this->list_relation = true;
	}
	
	public function _before_insert($data = '') {
		$uid = md5 ( uniqid ( rand (), true ) );
		$data ['uid'] = $uid;
		
		$lnglat = explode(',',$_POST['lnglat']);
		$data ['latitude'] = $lnglat[0];
		$data ['longitude'] = $lnglat[1];
		
		$data ['weixinurl'] =  C ( 'pin_baseurl' ) . '/weixin/index/uid/'. $uid;
		$data ['token'] = 'weixin';
		
		//生成spid
		$data['spid'] = $this->_mod->get_spid($data['pid']);
		
		return $data;
	}
	
	public function _before_edit() {
		$this->_before_add ();
	}
	
	public function _before_add() {
		$role_list = M('admin_role')->where('status=1')->select();
	
		$shop_list = M('shop')->select();
		$this->assign('shop_list', $shop_list);
		$this->assign('role_list', $role_list);
	}
	
	//商城设置页面处理
	public function shopset(){
		$id=$_GET['id'];
		$m=M("shop");
		$arr=$m->find($id);
		$this->assign('data',$arr);
		$this->display();
		return $id;
	}
	
	public function setadd(){
		if(IS_POST){
			if (false === $data = $this->_mod->create ()) {
				$this->error ( $this->_mod->getError () );
			}
	
			if ($_POST['appid']==null) {
	
				$this->error ( '请填写AppID' );
			}else{
	
				$data['appid']=$_POST['appid'];
			}
			if ($_POST['appsecret']==null){
	
				$this->error('请填写AppSecret');
			}else{
	
				$data['appsecret']=$_POST['appsecret'];
			}
			if ($_POST['wxpayone']==null){
	
				$this->error('请填写微信支付参数1');
			}else{
	
				$data['wxpayone']=$_POST['wxpayone'];
			}
			if ($_POST['wxpaytwo']==null){
	
				$this->error('请填写微信支付参数2');
			}else {
	
				$data['wxpaytwo']=$_POST['wxpaytwo'];
			}
			if ($_POST['switch']==on){
				$data['status']=1;
			}else {
				$data['status']=0;
			}
			$m=M('shop');
			$data['id']=$_POST['id'];
				
			//最后保存
			$count=$m->save($data);
			if ($count>0) {
				$this->success('数据修改成功', U('shop/index'));//可以用U函数代替
			}else{
				$this->error("数据修改失败");
	
			}
		}
	
	}
	
	public function edit(){
		$mod = D($this->_name);
		$pk = $mod->getPk();
		if (IS_POST) {
			$lnglat = explode(',',$_POST['lnglat']);
			$_POST['latitude'] = $lnglat[0];
			$_POST['longitude'] = $lnglat[1];
			unset($_POST['lnglat']);
			if (false === $data = $mod->create()) {
				IS_AJAX && $this->ajaxReturn(0, $mod->getError());
				$this->error($mod->getError());
			}
			if (method_exists($this, '_before_update')) {
				$data = $this->_before_update($data);
			}
			
			if (false !== $mod->save($data)) {
				if( method_exists($this, '_after_update')){
					$id = $data['id'];
					$this->_after_update($id);
				}
				IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'edit');
				$this->success(L('operation_success'));
			} else {
				IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
				$this->error(L('operation_failure'));
			}
		} else {
			$id = $this->_get('id', 'intval');
			
			/* if(empty($uid)){
				$uid = $this->shopId();
			} */
			$info = $mod->where(array('id'=>$id))->find();
			//dump($mod->getLastSql(), 'DEBUG');
			$this->assign('info', $info);
			
			//分类
			$spid = $this->_mod->where(array('id'=>$id))->getField('spid');
			if( $spid==0 ){
				$spid = $info['spid'];
			}else{
				$spid .=  $info['spid']; 
			}
			$this->assign('selected_ids',$spid); //分类选中 
			
			$this->display();
		}
	}
	//公告 
	public function toconfig(){
		
		$where = $this->search();
		$count =  M('shop_configs')->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		
		$select =  M('shop_configs')->where($where)->limit($pager->firstRow.','.$pager->listRows);
		$list = M('shop_configs')->where($where)->limit($pager->firstRow.','.$pager->listRows)->select();
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	//搜索
	private function search() {
	
		//Log::write("_search----dis");
		$map = array();
		$title = $this->_request('notice', 'trim');
		$title && $map['notice'] = array('like','%'. $title .'%');
		$map['uid'] = array('in',$this->getUidList());
		$this->assign('search', array(
				'notice' => $title,
		));
		return $map;
	}
	//公告状态修改
	public function toconfig_ajax_edit(){
		$mod = D('shop_configs');
		$pk = $mod->getPk();
		$id = $this->_get($pk, 'intval');
		$field = $this->_get('field', 'trim');
		$val = $this->_get('val', 'trim');
		//允许异步修改的字段列表  放模型里面去 TODO
		$mod->where(array($pk=>$id))->setField($field, $val);
		//Log::write($mod->getLastSql(), 'DEBUG');
		$this->ajaxReturn(1);
	}
	//编辑公告
	public function toconfig_edit(){
		$mod = M('shop_configs');
		if(IS_POST){
			$_POST['uid'] = $this->shopId();
			$flag = $mod->where(array('id'=>$_POST['id']))->save($_POST);
			//Log::write($mod->getLastSql(), 'DEBUG');
			if (false !== $flag) {
				$this->success(L('operation_success'),U('shop/toconfig'));
			} else {
				$this->error(L('operation_failure'));
			}
		}else {
			$id = $this->_get('id', 'intval');
			$map['id'] = $id;
			$info = $mod->where($map)->find();
			$this->assign('info', $info);
			$this->display();
		}
		
	}
	//添加公告
	public function toconfig_add(){
		$mod = M('shop_configs');
		if (IS_POST){
			$_POST['uid'] = $this->shopId();
			$flag = $mod->add($_POST);
			//Log::write($mod->getLastSql(), 'DEBUG');
			if (false !== $flag) {
					$this->success(L('operation_success'),U('shop/toconfig'));
				} else {
					$this->error(L('operation_failure'));
				}
		}else {
			$this->display();
		}
	}
	//删除公告
	public function toconfig_del(){
		$ids = $this->_request('id');
		if ($ids) {
			$delet_info = M('shop_configs')->where(array('id'=>$this->_request('id')))->delete();
			if ($delet_info) {
				$this->ajaxReturn(1, L('operation_success'));
			} else {
				$this->ajaxReturn(0, L('operation_failure'));
			}
		} else {
			$this->ajaxReturn(0, L('illegal_parameters'));
		}
	}
	public function toeditpwd(){
		$this->assign('userid',  $_SESSION['admin']['id']);
		$this->display();
	}
	
	protected function _search() {
		($keyword = $this->_request ( 'keyword', 'trim' )) && $map ['name'] = array (
				'like', '%' . $keyword . '%'
		);
		$this->assign ( 'search', array (
				'keyword' => $keyword
		) );
		return $map;
	}
	
	/**
	 * 获取紧接着的下一级分类ID
	 */
	public function ajax_getchilds() {
		$id = $this->_get('id', 'intval');
		$type = $this->_get('type', 'intval', null);
		$map = array('pid'=>$id);
		if (!is_null($type)) {
			$map['type'] = $type;
		}
		$return = $this->_mod->field('id,name')->where($map)->select();
// 		dump($this->_mod->getLastSql(), 'DEBUG');
		if ($return) {
			$this->ajaxReturn(1, L('operation_success'), $return);
		} else {
			$this->ajaxReturn(0, L('operation_failure'));
		}
	}
	
	public function distribute(){
		$uid = $this->shopId();
		$model = D('shop_distribute');
		$list = $model->where(array('uid'=>$uid))->select();
		
		$priv_ids = array();
		foreach ($list as $val) {
			$priv_ids[] = $val['area_id'];
		}
		$list = M('area')->order('pid asc')->select();
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
		$this->assign('list', $data);
		$this->display();
	}
	
	public function edit_distribute(){
		$area_mod = D('area');
		$shop_distribute_mod = D('shop_distribute');
		$uid = $this->shopId();
		$shop_distribute_mod->where(array('uid'=>$uid))->delete();
		if (is_array($_POST['area_id']) && count($_POST['area_id']) > 0) {
			foreach ($_POST['area_id'] as $area_id) {
				$shop_distribute_mod->add(array(
						'uid' => $uid,
						'area_id' => $area_id
				));
			}
		}
		$this->success(L('operation_success'));
	}
	
	public function money(){
		$mod = M('item_order');
		$where['uid']  =  array('in',$this->getUidList());;//$this->shopId();
		$sid = $this->getUidList();
// 		$where['status'] =  array('egt',2);;
		$this->assign('countValue',  $mod->where('status >= 2 and status != 5')->sum('order_sumPrice'));
		$where['_string'] = " status >=2 and status != 5 and to_days(from_unixtime(add_time,'%Y%m%d')) = to_days(now())";
// 		$this->assign('todayValue',  $mod->where("uid in ('".$sid."') and status >= 2 and to_days(from_unixtime(add_time,'%Y%m%d')) = to_days(now())")->sum('order_sumPrice'));
		$this->assign('todayValue',  $mod->where($where)->sum('order_sumPrice'));
		
		$where['_string'] = " status >=2 and status != 5 and (to_days(now()) - to_days(from_unixtime(add_time,'%Y%m%d'))) = 1";
		//$this->assign('yesterdayValue', $mod->where("uid in( '".$sid."') and status >= 2 and (to_days(now()) - to_days(from_unixtime(add_time,'%Y%m%d'))) = 1")->sum('order_sumPrice'));
		$this->assign('yesterdayValue', $mod->where($where)->sum('order_sumPrice'));
		//$this->assign('curMonValue',   $mod->where("uid in ('".$sid."') and status >= 2 and from_unixtime(add_time,'%Y%m') = date_format(curdate(), '%Y%m')")->sum('order_sumPrice'));
		$where['_string'] = " status >=2 and status != 5 and from_unixtime(add_time,'%Y%m') = date_format(curdate(), '%Y%m')";
		$this->assign('curMonValue',   $mod->where($where)->sum('order_sumPrice'));
		//Log::write($mod->getLastSql(), 'DEBUG');
		$this->display();
	}
	
	public function money_json(){
		$id = $this->getUidList();
		$where['uid']  =  array('in', $id);
// 		$where['status'] =  array('egt',2);
		$where['_string'] = " status >=2 and status != 5 and TO_DAYS(FROM_UNIXTIME(add_time,'%Y%m%d'))>(TO_DAYS(NOW())-30)";
		$res = M('item_order')
				->where($where)
				->field("SUM(order_sumPrice) value, FROM_UNIXTIME(add_time,'%m/%d') name, FROM_UNIXTIME(add_time,'%Y-%m-%d') displayDate ")
				->group("DATE(FROM_UNIXTIME(add_time,'%Y%m%d'))")
				->order('displayDate asc')
				->select();
// 		$sql = "SELECT SUM(order_sumPrice) value ,FROM_UNIXTIME(add_time,'%m/%d') name ,
// 				FROM_UNIXTIME(add_time,'%Y-%m-%d') displayDate  
// 				FROM weixin_item_order
// 				WHERE status>=2 and  TO_DAYS(FROM_UNIXTIME(add_time,'%Y%m%d'))>(TO_DAYS(NOW())-30) and uid='".  $id.  "'
// 				GROUP BY DATE(FROM_UNIXTIME(add_time,'%Y%m%d')) ORDER BY NAME ASC";
		
// 		//Log::write($sql);
// 		$res = M('')->query($sql);
		$this->ajaxReturn(1, L('operation_success'), $res);
	}
	
	
	public function index() {
		$tree = new Tree();
		$tree->icon = array('│ ','├─ ','└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		
		$where['uid']  = array('in',$this->getUidList());
		$result = $this->_mod->where($where)->select();
		$array = array();
		foreach($result as $r) {
			$r['str_status'] = '<img data-tdtype="toggle" data-id="'.$r['id'].'" data-field="status" data-value="'.$r['status'].'" src="__STATIC__/images/admin/toggle_' . ($r['status'] == 0 ? 'disabled' : 'enabled') . '.gif" />';
			$r['str_isself'] = '<img data-tdtype="" data-id="'.$r['id'].'" data-field="" data-value="'.$r['isself'].'" src="__STATIC__/images/admin/toggle_' . ($r['isself'] == 1 ? 'disabled' : 'enabled') . '.gif" />';
			$r['str_type'] = $r['type'] ? '<span class="gray">'.L('item_cate_type_tag').'</span>' : L('item_cate_type_cat');
			$r['str_manage'] = ($r['isself'] == 0 ? '<a class="btn btn-link" href="'.U('shop/shopset',array('id'=>$r['id'])).'" >'.'公众号配置'.'</a> ' : '') . '
								<a class="btn btn-link" href="'.U('shop/edit',array('id'=>$r['id'])).'" >'.L('edit').'</a>  |
								<a href="javascript:void(0);" class="J_showdialog btn btn-link" 
										data-uri="'. U('shop/index_order', array('id'=>$r['id'])) .'"
												data-title="'.'首页推广顺序修改' .'" data-id="edit" data-width="520" 
														data-height="110">'.'首页推广顺序' .'</a> |
                                <a href="javascript:;" class="J_confirmurl btn btn-link" 
										data-acttype="ajax" data-uri="'.U('shop/delete',array('id'=>$r['id'])).'"
												data-msg="'.sprintf(L('confirm_delete_one'),$r['name']).'">'.L('delete').'</a> ';
	
			$isPre = $this->isLeader($r['pid'], $result);
			//$r['parentid_node'] = ($r['pid'])? ' class="child-of-node-'.$r['pid'].'"' : '';
			
			if (!$isPre) {
				$r['pid'] = 0;
			}
			$r['parentid_node'] = $isPre ? ' class="child-of-node-'.$r['pid'].'"' : '';
			$array[] = $r;
		}
		$str  = "<tr id='node-\$id' \$parentid_node>
                <td align='center'><input type='checkbox' value='\$id' class='J_checkitem'></td>
                <td align='center'>\$id</td>
                <td>\$spacer<span data-tdtype='' data-field='name' data-id='\$id'   style='color:\$fcolor'>\$name</span></td>
                <td align='center'>\$address</td>
                <td align='center'><span data-tdtype='' data-field='ordid' data-id='\$id' >\$tel</span></td>
                <td align='center'>\$str_isself</td>
                <td align='center'>\$str_status</td>
                <td align='center'>\$str_manage</td>
                </tr>";
		$tree->init($array);
		$list = $tree->get_tree(0, $str);
		
		$this->assign('list', $list);
		//bigmenu (标题，地址，弹窗ID，宽，高)
		/* $big_menu = array(
				'title' => L('add_item_cate'),
				'iframe' => U('item_cate/add'),
				'id' => 'add',
				'width' => '520',
				'height' => '360'
		);
		$this->assign('big_menu', $big_menu); */
		$this->assign('list_table', true);
		$this->display();
	}
	
	/**
	 * 判断在当前列表中，是不是只有子，没有父，即为头头
	 * @param $ma 需要判断的对象
	 * @param $arr 判断的范围
	 * @return boolean 
	 * */
	public function isLeader($ma,$arr) {
		$data = $arr;
		$is = 0;
		foreach ($data as $key => $val) {
			//Log::write('ma>>>' . $ma . '---val--'. $val['id']);
			if ($ma == $val['id']) {
				$is = 1;
				break;
			}
		}
		//Log::write('result>>' . $is);
		return $is;
	}
	
	
	/**
	* 修改提交数据
	*/
	protected function _before_update($data = '') {
		$shopTmp = $this->_mod->field('pid')->where(array('id'=>$data['id']))->find();
		if ($data['pid'] != $shopTmp['pid']) {
			//不能把自己放到自己或者自己的子目录们下面
			$wp_spid_arr = $this->_mod->get_child_ids($data['id'], true);
			if (in_array($data['pid'], $wp_spid_arr)) {
				if (IS_AJAX) {
					$this->ajaxReturn(0, L('cannot_move_to_child'));
				} else {
					$this->error(L('cannot_move_to_child'));
				}
			}
			//重新生成spid
			$data['spid'] = $this->_mod->get_spid($data['pid']);
		}
		return $data;
	}

	public function index_order() {
		parent::edit();
	}
}