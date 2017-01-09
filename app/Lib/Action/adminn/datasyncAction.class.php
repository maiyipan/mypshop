<?php
class datasyncAction extends backendAction {

    public function _initialize() {
//         parent::_initialize();
    }

    public  function  index() {
		dump($this->_request('op'));
// 		/etc/nginx/conf/vhost
    	$this->display();
    }
}