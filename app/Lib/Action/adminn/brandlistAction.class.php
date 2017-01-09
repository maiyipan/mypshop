<?php

class brandlistAction extends backendAction
{

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('brandlist');
    }

    public function _before_index() {
        $big_menu = array(
            'title' => L('添加品牌'),
            'iframe' => U('brandlist/add'),
            'id' => 'add',
            'width' => '400',
            'height' => '130'
        );
        $this->assign('big_menu', $big_menu);
        //默认排序
        $this->sort = 'ordid';
        $this->order = 'ASC';
    }
    
    /**
     * 入库数据整理
     */
    protected function _before_insert($data = '') {
//     	$data['uid'] = $this->shopId();
    	$data['add_time'] = time();
    	return $data;
    }

    protected function _search() {
        $map = array();
        ($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%'.$keyword.'%');
        $this->assign('search', array(
            'keyword' => $keyword,
        ));
//         $map['uid'] = $this->shopId();
        return $map;
    }

    public function ajax_check_name() {
        $name = $this->_get('name', 'trim');
        //Log::write($name,'DEBUG');
        $id = $this->_get('id', 'intval');
        if (D('brandlist')->name_exists($name, $id, $this->shopId())) {
            $this->ajaxReturn(0, L('该品牌名称已存在'));
        } else {
            $this->ajaxReturn(1);
        }
    }
    
      public function deletebrand()
    {
    	 
        $mod = D($this->_name);
      
        $pk = $mod->getPk();
        $ids = trim($this->_request($pk), ',');
        
       
        if ($ids) {
        	$count=M('item')->where("brand in (".$ids.")")->count();
        	if($count>0)
        	{
          IS_AJAX && $this->ajaxReturn(0,'品牌被引用，不能删除');exit;
        	}
        	
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
    
}