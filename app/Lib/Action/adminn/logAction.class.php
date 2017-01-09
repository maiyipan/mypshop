<?php
class logAction extends backendAction {
	
	public function _initialize() {
		parent::_initialize();
		/* $this->_mod = D('menu');
		$this->item_order=M('item_order'); */
		$this->sid = $_SESSION['admin']['uid'];
	}
	public function loginlog() {
		$mod = D('login_log');
		!empty($mod) && $this->_list($mod);
		$this->display();
	}
	
	public function operatelog() {
		$mod = D('operate_log');
		!empty($mod) && $this->_list($mod);
		$this->display();
	}
}