<?php

class messageAction extends backendAction{

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('message');
    }

    public function _before_index() {
        $big_menu = array(
        	'title' => L('发送通知'),
            'iframe' => U('message/add'),
            'id' => 'add',
            'width' => '500',
            'height' => '320'
        );
        $this->assign('big_menu', $big_menu);
        $this->assign('type',$type);
    }

    protected function _search() {
        $map = array();
        ($time_start = $this->_request('time_start', 'trim')) && $map['add_time'][] = array('egt', strtotime($time_start));
        ($time_end = $this->_request('time_end', 'trim')) && $map['add_time'][] = array('elt', strtotime($time_end)+(24*60*60-1));
        ($keyword = $this->_request('keyword', 'trim')) && $map['info'] = array('like', '%'.$keyword.'%');
        ($from_name = $this->_request('from_name', 'trim')) && $map['from_name'] = array('like', '%'.$from_name.'%');
        ($to_name = $this->_request('to_name', 'trim')) && $map['to_name'] = array('like', '%'.$to_name.'%');
        $type = $this->_request('type', 'intval');
        if( $type ){
            if( $type==1 ){
                $map['from_id'] = 0;
            }else if( $type==2 ){
                $map['from_id'] = array('gt',0);
            }
        }
        $this->assign('search', array(
            'time_start' => $time_start,
            'time_end' => $time_end,
            'from_name' => $from_name,
            'to_name'   => $to_name,
            'type'  => $type,
            'keyword' => $keyword,
        ));
        return $map;
    }

    public function add() {
        if (IS_POST) {
            //内容
            $type = $this->_post('type', 'trim', 'custom');
            //用户
            $to_name = $this->_post('to_name', 'trim');
            //发送者
            $from_user = session('admin');
            $from_name = $from_user['username'];
            //接收者
            $to_user = array(array('id'=>'0', 'username'=>'0'));
            if ($to_name) {
                //指定用户
                $to_name = split(PHP_EOL, $to_name);
                $to_user = M('user')->field('id,username')->where(array('id'=>array('in', $to_name)))->select();
            }
            //内容
            $info = $this->_post('info', 'trim');
            !$info && $this->ajaxReturn(0, L('message_empty'));
            //逐条发送
            $user_msgtip_mod = D('user_msgtip');
            foreach ($to_user as $val) {
                $this->_mod->create(array(
                    'ftid' => $val['id'],
                    'from_id' => 0,
                    'from_name' => $from_name,
                    'to_id' => $val['id'],
                    'to_name' => $val['username'],
                    'info' => $info,
                ));
                $this->_mod->add();
                if ($val['id'] != '0') {
                    $user_msgtip_mod->add_tip($val['id'], 4);
                }
            }
            $this->ajaxReturn(1, L('operation_success'), '', 'add');
        } else {
            //通知模版
            $tpl_list = M('message_tpl')->field('id,alias,name')->where(array('type'=>'msg', 'is_sys'=>'0'))->select();
            $this->assign('tpl_list', $tpl_list);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }
    
    public function user() {
    	if(IS_POST){
    		//内容
    		$type = $this->_post('type', 'trim', 'custom');
    		//用户
    		$to_id = $this->_post('to_id', 'trim');
    		$to_name = $this->_post('to_name', 'trim');
    		//发送者
    		$from_user = session('admin');
    		$from_name = $from_user['username'];
    		//内容
    		$info = $this->_post('info', 'trim');
    		!$info && $this->ajaxReturn(0, L('message_empty'));
    		//逐条发送
    		$user_msgtip_mod = D('user_msgtip');
    		$this->_mod->create(array(
    				'ftid' => $to_id,
    				'from_id' => 0,
    				'from_name' => $from_name,
    				'to_id' => $to_id,
    				'to_name' => $to_name,
    				'info' => $info,
    		));
    		$this->_mod->add();
    		if ($to_id != '0') {
    			$user_msgtip_mod->add_tip($to_id, 4);
    		}
    		$this->ajaxReturn(1, L('operation_success'), '', 'add');
    	}else {
    		if (IS_AJAX) {
    			$id = $_GET['id'];
    			$user = D('user')->find($id);
    			$this->assign('user', $user);
				$response = $this->fetch();
				$this->ajaxReturn(1, '', $response);
			} else {
				$this->display();
			}
    	}
    }

}