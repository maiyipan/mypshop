<?php
class spreadqrcodeAction extends backendAction {
	
	
	public  function _initialize() {
		parent::_initialize();
		$this->_mod = D('spreadqrcode');
	}

	protected function _before_insert($data) {
		
		vendor('QRCode.phpqrcode');
		$qrapi = new \QRcode();
		$value = $this->_post('url', 'trim');; //二维码内容
		$errorCorrectionLevel = 'L';//容错级别
		$matrixPointSize = 12;//生成图片大小
		$filename = md5(uniqid(mt_rand(), true));
		$name = C('pin_attach_path').'spread/'.$filename;
		$QR =$name .'.png';
		//保存了二维码 路径为 $outfile
		$qrapi->png($value, $QR, $errorCorrectionLevel, $matrixPointSize, 2, true);
		
		//exit();
		$logo = C('pin_attach_path').'sys/' . 'logo.png'; 
		if ($logo !== FALSE) {
			$QR = imagecreatefromstring(file_get_contents($QR));
			$logo = imagecreatefromstring(file_get_contents($logo));
			$QR_width = imagesx($QR);//二维码图片宽度
			$QR_height = imagesy($QR);//二维码图片高度
		
			$logo_width = imagesx($logo);//logo图片宽度
			$logo_height = imagesy($logo);//logo图片高度
			$logo_qr_width = $QR_width / 5;
			$scale = $logo_width/$logo_qr_width;
			$logo_qr_height = $logo_height/$scale;
			$from_width = ($QR_width - $logo_qr_width) / 2;
			$re = imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
					$logo_qr_height, $logo_width, $logo_height);
		}
		//输出图片
		$haveLog = $name  . '_logo' .'.png';
		imagepng($QR,$haveLog);
		$url1 = "http://127.0.0.1/".$name  .'.png';
		$url2 = "http://127.0.0.1/".$haveLog;

		$data['img'] = $filename. '_logo' .'.png';
		return $data;
		
	}
	
	/*
	public function download ($id) {
		
		$data = $this->_mod->where('id = '. $id)->find();
		//dump(F('setting'));
		//dump(C('pin_attach_path_path'));
		$image = C('pin_attach_path').'spread/'.$data['img'];
		dump($image);
		
		$url2 = "http://127.0.0.1/".$image;
		
		echo "<img src='$url2'>";
		exit();
		
		$filename=realpath($image);  //文件名
		$date=date("Ymd-H:i:m");
		$name = $data['name'];
		Header( "Content-type:   application/octet-stream ");
		Header( "Accept-Ranges:   bytes ");
		Header( "Accept-Length: " .filesize($filename));
		header( "Content-Disposition:   attachment;   filename= {$date}.png");
		echo file_get_contents($filename);
		readfile($filename);
	}
	*/
	public function download() {
		
		 $id = $_GET['id'];
		 $data = $this->_mod->where('id = '. $id)->find();
		 $nn = $data['img'];//'8ac0231e6b10b3a56dfbdc1b2f86c2df_logo.png';
		 $image = $logo = C('pin_attach_path').'spread/' . $nn;
    	 $filename=realpath($image);  //文件名
		 $date=date("Ymd-H:i:m");
		 $name = $data['name'] .'_'.$data['remark'];
		 Header( "Content-type:   application/octet-stream "); 
		 Header( "Accept-Ranges:   bytes "); 
		 Header( "Accept-Length: " .filesize($filename));
		 header( "Content-Disposition:   attachment;   filename= {$name}.png"); 
		 echo file_get_contents($filename);
		 readfile($filename); 
	}
	
}