<?php
class areaAction extends backendAction {

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

}