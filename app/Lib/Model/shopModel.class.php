<?php

/**
 * 店铺管理model
 * @author JimYan
 * */
class shopModel extends RelationModel {
	/**
	 * 检测店铺书否存在
	 * @param $uid 店铺uid
	 * @return true 存在  false 不存在
	 * */
	public function shop_exists($uid) {
		$where  =  array('uid'=>$uid);
		$result = $this->where($where)->count('uid');
		if ($result) {
			return true;
		} else {
			return false;
		}
	}
	//修改过
	public function shop_cache(){
		$shop = array();
		$res = $this->getField('uid,name,pid,address,erpnum,appid,appsecret,wxpayone,wxpaytwo,path');
		foreach ($res as $key=>$data) {
			foreach ($data as $kk=>$vv) {
				$shop['spconf_'.$kk.'_'.$key] =  $vv; 
			}
		}
		
		F('shop', $shop);
		return $shop;
	}
	/**
	 * 后台有更新则删除缓存
	 */
	protected function _before_write($data, $options) {
		F('shop', NULL);
	}
	
	/**
	 * 判断是否为独立运营 
	 * 是否没有有子 true 
	 * */
	public function isself($uid) {
		$where  =  array('uid'=>$uid);
		$result = $this->where($where)->find();
		if ($result['isself'] == 0) { //独立运营
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 上级独立运营的uid
	 * */
	public  function  getUidForP($uid){
		$where  =  array('uid'=>$uid);
		$result = $this->where($where)->find();
		//dump($this->getLastSql());
// 		 if ($this->isself($result['uid'])) {
// 			return $result['uid'] ;
// 		} else {
// 			//先根据父pid查到uid
// 			$puid = $this->getUidByPid($result['pid']);
// 			return $this->getUidForP($puid);
// 		} 
		 if ($this->isself($result['uid'])) {
			return $result['uid'] ;
		} else {
			//先根据父pid查到uid
			$puid = $this->getUidByPid($result['pid']);
			return $this->getUidForP($puid);
		} 
	}
	
	
	/**
	 * 获取uid对应门店所有的子门店
	 * */
	public function  getChildsById ($id) {
		$list [] = $this->getChilds($id);
		foreach ($list as $key=>$val) {
			foreach ($val as $k=>$v){
				if ($v['isself'] == 0) { //独立运营
					$list[] = $this->getChilds($v['id']);
				}
			}
		}
		return $list;
	}
	
	public function getChilds($id) {
		$where = array(
				'pid'=>$id
		);
		return M('shop')->where($where)->select();
	}
	
	/**
	 * 更加uid查询 店铺
	 * */
	public function getShopByUid ($uid) {
		$where = array('uid'=>$uid);
		return $this->where($where)->find();
	}
	
	
	/**
	* 生成spid
	*
	* @param int $pid 父级ID
	*/
	public function get_spid($pid) {
		if (!$pid) {
			return 0;
		}
		$pspid = $this->where(array('id'=>$pid))->getField('spid');
		if ($pspid) {
			$spid = $pspid . $pid . '|';
		} else {
			$spid = $pid . '|';
		}
		return $spid;
	}
	
	
	/**
	 * 获取分类下面的所有子分类的ID集合
	 *
	 * @param int $id
	 * @param bool $with_self
	 * @return array $array
	 */
	public function get_child_ids($id, $with_self=false) {
		$spid = $this->where(array('id'=>$id))->getField('spid');
		$spid = $spid ? $spid .= $id .'|' : $id .'|';
		$id_arr = $this->field('id')->where(array('spid'=>array('like', $spid.'%')))->select();
		$array = array();
		foreach ($id_arr as $val) {
			$array[] = $val['id'];
		}
		$with_self && $array[] = $id;
		return $array;
	}

	public function getShopNmaeByUid($uid) {
		$where['uid'] = $uid;
		return $this->where($where)->getField('name');
	}
	
	public function getUidByPid($pid) {
		return $this->where('id = ' . $pid) ->getField("uid");
	}

	/**
	 * 查询uid对应的id 
	 * */
	public function getIdByUid($uid) {
		$where['uid'] = $uid;
		return $this->where($where)->getField('id');
	}
	
	/**
	 * 是否为父
	 * true 是
	 * false 否
	 * */
	public function isPre($id) {
		$map['pid'] = $id;
		$count  = $this->where($map)->count('id');
		if ($count == 0 ) { //不是
			return false;
		} else {
			return true; //是
		}
	}
	
	/**
	 * 获取一个默认的店铺
	 * */
	public function getDefaultShop($id) {
		$map['pid'] = $id;
		$data = $this->where($map)->find();
		return $data;
	}
}