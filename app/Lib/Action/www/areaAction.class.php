<?php
class areaAction extends frontendAction {

    public function list_area() {
    	$list = M('area')->order('pid asc')->select();
    	$temp = array();
    	foreach ($list as $key => $val) {
    		if($temp[$val['pid']]){
    			$children = $temp[$val['pid']];
    		}else{
    			$children = array();
    		}
    		$children[] = $val;
    		$temp[$val['pid']] = $children;
    	}
    	$data = array();
    	foreach ($list as &$val) {
    		if($val['pid'] == 0){
    			$children1 = $temp[$val['id']];
    			foreach($children1 as &$childrenVal){
    				$childrenVal['child'] = $temp[$childrenVal['id']];
    			}
    			$val['child'] = $children1;
    			$data[] = $val;
    		}
    		
    	}
    	exit(json_encode($data));
    }
    
    public function list_shop_area() {
    	$uid = $this->shopId();;
    	echo $uid;
    	$list = M('')->query("SELECT a.* FROM weixin_area a, weixin_shop_distribute b WHERE a.`id` = b.`area_id` AND uid='$uid'");
    	$temp = array();
    	foreach ($list as $key => $val) {
    		if($temp[$val['pid']]){
    			$children = $temp[$val['pid']];
    		}else{
    			$children = array();
    		}
    		$children[] = $val;
    		$temp[$val['pid']] = $children;
    	}
    	$data = array();
    	foreach ($list as &$val) {
    		if($val['pid'] == 0){
    			$children1 = $temp[$val['id']];
    			foreach($children1 as &$childrenVal){
    				$childrenVal['child'] = $temp[$childrenVal['id']];
    			}
    			$val['child'] = $children1;
    			$data[] = $val;
    		}
    
    	}
    	exit(json_encode($data));
    }
    
    public function ajax_list(){
    	$uid = $this->shopId();;
    	//Log::write($uid, 'DEBUG');
    	$list =  D ( 'shop_distribute' )->where(array('uid'=>$uid))->select();;
    	$temp = array();
    	foreach ($list as $key => $val) {
    		if($temp[$val['pid']]){
    			$children = $temp[$val['pid']];
    		}else{
    			$children = array();
    		}
    		$children[] = $val;
    		$temp[$val['pid']] = $children;
    	}
    	$data = array();
    	foreach ($list as &$val) {
    		if($val['pid'] == 0){
    			$children1 = $temp[$val['id']];
    			foreach($children1 as &$childrenVal){
    				$childrenVal['child'] = $temp[$childrenVal['id']];
    			}
    			$val['child'] = $children1;
    			$data[] = $val;
    		}
    
    	}
//     	dump($data);
    	exit(json_encode($data));
    }

}