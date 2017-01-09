<?php
class codeAction extends backendAction {
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'code' );
	}
	public function _before_index() {
		$big_menu = array (
				'title' => '新增',
				'iframe' => U ( 'code/add' ),
				'id' => 'add',
				'width' => '520',
				'height' => '210' 
		);
		$this->assign ( 'big_menu', $big_menu );
	}
	// 创建二维码
	public function code() {
		if (false === $shopconf = F ( 'shop' )) {
			$conf = D ( 'shop' )->shop_cache ();
		}
		C ( F ( 'shop' ) ); // 获取缓存
		
		$num = $_POST ['num'] * 86400;
		
		// 临时
		if ($_POST ['sex'] == 0) {
			if (isset ( $num ) && $num !== null) {
				switch ($num) {
					case 86400 :
						$qrcode = '{  "expire_seconds": 86400,"action_name": "QR_SCENE","action_info": {"scene": {"scene_id": 10000}}}';
						break;
					case 172800 :
						$qrcode = '{  "expire_seconds": 172800,"action_name": "QR_SCENE","action_info": {"scene": {"scene_id": 10000}}}';
						break;
					case 259200 :
						$qrcode = '{  "expire_seconds": 259200,"action_name": "QR_SCENE","action_info": {"scene": {"scene_id": 10000}}}';
						break;
					case 345600 :
						$qrcode = '{  "expire_seconds": 345600,"action_name": "QR_SCENE","action_info": {"scene": {"scene_id": 10000}}}';
						break;
					case 432000 :
						$qrcode = '{  "expire_seconds": 432000,"action_name": "QR_SCENE","action_info": {"scene": {"scene_id": 10000}}}';
						break;
					case 518400 :
						$qrcode = '{  "expire_seconds": 518400,"action_name": "QR_SCENE","action_info": {"scene": {"scene_id": 10000}}}';
						break;
					case 604800 :
						$qrcode = '{  "expire_seconds": 604800,"action_name": "QR_SCENE","action_info": {"scene": {"scene_id": 10000}}}';
						break;
					default :
						break;
				}
			} else {
				echo "输入有误 ！";
			}
		} elseif ($_POST ['sex'] == 1) { // 永久
			$qrcode = '{	"action_name":"QR_LIMIT_SCENE","action_info": {"scene": {"scene_id": 1000}}}';
		}
		$uid = $this->uid;
		$weixinInter = new WeixinInterface ( $uid );
		$jiaxian_token = $weixinInter->findAccess_token ();
		// $jiaxian_token=findAccess_token('test');//获取token
		$url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $jiaxian_token;
		$result = $this->https_post ( $url, $qrcode );
		$ticket = $result ['ticket'];
		$this->codedownload ( $ticket ); // 获取二维码
	}
	// 解析URL整合qrcode
	public function https_post($url, $data) {
		$curl = curl_init ();
		curl_setopt ( $curl, CURLOPT_URL, $url );
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, 1 );
		curl_setopt ( $curl, CURLOPT_USERAGENT, $_SERVER ['HTTP_USER_AGENT'] );
		curl_setopt ( $curl, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $curl, CURLOPT_AUTOREFERER, 1 );
		curl_setopt ( $curl, CURLOPT_POST, 1 );
		curl_setopt ( $curl, CURLOPT_POSTFIELDS, $data );
		curl_setopt ( $curl, CURLOPT_TIMEOUT, 30 );
		curl_setopt ( $curl, CURLOPT_HEADER, 0 );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		$tmpInfo = curl_exec ( $curl );
		if (curl_errno ( $curl )) {
			echo 'Errno' . curl_error ( $curl );
		}
		curl_close ( $curl );
		// 取出openid
		$data = json_decode ( $tmpInfo, true );
		
		return $data;
	}
	// 下载二维码
	public function codedownload($ticket) {
		$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode ( $ticket );
		$ticketdown = urlencode ( $ticket );
		$imageInfo = $this->downloadcode ( $url );
		$link = __FILE__;
		$filename = C('pin_attach_path') . "/code/" . date ( 'ymdhis' ) . ".jpg";
		$local_file = fopen ( $filename, 'w' );
		if (false !== $local_file) {
			if (false !== fwrite ( $local_file, $imageInfo ["body"] )) {
				fclose ( $local_file );
			}
		} 
		/* dump($imageInfo["body"]);
		$re = $this->_upload($imageInfo["body"], 'code/');
		dump($re);
		exit(); */
		$m = M ( 'code' );
		if ($_POST ['sex'] == 1) {
			$name = "永久";
		} elseif ($_POST ['sex'] == 0) {
			$name = "临时";
		}
		$data ['type'] = $name;
		$data ['ticket'] = $ticketdown;
		$time = date ( "Y-m-d H:i:s" );
		$data ['time'] = $time;
		$data ['image'] = 'code/'.date ( 'ymdhis' );
		$data ['shopid'] = $this->uid;
		$data ['remark'] = $_POST['remark'];
		$idnum = $m->add ( $data );
		if ($idnum > 0) {
			$this->success ( '数据添加成功', U('index') );
		} else {
			$this->error ( "数据添加失败" );
		}
	}
	// 解析
	public function downloadcode($url) {
		$ch = curl_init ( $url );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_NOBODY, 0 );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$packeData = curl_exec ( $ch );
		$httpinfo = curl_getinfo ( $ch );
		curl_close ( $ch );
		return array_merge ( array (
				'body' => $packeData 
		), array (
				'header' => $httpinfo 
		) );
	}
	
	public function pre($image){
// 		$data = explode('/',$image);
// 		$data['qrcode'] =$data[1].'.jpg';
		$data['qrcode'] =$image.'.jpg';
		$this->assign('data',$data);
		if (IS_AJAX) {
			$response = $this->fetch();
			$this->ajaxReturn(1, '', $response);
		} else {
			$this->display();
		}
	}
	// 删除记录
	public function down($image) {
		$image = $logo = C ( 'pin_attcch_path' ) . 'data/upload/' . $image;
		$filename = realpath ( $image ); // 文件名
		$date = date ( "Ymd-H:i:m" );
		Header ( "Content-type:   application/octet-stream " );
		Header ( "Accept-Ranges:   bytes " );
		Header ( "Accept-Length: " . filesize ( $filename ) );
		header ( "Content-Disposition:   attachment;   filename= {$date}.jpg" );
		echo file_get_contents ( $filename );
		readfile ( $filename );
	}
}