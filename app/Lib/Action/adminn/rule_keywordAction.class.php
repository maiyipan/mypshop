<?php
class rule_keywordAction extends backendAction {
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'rule_keyword' );
	}
	
	public function _search() {
		$map = array();
		$map['iskey'] = 1;
		$map ['uid'] =  array('in', $this->getUidList());
		return $map;
	}
	
	public function _before_index(){
		
		//查询素材列表
		//$uid  = $this->uid;
		$uid = $this->shopId();
		$media = M('media')->where('shopid = '."'$uid'") ->select();
		$this->assign('media', $media);
		
		//关注回复
		
		$where['uid'] = $uid;
		$where['isfollow'] = 1;
		$follow = M('keyword')->where($where)->find();
		$this->assign('follow', $follow);
		
		//默认回复
		unset($where['isfollow']);
		$where['ismess'] = 1;
		$mess = M('keyword')->where($where)->find();
		$this->assign('mess', $mess);
		$this->list_relation = true;
	}
	
	
	
	public function addmessreply(){
		$keyword = M('keyword');
		$id = $_POST['id'];
		$media_id = $_POST ['media_id'];
		$data['media_id'] = $media_id;
		$data['uid'] = $this->shopId();
		if ($id == '') { //增加
			//Log::write('keyword add');
			$data['ismess'] = 1;
			$keyword->data($data)->add();
		} else { //update
			//Log::write('keyword update');
			$data['kid'] = $id;
			$keyword->data($data)->save();
			//Log::write('keyword sql' . $keyword->getLastSql(), 'DEBUG');
		}
		$this->redirect('index');
	}
	
	/**
	 * 添加关注回复
	 * */
	public function addfollreply(){
		$keyword = M('keyword');
		$id = $_POST['id'];
		$media_id = $_POST ['media_id'];
	
		$data['media_id'] = $media_id;
		$data['uid'] = $this->shopId();
		if ($id == '') { //增加
			//Log::write('keyword add');
			$data['isfollow'] = 1;
			$keyword->data($data)->add();
		} else { //update
			//Log::write('keyword update');
			$data['kid'] = $id;
			$keyword->data($data)->save();
			//Log::write('keyword sql' . $keyword->getLastSql(), 'DEBUG');
		}
		$this->redirect('index');
	
	}
	
	/**
	 * 添加规则
	 * */
	public function addrule() {
		if (IS_POST) {
			$name = $_POST['rule_name'];
			$keywords = $_POST['keywords'];
			$media_id = $_POST['media_id'];
			
			$rule_data['name'] = $name;
			$rule_data['media_id'] = $media_id;
			
			
			$uid = $this->shopId();
			$rule_data['uid'] = $uid;
			$rule_keyword_id = M('rule_keyword')->data($rule_data)->add();
			
			$keyword_array = explode(", ",$keywords);
			foreach ($keyword_array as $val) {
				$keyword_list[] = array(
						'kyword'=>$val,
						'media_id'=>$media_id,
						'rule_keyword_id' => $rule_keyword_id,
						'iskey'=>'1',
						'uid'=>$uid
				);
			}
			M('keyword')->addAll($keyword_list);
			$this->redirect('index');
		} else {
			$media = M('media')->where('shopid = ' . "'$this->uid'")->select();
			$this->assign('media', $media);
			$this->display();
		}
		
	}
	
	/**
	 * 
	 * */
	public function  _before_edit(){
		$id = $_GET['id'];
		$keyword_list = M('keyword')->field('kid,kyword')->where('rule_keyword_id = ' . $id)->select();
		//dump($keyword_list);
		$kwordStr;
		$kid;
		$s = end($keyword_list);
		$endVal = $s['kid'];
		
		//dump($endVal['kid']);
		foreach ($keyword_list as  $val) {
			//dump($val['kyword']);
			$kwordStr = $kwordStr . $val['kyword'];
			$kid = $kid. $val['kid'];
			if ($endVal != $val['kid']) {
				$kid= $kid.',';
				$kwordStr = $kwordStr .', ';
			}
		}
		//dump($kwordStr);
		$this->assign('kid',$kid);
		$this->assign('kwordStr',$kwordStr);
		$media = M('media')->where('shopid=' . "'$this->uid'") ->select();
		$this->assign('media', $media);
	} 
	
	public function eidt(){
		if (IS_POST) {
			$id = $_POST['id'];
			$name = $_POST['rule_name'];
			$keywords = $_POST['keywords'];
			$media_id = $_POST['media_id'];
			
			$rule_data['id'] = $id;
			$rule_data['name'] = $name;
			$rule_data['media_id'] = $media_id;
			
			M('rule_keyword')->data($rule_data)->save();
			
			//先删除一次。然后再重新添加
			$kid = $_POST['kid'];
			M('keyword') -> where('kid in('. $kid . ')')->delete();
				
			$keyword_array = explode(", ",$keywords);
			foreach ($keyword_array as $val) {
				$keyword_list[] = array(
						'kyword'=>$val,
						'media_id'=>$media_id,
						'rule_keyword_id' => $id,
						'iskey'=>'1'
				);
			}
			M('keyword')->addAll($keyword_list);
			$this->redirect('index');
		}
	}
	
	
}