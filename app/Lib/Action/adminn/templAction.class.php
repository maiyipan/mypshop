<?php
class templAction extends backendAction {

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('templ');
    }
    public function _search() {
        $map = array();
        $map ['uid'] =  array('in', $this->getUidList());// $this->shopId();
        return $map;
    }

    public function index() {
        $big_menu = array(
            'title' => '添加微信消息模板',
            'iframe' => U('templ/add'),
            'id' => 'add',
            'width' => '520',
            'height' => '110',
        );
        $this->assign('big_menu', $big_menu);
        $where['uid'] = $this->shopId();
        $count =  $this->_mod->where($where)->count();
        //Log::write('count>>>'.$count);
        $pager = new Page($count, $pagesize);
        $page = $pager->show();
        $this->assign("page", $page);
        
        $select =  $this->_mod->where($where)->limit($pager->firstRow.','.$pager->listRows);
        $list = $select->select();
        foreach ($list as $key=>$val){
        	if($val['key'] == 1){
        		$list[$key]['name'] = '订单完成模板';
        	}else {
        		$list[$key]['name'] = '退款完成模板';
        	}
        }
        //Log::write('list>>'.$list['sid']);
        $this->assign('list', $list);
        $this->assign('list_table', true);
        $this->display();
    }
	/**
	 * 添加
	 * {@inheritDoc}
	 * @see backendAction::add()
	 */
    public function add() {
    	if(IS_POST){
    		$save_info ['uid'] = $this->shopId(); //店铺id
    		$save_info ['key'] = $this->_post('key','trim');				//模板key
    		$save_info ['val'] = $this->_post('templateid','trim');//模板id
    		$id_info = $this->_mod->where(array('uid'=>$this->shopId(),'key'=>$save_info ['key']))->find();
    		if($id_info){
    			
    			$this->error('不能创建多个相同模板，请重试。',U('templ/index'));
    		
    		}else {
    			$add_info = $this->_mod->add($save_info);
    			//Log::write($this->_mod->getLastSql(), 'DEBUG');
    			if($add_info){
    				$this->success('添加成功',U('templ/index'));
    			}else {
    				$this->error('添加失败，请重试',U('templ/index'));
    			}
    		}
    		
    	}else {
    		$response = $this->fetch();
    		$this->ajaxReturn(1,'',$response);
    	}
    	
    }
    /**
     * 编辑
     * {@inheritDoc}
     * @see backendAction::edit()
     */
    public function edit() {
    	if(IS_POST){
    		$id = $this->_post('id','trim');
    		$save_info ['uid'] = $this->shopId(); //店铺id
    		$save_info ['key'] = $this->_post('key','trim');				//模板key
    		$save_info ['val'] = $this->_post('templateid','trim');//模板id
    		$add_info = $this->_mod->where(array('id'=>$id))->save($save_info);
    		//Log::write($this->_mod->getLastSql(), 'DEBUG');
    		if($add_info){
    			$this->success('修改成功',U('templ/index'));
    		}else {
    			$this->error('修改失败，请重试',U('templ/index'));
    		}
    	}else {
	    	$list = $this->_mod->where(array('id'=>$this->_get('id','trim')))->find();
	    	//Log::write($this->_mod->getLastSql(), 'DEBUG');
	    	$this->assign('list',$list);
	    	$response = $this->fetch();
	    	$this->ajaxReturn(1,'',$response);
    	}
    }
    
}