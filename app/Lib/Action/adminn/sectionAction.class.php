<?php

class sectionAction extends backendAction{

     public function _initialize() {
        parent::_initialize();
        $this->_mod = D('section');
    }
    
   /* public function index() {
    	
    	$this->display();
    }*/
    
    //上传图片
    public function ajax_upload_img() {
    	$type = $this->_get('type', 'trim', 'img');
    	if (!empty($_FILES[$type]['name'])) {
    		$dir = date('ym/');
    		$result = $this->_upload($_FILES[$type], 'section/'. $dir );
    		if ($result['error']) {
    			$this->ajaxReturn(0, $result['info']);
    		} else {
    			$savename = $dir . $result['info'][0]['savename'];
    			$this->ajaxReturn(1, L('operation_success'), $savename);
    		}
    	} else {
    		$this->ajaxReturn(0, L('illegal_parameters'));
    	}
    }
}
