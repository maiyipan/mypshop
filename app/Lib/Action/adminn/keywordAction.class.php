<?php
class keywordAction extends backendAction {
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'keyword' );
	}
	
	
	public function _search() {
		$map = array();
		$map['iskey'] = 1;
		return $map;
	}
	
	public function _before_index(){
		
		//查询素材列表
		$media = M('media')->select();
		$this->assign('media', $media);
		
		//关注回复
		$follow = M('keyword')->where('isfollow = 1')->find();
		$this->assign('follow', $follow);
		
		//默认回复
		$mess = M('keyword')->where('ismess = 1')->find();
		$this->assign('mess', $mess);
		
		$this->list_relation = true;
		/* $keyword = M('keyword')->where('iskey = 1')->find();
		$this->assign('list', $keyword); */
		
		//$this->display();
	}
	
	public function addkeyword() // 关键词
{
		$op = $_GET ['op'];
		if (! empty ( $op )) {
			$keyword = array ();
			$keyword ['kename'] = trim ( $_POST ['kename'] );
			$keyword ['kyword'] = trim ( $_POST ['keword'] );
			$keyword ['type'] = intval ( $_POST ['ketype'] );
			$linkinfo = $_POST ['linkinfo'];
			$titles = $_POST ['titles'];
			$imageinfo = $_POST ['imageinfo'];
			if ($op == 'add') {
				if ($keyword ['type'] == 1) {
					$keyword ['kecontent'] = trim ( $_POST ['kecontent'] );
					$keyword ['linkinfo'] = '';
					$keyword ['titles'] = '';
					$keyword ['imageinfo'] = '';
				} else {
					$keyword ['kecontent'] = '';
					$keyword ['linkinfo'] = serialize ( $linkinfo );
					$keyword ['titles'] = serialize ( $titles );
					$keyword ['imageinfo'] = serialize ( $imageinfo );
				}
				$keyword ['iskey'] = 1;
				M ( 'keyword' )->data ( $keyword )->add ();
			}
			
			if ($op == 'update') {
				$kid = trim ( $_POST ['kid'] );
				if ($keyword ['type'] == 1) {
					$keyword ['kecontent'] = trim ( $_POST ['kecontent'] );
				} else {
					$keyword ['linkinfo'] = serialize ( $linkinfo );
					$keyword ['titles'] = serialize ( $titles );
					$keyword ['imageinfo'] = serialize ( $imageinfo );
				}
				$where = array (
						'kid' => $kid 
				);
				M ( 'keyword' )->where ( $where )->data ( $keyword )->save ();
			}
			
			if ($op == 'del') {
				$kid = trim ( $_POST ['kid'] );
				M ( 'keyword' )->where ( array (
						'kid' => $kid 
				) )->delete ();
			}
		} else {
			/* $keyinfo = M ( 'keyword' )->where ( 'iskey=1' )->order ( 'kid desc' )->select ();
			$this->assign ( 'keyinfo', $keyinfo ); */
			$this->display ();
		}
	}
	public function addkeyword_ajax() {
		$kid = $_GET ['kid'];
		$keyinfo = M ( 'keyword' )->where ( "iskey=1 AND kid=" . $kid )->select ();
		// $keyinfo = $db->getAll('SELECT * FROM '.tname('keyword').' WHERE iskey=1 AND kid='.$kid);
		
		foreach ( $keyinfo as $k => $v ) {
			$titles = unserialize ( $v ['titles'] );
			$imageinfo = unserialize ( $v ['imageinfo'] );
			$linkinfo = unserialize ( $v ['linkinfo'] );
			
			$keyinfo [$k] ['titles'] = $titles;
			$keyinfo [$k] ['imageinfo'] = $imageinfo;
			$keyinfo [$k] ['linkinfo'] = $linkinfo;
		}
		
		echo json_encode ( $keyinfo );
	}
	public function allimages() {
		//dump("test");
		$imagesinfo = M ( 'images' )->order ( 'iid desc' )->select ();
		
		echo json_encode ( $imagesinfo );
	}
	public function ajaxupload() { // 上传图片
		if ($_POST ['sub'] == 'submit') {
			if ($_FILES ['image'] ['error'] ['image'] == 0) {
				$date_dir = date ( 'ym/d/' ); // 上传目录
				if ($_FILES ['image']) {
					$result = $this->_upload ( $_FILES ['image'], 'keyword/' . $date_dir );
					if ($result ['error']) {
						$item_url = $result ['info'];
						// $this->error($result['info']);
					} else {
						$item_url = "/data/upload/keyword/" . $date_dir . $result ['info'] [0] ['savename'];
						// foreach( $result['info'] as $key=>$val ){
						// $item_imgs[] = array(
						// 'url' => $date_dir . $val['savename'],
						// );
						// }
					}
				}
			}
			$image = array (
					'imgurl' => $item_url 
			);
			M ( 'images' )->data ( $image )->add ();
			echo json_encode ( $image );
			
		}
	}
	public function ajaxkeyword() {
		$keyinfo = M ( 'keyword' )->where ( ' iskey=1 AND type=2' )->select ();
		
		foreach ( $keyinfo as $k => $v ) {
			$titles = unserialize ( $v ['titles'] );
			$imageinfo = unserialize ( $v ['imageinfo'] );
			$linkinfo = unserialize ( $v ['linkinfo'] );
			$keyinfo [$k] ['titles'] = $titles;
			$keyinfo [$k] ['imageinfo'] = $imageinfo;
			$keyinfo [$k] ['linkinfo'] = $linkinfo;
		}
		echo json_encode ( $keyinfo );
		exit ();
	}
	
	/* 修改文本表单内容* */
	public function ajax_form() {
		$data ['kid'] = $this->_post ( 'id', 'intval' );
		$kename = $this->_post ( 'kename', 'trim' );
		$data ['kecontent'] = $this->_post ( 'kecontent', 'trim' );
		$kyword = $this->_post ( 'kyword', 'trim' );
		
		if (! empty ( $kename )) {
			$data ['kename'] = $kename;
		}
		if (! empty ( $kyword )) {
			
			$data ['kyword'] = $kyword;
		}
		$res = M ( 'keyword' )->save ( $data );
		if ($res !== false) {
			$data = array (
					'res' => 'succ',
					'msg' => '修改成功' 
			);
		} else {
			$data = array (
					'res' => 'error',
					'msg' => '修改失败' 
			);
		}
		
		echo json_encode ( $data );
		exit ();
	}
	
	/**
	 * **********
	 *
	 * 消息自动回复
	 * *************
	 */
	public function addmess() {
		$op = $_GET ['op'];
		if ($op == 'add') {
			// $keyinfo = $db->getAll('SELECT * FROM '.tname('keyword').' WHERE ismess=1');
			$keyinfo = M ( 'keyword' )->where ( 'ismess=1' )->select ();
			$keyword = array ();
			// $keyword['kename'] = trim($_POST['kename']);
			// $keyword['kyword'] = trim($_POST['keword']);
			$keyword ['type'] = intval ( $_POST ['ketype'] );
			
			$linkinfo = $_POST ['linkinfo'];
			$titles = $_POST ['titles'];
			$imageinfo = $_POST ['imageinfo'];
			if (empty ( $keyinfo )) {
				if ($keyword ['type'] == 1) {
					$keyword ['kecontent'] = trim ( $_POST ['kecontent'] );
				} else {
					$keyword ['linkinfo'] = serialize ( $linkinfo );
					$keyword ['titles'] = serialize ( $titles );
					$keyword ['imageinfo'] = serialize ( $imageinfo );
				}
				$keyword ['ismess'] = 1;
				M ( 'keyword' )->data ( $keyword )->add ();
				// $db->inserttable("keyword",$keyword);
			} else {
				if ($keyword ['type'] == 1) {
					$keyword ['linkinfo'] = NULL;
					$keyword ['titles'] = NULL;
					$keyword ['imageinfo'] = NULL;
					$keyword ['kecontent'] = trim ( $_POST ['kecontent'] );
				} else {
					$keyword ['kecontent'] = NULL;
					$keyword ['linkinfo'] = serialize ( $linkinfo );
					$keyword ['titles'] = serialize ( $titles );
					$keyword ['imageinfo'] = serialize ( $imageinfo );
				}
				$where = array (
						'ismess' => 1 
				);
				M ( 'keyword' )->data ( $keyword )->where ( $where )->save ();
				// $db->updatetable("keyword",$keyword,$where);
			}
		}
		$this->display ();
	}
	public function showmess() {
		// $keyinfo = $db->getAll('SELECT * FROM '.tname('keyword').' WHERE ismess=1');
		$keyinfo = M ( 'keyword' )->where ( 'ismess=1' )->select ();
		if (! empty ( $keyinfo )) {
			foreach ( $keyinfo as $k => $v ) {
				$titles = unserialize ( $v ['titles'] );
				$imageinfo = unserialize ( $v ['imageinfo'] );
				$linkinfo = unserialize ( $v ['linkinfo'] );
				$keyinfo [$k] ['titles'] = $titles;
				$keyinfo [$k] ['imageinfo'] = $imageinfo;
				$keyinfo [$k] ['linkinfo'] = $linkinfo;
			}
			echo json_encode ( $keyinfo );
		} else {
			echo json_encode ( $keyinfo );
		}
	}
	public function addfollow() {
		$op = $_GET ['op'];
		if ($op == 'add') {
			$keyinfo = M ( 'keyword' )->where ( 'isfollow=1' )->select ();
			
			$keyword = array ();
			// $keyword['kename'] = trim($_POST['kename']);
			// $keyword['kyword'] = trim($_POST['keword']);
			$keyword ['type'] = intval ( $_POST ['ketype'] );
			$linkinfo = $_POST ['linkinfo'];
			$titles = $_POST ['titles'];
			$imageinfo = $_POST ['imageinfo'];
			if (empty ( $keyinfo )) {
				if ($keyword ['type'] == 1) {
					$keyword ['kecontent'] = trim ( $_POST ['kecontent'] );
				} else {
					$keyword ['linkinfo'] = serialize ( $linkinfo );
					$keyword ['titles'] = serialize ( $titles );
					$keyword ['imageinfo'] = serialize ( $imageinfo );
				}
				$keyword ['isfollow'] = 1;
				
				M ( 'keyword' )->data ( $keyword )->add ();
			} else {
				if ($keyword ['type'] == 1) {
					$keyword ['linkinfo'] = NULL;
					$keyword ['titles'] = NULL;
					$keyword ['imageinfo'] = NULL;
					$keyword ['kecontent'] = trim ( $_POST ['kecontent'] );
				} else {
					$keyword ['kecontent'] = NULL;
					$keyword ['linkinfo'] = serialize ( $linkinfo );
					$keyword ['titles'] = serialize ( $titles );
					$keyword ['imageinfo'] = serialize ( $imageinfo );
				}
				$where = array (
						'isfollow' => 1 
				);
				M ( 'keyword' )->data ( $keyword )->where ( $where )->save ();
			}
		}
		$this->display ();
	}
	
	
	public function showfollow() {
		// $keyinfo = $db->getAll('SELECT * FROM '.tname('keyword').' WHERE isfollow=1');
		$keyinfo = M ( 'keyword' )->where ( 'isfollow=1' )->select ();
		if (! empty ( $keyinfo )) {
			foreach ( $keyinfo as $k => $v ) {
				$titles = unserialize ( $v ['titles'] );
				$imageinfo = unserialize ( $v ['imageinfo'] );
				$linkinfo = unserialize ( $v ['linkinfo'] );
				
				$keyinfo [$k] ['titles'] = $titles;
				$keyinfo [$k] ['imageinfo'] = $imageinfo;
				$keyinfo [$k] ['linkinfo'] = $linkinfo;
			}
			echo json_encode ( $keyinfo );
		} else {
			echo json_encode ( $keyinfo );
		}
	}
	public function delimg() {
		if (isset ( $_POST ['iid'] )) {
			$url = M ( 'images' )->field ( 'imgurl' )->find ( $_POST ['iid'] );
			M ( 'images' )->delete ( $_POST ['iid'] );
			unlink ( $url ['imgurl'] );
		}
	}
	
	
	public function addmessreply(){
		$keyword = M('keyword');
		$id = $_POST['id'];
		$media_id = $_POST ['media_id'];
	
		$data['media_id'] = $media_id;
	
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
		//dump($id. '--' .$media_id);
	
	}
}