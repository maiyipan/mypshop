<?php

class tagAction extends backendAction
{

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('tag');
    }

    public function _before_index() {
        $big_menu = array(
            'title' => L('添加标签'),
            'iframe' => U('tag/add'),
            'id' => 'add',
            'width' => '400',
            'height' => '50'
        );
        $this->assign('big_menu', $big_menu);

        $p = $this->_get('p','intval',1);
        $this->assign('p',$p);
    }

    protected function _search() {
        $map = array();
        ($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%'.$keyword.'%');
        $this->assign('search', array(
            'keyword' => $keyword,
        ));
       $map ['uid'] =  array('in', $this->getUidList());// $this->shopId();        return $map;
       return $map;
    }

    public function ajax_check_name() {
        $name = $this->_get('name', 'trim');
        $id = $this->_get('id', 'intval');
        if (D('tag')->name_exists($name, $id)) {
            $this->ajaxReturn(0, L('标签已存在'));
        } else {
            $this->ajaxReturn(1);
        }
    }
    
    /**
     * 预览
     * */
    public function pre() {
    	$id = $this->_get('id');
    	$data=$this->item_qrcode($id);
    	$this->assign('data',$data);
    	if (IS_AJAX) {
    		$response = $this->fetch();
    		$this->ajaxReturn(1, '', $response);
    	} else {
    		$this->display();
    	}
    }
    
    public function item_qrcode($id){
    	$tab=M('tag');
    	$data=$tab->where('id='.$id)->find();
    	//$url  = U('index/doSearch', array('keyword'=>$data['name']));
    	$url = C('pin_baseurl'). '/index/doSearch/keyword/'. $data['name'] . '/sid/'. $data['uid'];
    	$qrcode=new ItemQRcode($url,$id,$table);
    	$image=$qrcode->qrcodeNoSave($url);
    	$data = array(
    			'imgurl'=>$image,
    			 'url' =>$url
    	);
    	return $data;
    }
    
    public function _before_insert($data) {
    	$data['uid'] = $this->shopId();
    	return $data;
    }
}