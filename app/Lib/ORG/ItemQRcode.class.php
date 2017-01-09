<?php
class ItemQRcode{
	public function qrcode($url,$id,$table){
		
		$item_qrcode=M($table);
		vendor('QRCode.phpqrcode');
		$qrapi = new \QRcode();
		//     	$value = $this->_post('url', 'trim');; //二维码内容
// 		$value = C('pin_baseurl')."/index.php/item/details/id/".$id;
		$value = C('pin_baseurl').$url;
		$errorCorrectionLevel = 'L';//容错级别
		$matrixPointSize = 12;//生成图片大小
		$filename = md5(uniqid(mt_rand(), true));
		$name = C('pin_attach_path').'item/qrcode/'.$filename;
		$QR =$name .'.png';
		//保存了二维码 路径为 $outfile
		$qrapi->png($value, $QR, $errorCorrectionLevel, $matrixPointSize, 2, true);
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
		$image['qrcode']= 'item/qrcode/'.$filename.'.png';
		$image['id']=$id;
		$image['url']=$value;		 
		$item_qrcode->where("id=".$id)->save($image);
		return $image;
	}
	
	
	public function qrcodeNoSave($url){
		vendor('QRCode.phpqrcode');
		$qrapi = new \QRcode();
		//     	$value = $this->_post('url', 'trim');; //二维码内容
		// 		$value = C('pin_baseurl')."/index.php/item/details/id/".$id;
// 		$value = C('pin_baseurl').$url;
		$value = $url;
		$errorCorrectionLevel = 'L';//容错级别
		$matrixPointSize = 12;//生成图片大小
		$filename = md5(uniqid(mt_rand(), true));
		$name = C('pin_attach_path').'tag/qrcode/'.$filename;
		$QR =$name .'.png';
		//保存了二维码 路径为 $outfile
		$qrapi->png($value, $QR, $errorCorrectionLevel, $matrixPointSize, 2, true);
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
		return '/'. $haveLog;
	}
}