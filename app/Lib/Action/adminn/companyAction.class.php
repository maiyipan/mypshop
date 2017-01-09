<?php
class companyAction extends backendAction {
	
	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('company');
	}
	public function _befor_index(){
		$where['shopid'] = $this->shopId();
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		
		$select =  $this->_mod->where($where)->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	
	public function company_delete(){
		$ids = $this->_request('id');
		if ($ids) {
			if (false !== $this->_mod->delete($ids)) {
				$this->ajaxReturn(1, L('operation_success'));
			} else {
				$this->ajaxReturn(0, L('operation_failure'));
			}
		} else {
			$this->ajaxReturn(0, L('illegal_parameters'));
		}
	}
	
	
	
	
	
}