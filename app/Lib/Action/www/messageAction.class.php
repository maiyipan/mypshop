<?php
class messageAction extends frontendAction {
	public function _initialize() {
		parent::_initialize ();
	}
	
	public function index() {
		$userid = $this->visitor->info ['id'];
		$list = D('message')->where('to_id=0 or to_id ='.$userid)->order('add_time desc')->select();
		$this->assign("list",$list);
		$this->display ();
	}
}