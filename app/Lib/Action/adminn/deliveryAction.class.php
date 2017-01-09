<?php

class deliveryAction extends backendAction
{

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('delivery');
    }

    public function _before_index() {
        $big_menu = array(
            'title' => L('添加快递公司'),
            'iframe' => U('delivery/add'),
            'id' => 'add',
            'width' => '400',
            'height' => '130'
        );
        $this->assign('big_menu', $big_menu);

        //默认排序
        $this->sort = 'ordid';
        $this->order = 'ASC';
    }

    protected function _search() {
        $map = array();
        ($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%'.$keyword.'%');
        $this->assign('search', array(
            'keyword' => $keyword,
        ));
        return $map;
    }

    public function ajax_check_name() {
        $name = $this->_get('name', 'trim');
        $id = $this->_get('id', 'intval');
        $where = "name='" . $name."'" ;
        if($id){
        	$where .= "AND id<>'" . $id . "'";
        }
        $result = $this->_mod->where($where)->count('id');
        if ($result) {
        	$this->ajaxReturn(0, L('该快递公司已存在'));
        } else {
        	$this->ajaxReturn(1);
        }
    }
    
}