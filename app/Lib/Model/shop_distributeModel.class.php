<?php
class shop_distributeModel extends RelationModel {
    
	public function queryList($uid,$pager){
		$prefix = C(DB_PREFIX);
		$m = M('shop_distribute');
		$sql = "select c.*,a1.title province,a2.title city,a3.title town
	 	        from $prefix"."shop_distribute c ,".$prefix."area a1 ,".$prefix."area a2,".$prefix."area a3
	 	        where a1.id=c.areaId1 and a2.id=c.areaId2 and a3.id=c.areaId3 ";
		$sql.=" and c.uid = '$uid' ";
		$sql.=" order by areaId1 asc ";
		$sql.=" limit $pager->firstRow , $pager->listRows";
		return $m->query($sql);
	}
	
	public function name_exists($name, $pid, $id=0) {
		$where = "name='" . $name . "' AND pid='" . $pid . "' AND id<>'" . $id . "'";
		$result = $this->where($where)->count('id');
		if ($result) {
			return true;
		} else {
			return false;
		}
	}
	
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
	
}