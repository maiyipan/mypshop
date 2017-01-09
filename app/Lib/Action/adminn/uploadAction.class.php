<?php
class uploadAction extends backendAction
{

	public function image() {
		$file_type = empty($_REQUEST['dir']) ? 'image' : trim($_REQUEST['dir']);
		$result = $this->_upload($_FILES['imgFile']);
		if ($result['error']) {
			echo json_encode(array('error'=>1, 'message'=>$result['info']));
		} else {
			echo json_encode(array('error'=>0, 'url'=>'/data/upload/' . $file_type . '/' . $result['info'][0]['savename']));
		}
		exit;
	}
	
	/**
	 * 单个上传图片
	 */
	public function pic(){
            //dump(C('pin_item_bimg.width'));
            //dump(C('pin_item_bimg.height'));
		$file_type = empty($_REQUEST['dir']) ? 'image' : trim($_REQUEST['dir']);
		$result = $this->_upload($_FILES['Filedata'], $file_type,
                        array(
                'width'=>C('pin_item_bimg.width').','.C('pin_item_img.width').','.C('pin_item_simg.width'), 
                'height'=>C('pin_item_bimg.height').','.C('pin_item_img.height').','.C('pin_item_simg.height'),
                'suffix' => '_b,_m,_s',
                //'remove_origin'=>true 
            ));
		if ($result['error']) {
			echo json_encode(array('error'=>1, 'message'=>$result['info']));
		} else {
			echo json_encode(array('error'=>0, 'name'=>$file_type . '/' . $result['info'][0]['savename'] ,'path'=>'/data/upload/' .$file_type . '/' . $result['info'][0]['savename']));
		}
		exit;
	}
	
    protected function _upload_init($upload) {
        $file_type = empty($_REQUEST['dir']) ? 'image' : trim($_REQUEST['dir']);
        $ext_arr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
            'flash' => array('swf', 'flv'),
            'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
            'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
        );
        $allow_exts_conf = explode(',', C('pin_attr_allow_exts')); // 读取配置
        $allow_max = C('pin_attr_allow_size'); // 读取配置
        //和总配置取交集
        $allow_exts = array_intersect($ext_arr[$file_type], $allow_exts_conf);
        $allow_max && $upload->maxSize = $allow_max * 1024;   //文件大小限制
        $allow_exts && $upload->allowExts = $allow_exts;  //文件类型限制
        $upload->savePath =  './data/upload/' . $file_type . '/';
        $upload->saveRule = 'uniqid';
        $upload->autoSub = true;
        $upload->subType = 'date';
        $upload->dateFormat = 'Ym';
        return $upload;
    }
    
}