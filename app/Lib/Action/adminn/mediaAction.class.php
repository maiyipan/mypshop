<?php

/**
 * 素材管理
 * */
class mediaAction extends backendAction {

    public $list_relation = true;
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('media');
        $this->_media_item_mod = D('media_item');
    }
    
    public function _search() {
    	$map = array();
    	$map ['shopid'] =  array('in', $this->getUidList());
    	return $map;
    }
    
    
    public function add() {
    	//Log::write('op>>>'.$_GET['op']);
    	//exit();
    	$op = $_GET ['op'];
    	if (! empty ( $op )) {
    		$date = time();
    		$media = array ();
    		$media ['name'] = trim ( $_POST ['name'] );
    		$media ['media_type'] = intval ( $_POST ['media_type'] );
    		$media ['create_time'] = $date;
    		$media ['create_admin'] = '';
    		$media ['shopid']= $this->uid;
    		
    		
    		$titles = $_POST ['titles'];
    		$picurls = $_POST ['picurls'];
    		$urls = $_POST ['urls'];
    		$ids = $_POST['ids'];
    		$contents = $_POST['contents'];
    		$isurls = $_POST['isurls'];
    		$addcontentUrls = $_POST['addcontentUrls'];
    		
    		if ($op == 'add') {
    			if ($media ['media_type'] == 0) { //文本
    				$media_item ['content'] = trim ( $_POST ['content'] );
    				$media_item ['title'] = '';
    				$media_item ['description'] = '';
    				$media_item ['url'] = '';
    				$media_id = M('media') -> data($media)->add();   
//     				dump(M('media')->getLastSql(), 'DEBUG');
    				$media_item['media_id'] = $media_id;   				
    				M('media_item')->data($media_item)->add();
    				
    			} else { //多图文
    				//Log::write('ok--' . $date);
    				$media_item ['content'] = '';
    				$media_id = M('media') -> data($media)->add();
    				for ($i = 0; $i < count ($titles); $i++) {
    					$media_item_list[] = array(
//     							'id'=>$ids[$i],
    							'media_id'=>$media_id,
							    'title'=>$titles[$i],
    							'picurl' => $picurls[$i],
    							'url' => $urls[$i],
    							'content' => $contents[$i],
    							'isurl' => $isurls[$i],
    							'contentUrl' => $addcontentUrls[$i],
    							'add_time' => $date,
    					);
    				}
    				
    				M('media_item')->addAll($media_item_list);		
    				//Log::write('--' . M('media_item')->getLastSql(), 'DEBUG');
    			}
    			/* $keyword ['iskey'] = 1;
    			M ( 'keyword' )->data ( $keyword )->add (); */
    		}
    			
    		if ($op == 'update') {
    			$id = trim ( $_POST ['id'] );
    			if ($media ['media_type'] == 0) { //文本
    				$media_item ['content'] = trim ( $_POST ['content'] );
    				$media_item ['title'] = '';
    				$media_item ['description'] = '';
    				$media_item ['url'] = '';
    				$media_id = M('media') -> data($media)->add();
    				$media_item['media_id'] = $media_id;
    				//M('media_item')->data($media_item)->add();
    			} else { //多图文
    				$media_item ['content'] = '';
    				$media['id'] = $id;
    				M('media')-> data($media)->save();
    				M('media_item')->where('media_id=' .$id)->delete();
    				for ($i = 0; $i < count ($titles); $i++) {
    					$media_item_list[] = array(
    							'media_id'=>$id,
    							'title'=>$titles[$i],
    							'picurl' => $picurls[$i],
    							'url' => $urls[$i],
    							'content' => $contents[$i],
    							'isurl' => $isurls[$i],
    							'contentUrl' => $addcontentUrls[$i],
    							'add_time' => $date,
    					);
    					//M('media_item')->save($media_item_list);
    					////Log::write('update ' .$ids[$i] .':'. M('media_item')->getLastSql(), 'DEBUG','DEBUG');
    				}
    				M('media_item')->addAll($media_item_list);
    			}
    		}
    		if ($op == 'del') {
    			$kid = trim ( $_POST ['kid'] );
    			M ( 'keyword' )->where ( array (
    					'kid' => $kid
    			) )->delete ();
    		}
    	} else {
    		$keyinfo = M ( 'keyword' )->where ( 'iskey=1' )->order ( 'kid desc' )->select ();
    		//Log::write(M ( 'keyword' )->getLastSql(), 'DEBUG');
    		$this->assign ( 'keyinfo', $keyinfo );
    		$this->display ();
    	}
    }

    protected function _before_insert($data) {
        //判断开始时间和结束时间是否合法
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        if ($data['start_time'] >= $data['end_time']) {
            $this->ajaxReturn(0, L('ad_endtime_less_startime'));
        }

        switch ($data['type']) {
            case 'text':
                $data['content'] = $this->_post('text', 'trim');
                break;
            case 'image':
                $data['content'] = $this->_post('img', 'trim');
                break;
            case 'code':
                $data['content'] = $this->_post('code', 'trim');
                break;
            case 'flash':
                $data['content'] = $this->_post('flash', 'trim');
                break;
            default :
                $this->ajaxReturn(0, L('ad_type_error'));
                break;
        }
        return $data;
    }

    /* public function _before_edit() {
        $id = $this->_get('id', 'intval');
        $board_id = $this->_mod->where(array('id'=>$id))->getField('board_id');
        $board_info = $this->_adboard_mod->field('name,width,height')->where(array('id'=>$board_id))->find();
        $this->assign('board_info', $board_info);
        $this->assign('ad_type_arr', $this->_ad_type); 
    }
    */
    public function edit(){
    	$id = $_GET['id'];
    	$uid= $this->uid;
    	$media = M('media')->where('id = ' . $id  .'  and  shopid = '."'$uid'")->order('id')->find();
    	
    	//dump($media);
    	$this->assign('media', $media);
    	
    	$media_item = M('media_item')->where('media_id=' . $id)->order('id')->select();
    	$media_item_first = $media_item[0];
    	$this->assign('media_item_first', $media_item_first);
    	
    	array_splice($media_item, 0,1);
    	/* foreach ($media_item as $key=>$val) {
    		//Log::write('---' . $key . '--' . $val);
    		foreach ($val as $keyt=>$valt) {
    			//Log::write('---' . $keyt . '--' . $valt);
    		}
    	} */
    	
    	//dump($media_item);
    	$this->assign('media_item_other', $media_item);
    	
    	$this->display();
    	
    }
    public function delete(){
    	$id = $_GET['id'];
    	$uid= $this->uid;
    	$media = M('media')->where('id  = ' .$id  .'  and shopid = '."'$uid'" )->delete();
    	$media_item = M('media_item')->where('media_id=' . $id)->delete();
    	if($media_item){
    		$this->success('删除成功');
    	}else {
    		$this->error();
    	}
    	
    }


    //上传图片
    public function ajax_upload_img() {
        $type = $this->_get('type', 'trim', 'img');
        if (!empty($_FILES[$type]['name'])) {
            $dir = date('ym/d/');
            $result = $this->_upload($_FILES[$type], 'advert/'. $dir );
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