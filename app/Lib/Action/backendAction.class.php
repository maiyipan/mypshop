<?php
/**
 * 后台控制器基类
 *
 * @author  jimyan
 */
class backendAction extends baseAction
{
    protected $_name = '';
    protected $menuid = 0;
    
    protected $uid = '';
    public function _initialize() {
        parent::_initialize();
        $this->uid = $_SESSION['admin']['uid']; 
        $this->_name = $this->getActionName();
        $this->check_priv();
        $this->saveOperateLog();
        $this->shoplist();
        
        $this->menuid = $this->_request('menuid', 'trim', 0);
        //初始化店铺配置
        if ($this->menuid) {
        	$role_id = $_SESSION['admin']['role_id'];
            $sub_menu = D('menu')->sub_menu($this->menuid, $role_id, $this->big_menu);
            $selected = '';
            foreach ($sub_menu as $key=>$val) {
                $sub_menu[$key]['class'] = '';
                if (MODULE_NAME == $val['module_name'] && ACTION_NAME == $val['action_name'] && strpos(__SELF__, $val['data'])) {
                    $sub_menu[$key]['class'] = $selected = 'on';
                }
            }
            if (empty($selected)) {
                foreach ($sub_menu as $key=>$val) {
                    if (MODULE_NAME == $val['module_name'] && ACTION_NAME == $val['action_name']) {
                        $sub_menu[$key]['class'] = 'on';
                        break;
                    }
                }
            }
//             dump($sub_menu);
            $this->assign('sub_menu', $sub_menu);
        } 
        $this->assign('menuid', $this->menuid);
        
        $shoplist= M('shop')->select();
        $tmp = array();
        foreach ($shoplist as $key=>$val) {
          $tmp[$val['uid']]= $val['name'];
        }
        $this->assign('shoplist',$tmp);
        
        
        
        //dump($shoplist);
        //dump($_SESSION['admin']);
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

    /**
     * 添加
     */
    public function add() {
    	
    	
        $mod = D($this->_name);
        if (IS_POST) {
            if (false === $data = $mod->create()) {
                IS_AJAX && $this->ajaxReturn(0, $mod->getError());
                $this->error($mod->getError());
            }
            if (method_exists($this, '_before_insert')) {
            	
                $data = $this->_before_insert($data);
            }
            if( $mod->add($data) ){
                if( method_exists($this, '_after_insert')){
                    $id = $mod->getLastInsID();
                    $this->_after_insert($id);
                }
                IS_AJAX && $this->ajaxReturn(1, L('operation_success'), '', 'add');
                $this->success(L('operation_success'));
            } else {
                IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                $this->error(L('operation_failure'));
            }
        } else {
        	if (method_exists($this, '_before_add')) {
        		$data = $this->_before_add($data);
        	}
            $this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
        }
    }

    /**
     * 修改
     */
    public function edit()
    {
        $mod = D($this->_name);
        $pk = $mod->getPk();
        if (IS_POST) {
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
            $id = $this->_get($pk, 'intval');
            $info = $mod->find($id);
            $this->assign('info', $info);
            $this->assign('open_validator', true);
            if (IS_AJAX) {
                $response = $this->fetch();
                $this->ajaxReturn(1, '', $response);
            } else {
                $this->display();
            }
        }
    }

    /**
     * ajax修改单个字段值
     */
    public function ajax_edit()
    {
        //AJAX修改数据
        $mod = D($this->_name);
        $pk = $mod->getPk();
        $id = $this->_get($pk, 'intval');
        $field = $this->_get('field', 'trim');
        $val = $this->_get('val', 'trim');
        //允许异步修改的字段列表  放模型里面去 TODO
        $mod->where(array($pk=>$id))->setField($field, $val);
        $this->ajaxReturn(1);
    }

    /**
     * 删除
     */
    public function delete()
    {
        $mod = D($this->_name);
        $pk = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        if ($ids) {
            if (false !== $mod->delete($ids)) {
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

    /**
     * 获取请求参数生成条件数组
     */
    protected function _search() {
    	
        //生成查询条件
        $mod = D($this->_name);
        $map = array();
        foreach ($mod->getDbFields() as $key => $val) {
            if (substr($key, 0, 1) == '_') {
                continue;
            }
            if ($this->_request($val)) {
                $map[$val] = $this->_request($val);
            }
        }
        $map['shopid']=$_SESSION['admin']['currid'];
        return $map;
    }

    /**
     * 列表处理
     *
     * @param obj $model  实例化后的模型
     * @param array $map  条件数据
     * @param string $sort_by  排序字段
     * @param string $order_by  排序方法
     * @param string $field_list 显示字段
     * @param intval $pagesize 每页数据行数
     */
    protected function _list($model, $map = array(), $sort_by='', $order_by='', $field_list='*', $pagesize=20)
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

        //如果需要分页
        if ($pagesize) {
            $count = $model->where($map)->count($mod_pk);
            $pager = new Page($count, $pagesize);
        }
        $select = $model->field($field_list)->where($map)->order($sort . ' ' . $order);
        $this->list_relation && $select->relation(true);
        if ($pagesize) {
            $select->limit($pager->firstRow.','.$pager->listRows);
            $page = $pager->show();
            $this->assign("page", $page);
        }
        $list = $select->select();
        
        //dump($select->getLastSql());
        //dump($list);
        $this->assign('list', $list);
        $this->assign('list_table', true);
    }

    public function check_priv() {
    	//session('admin', null);
        if (MODULE_NAME == 'attachment') {
            return true;
        }
        if ( (!isset($_SESSION['admin']) || !$_SESSION['admin']) && !in_array(ACTION_NAME, array('login','verify_code')) ) {
        	$loginurl = 'http://' . $_SERVER ['SERVER_NAME'] .U('index/login') ;
        	echo '<script>
        	 		window.location.href="' . $loginurl.'";'.'
					window.location.reload; 
        	 		</script>'; 
        }
        if($_SESSION['admin']['role_id'] == 1) {
            return true;
        }
        if (in_array(MODULE_NAME, explode(',', 'index'))) {
            return true;
        }
        $menu_mod = M('menu');
        $menu_id = $menu_mod->where(array('module_name'=>MODULE_NAME, 'action_name'=>ACTION_NAME))->getField('id'); 
        //$priv_mod = D('admin_role_priv');  
        /* $priv_mod = D('admin_auth');
        $r = $priv_mod->where(array('menu_id'=>$menu_id, 'role_id'=>$_SESSION['admin']['role_id']))->count();
        if (!$r) {
            $this->error(L('_VALID_ACCESS_'));
        } */
    }
    
    protected function update_config($new_config, $config_file = '') {
        !is_file($config_file) && $config_file = CONF_PATH . 'home/config.php';
        if (is_writable($config_file)) {
            $config = require $config_file;
            $config = array_merge($config, $new_config);
            file_put_contents($config_file, "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
            @unlink(RUNTIME_FILE);
            return true;
        } else {
            return false;
        }
    }
    
    public function saveOperateLog(){
    	$admin_id= $_SESSION['admin']['username'];
    	$tag_arg = array ('admin_id' => $admin_id,  'action' => MODULE_NAME, 'operate'=> ACTION_NAME);
    	tag ( 'operate_log', $tag_arg );
    }
    
    
    /**
     * return 门店本身uid及子uid
     * */
    public function getUidList(){
    	$uid = $_SESSION['admin']['uid'];
    	$currid = $_SESSION['admin']['currid']; 
//     	dump($_SESSION['admin']);
    	$shopModel = D('shop');
    	if  ($uid == $currid) {  //管理员
    		if ($_SESSION['admin']['shopcount'] > 0 ) {
    			$tmplist = $shopModel->getChildsById($_SESSION['admin']['shopid']);
    			foreach ($tmplist as $va) {
    				foreach ($va as $v) {
    					//dump($v['uid']);
    					$currid  = $currid. ',' . $v['uid'];
    				}
    			}
    			/* foreach ($_SESSION['admin']['shopList'] as $key=>$val) {
    			} */
    		}
    		return $currid;
    	} else {
    		 $shop = $shopModel->getShopByUid($currid);
    		 $tmplist = $shopModel->getChildsById($shop['id']);
    		 foreach ($tmplist as $va) {
    		 	foreach ($va as $v) {
    		 		//dump($v['uid']);
    		 		$currid  = $currid. ',' . $v['uid'];
    		 	}
    		 }
    		return $currid;
    	}
    	
    }
    
    public function shopId(){
    	return $_SESSION['admin']['currid'];
    }
    public function  shoplist() {
    	$this->assign('quan', $_SESSION['admin']['uid']);
    	$this->assign('shopList',$_SESSION['admin']);
    }
    public function qiehuan(){
    	$currid = $_GET['sid'];
    	$uid = $_SESSION['admin']['uid'];
    	//Log::write($currid);
    	if ($currid != $uid) {
    		foreach ($_SESSION['admin']['shopList'] as $key=>$val) {
    			//切换的店铺id和子店列表对比，session中当前列表
    			if ($val['uid'] ==$currid){  
    				$_SESSION['admin']['currid'] = $currid;
    				$_SESSION['admin']['currname'] =  $val['name'];
    			}
    		}
    	} else {
    		$_SESSION['admin']['currid'] = $_SESSION['admin']['uid'];
    		$_SESSION['admin']['currname'] =  $_SESSION['admin']['shopname'];
    	}
    }
    /**
     * 添加后台操作订单 日志
     */
    function addadminlogs($account,$orderid,$mode,$otherinfo=''){
        $logsdata  = array(
                           'uid'    => $this->uid,//店铺id号
                           'account'=> $account,  //操作人员
                           'orderid'=> $orderid,  //订单号
                           'mode'   => $mode,     //操作模块
                           'ctime'  => date('Y-m-d H:i:s'),
                           'otherinfo'=>$otherinfo
                        );
        M('adminorder_logs')->add($logsdata);
    }
    
}
