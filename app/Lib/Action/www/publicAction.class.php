<?php
class publicAction extends frontendAction {
	
	
	public function success() {
		$this->display();
	}
	
	public function error() {
		$this->display();
	}
	
	
	public function go404() {
		$this->_404();
	}
	
	public function go505() {
		
	}
}