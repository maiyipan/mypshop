<?php
class articleAction extends baseAction {
	public function index() {
		$id =  $this->_get('id', 'intval');
		$where ['id'] = $id;
		$article = M ( 'media_item' )->where ( $where )->find ();
		if ($article) {
			$this->assign ( 'info', $article );
			$this->display();
		} else {
			$this->_404 ();
		}
	}
}