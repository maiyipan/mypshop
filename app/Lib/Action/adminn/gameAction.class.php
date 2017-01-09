<?php
class gameAction extends backendAction {
	
	
	public function _initialize() {
		parent::_initialize();
		$this->_mod = D('game');
	}
	
	public function _before_index() {
	  $big_menu = array(
	      'title' => L('插件添加'),
	      'iframe' => U('game/add'),
	      'id' => 'add',
	      'width' => '520',
	      'height' => '410',
	  );
	  $this->assign('big_menu', $big_menu);
	}
	
	public function _search() {
	  $map = array();
	  $map ['sid'] =  array('in', $this->getUidList());// $this->shopId();
	  return $map;
	}
	
	protected function _before_insert($data) {
        //判断开始时间和结束时间是否合法
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
//         dump($data['start_time']);
//         dump( $data['end_time'] );
        if ($data['start_time'] >= $data['end_time']) {
           //dump('ddd');
            $this->error(L('ad_endtime_less_startime'));
        }
        $data['sid'] = $this->shopId();
        return $data;
    }

	
	//上传图片
	public function ajax_upload_img() {
		$type = $this->_get('type', 'trim', 'img');
		if (!empty($_FILES[$type]['name'])) {
			$dir = date('ym/d/');
			
			$result = $this->_upload($_FILES[$type], 'game/'. $dir,  array (
					'width' => C ( 'pin_item_bimg.width' ) . ',' . C ( 'pin_item_img.width' ) . ',' . C ( 'pin_item_simg.width' ),
					'height' => C ( 'pin_item_bimg.height' ) . ',' . C ( 'pin_item_img.height' ) . ',' . C ( 'pin_item_simg.height' ),
					'suffix' => '_b,_m,_s' ));
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
	
	
	protected function _before_update($data) {
		
		//判断开始时间和结束时间是否合法
		$data['start_time'] = strtotime($data['start_time']);
		$data['end_time'] = strtotime($data['end_time']);
		if ($data['start_time'] >= $data['end_time']) {
			$this->error(L('ad_endtime_less_startime'));
		}
		return $data;
	}
	/* protected function _after_update($data) {
		$this->redirect('index');
		exit();
	} */
	
	
}