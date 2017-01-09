<?php
class FullGive{
	
	 var $data = array();
	 
	 public function addFullGive($fullgive, $uid){
// 	 	$this->data[] = $fullgive;
	 	$newfullgive = array();
	 	$newfullgive['name'] = $fullgive['name'];
	 	$newfullgive['good_type'] = $fullgive['good_type'];
	 	$newfullgive['good_value'] = $fullgive['good_value'];
	 	$newfullgive['award_type'] = $fullgive['award_type'];
	 	$newfullgive['award_value'] = $fullgive['award_value'];
	 	$newfullgive['reserve'] = $fullgive['reserve'];
	 	$newfullgive['condition'] = $fullgive['condition'];
	 	$newfullgive['uid'] = $uid;
	 	$this->data[] = $newfullgive;
	 }
	 
	 public function addFullGive2($fullgive, $id, $type, $uid){
	 	if(isset($this->data[$type][$id])){
	 		return;
	 	}
	 	$this->data[$type][$id]['id'] = $id;
	 	$this->data[$type][$id]['name'] = $fullgive['name'];
	 	$this->data[$type][$id]['good_type'] = $fullgive['good_type'];
	 	$this->data[$type][$id]['good_value'] = $fullgive['good_value'];
	 	$this->data[$type][$id]['award_type'] = $fullgive['award_type'];
	 	$this->data[$type][$id]['award_value'] = $fullgive['award_value'];
	 	$this->data[$type][$id]['reserve'] = $fullgive['reserve'];
	 	$this->data[$type][$id]['condition'] = $fullgive['condition'];
	 	$this->data[$type][$id]['uid'] = $uid;
	 }
	 
	 public function add($item_id, $price, $num, $cate_id) {
	 	foreach ($this->data as &$fullgive){
	 		$good_type = $fullgive['good_type'];
	 		$good_value = $fullgive['good_value'];
	 		if($good_type == 1){
	 			$item_ids = explode(',', $good_value);
				if(in_array($item_id, $item_ids)){
					$fullgive['items'][$item_id]['item_id'] = $item_id;
					$fullgive['items'][$item_id]['price'] = $price;
					$fullgive['items'][$item_id]['num'] = $num;
					$fullgive['items'][$item_id]['cate_id'] = $cate_id;
				}
	 		}elseif ($good_type == 2){
	 			if($cate_id == $good_value){
	 				$fullgive['items'][$item_id]['item_id'] = $item_id;
	 				$fullgive['items'][$item_id]['price'] = $price;
	 				$fullgive['items'][$item_id]['num'] = $num;
	 				$fullgive['items'][$item_id]['cate_id'] = $cate_id;
	 			}
	 		}elseif ($good_type ==3){
	 				$fullgive['items'][$item_id]['item_id'] = $item_id;
	 				$fullgive['items'][$item_id]['price'] = $price;
	 				$fullgive['items'][$item_id]['num'] = $num;
	 				$fullgive['items'][$item_id]['cate_id'] = $cate_id;
	 		}
	 	}
	 }
	 
	 public function give() {
	 	$fullgivel = array();
	 	$fullgivef = array();
	 	foreach ($this->data as $fullgive){
	 		$condition = $fullgive['condition'];
	 		$award_type = $fullgive['award_type'];
	 		$award_value = $fullgive['award_value'];
	 		$sumPrice = 0;
	 		if($award_type == 1){
	 			if(!empty($fullgive['items'])){
	 				foreach ($fullgive['items'] as $item){
	 					$sumPrice += $item['num'] * $item['price'];
	 				}
	 				$fullgive['sumPrice'] = $sumPrice;
	 				$cprice = $condition - $sumPrice;
	 				if($sumPrice >= $condition){
	 					$fullgive['tt'] = 1;
	 					$fullgivef[$condition] = $fullgive;
// 	 					return $fullgive;
	 				}else{
	 					$fullgive['tt'] = 2;
	 					$fullgivel[$cprice] = $fullgive;
	 				}
	 			}
	 		}
	 	}
	 	if(!empty($fullgivef)){
	 		krsort($fullgivef);
	 		return reset($fullgivef);
	 	}else{
	 		ksort($fullgivel);
	 		return reset($fullgivel);
	 	}
	 }

	 public function givegife() {
	 	$fullgivel = array();
	 	$fullgiveg = array();
	 	$fullgivef = array();
	 	foreach ($this->data as $fullgive){
	 		$condition = $fullgive['condition'];
	 		$award_type = $fullgive['award_type'];
	 		$award_value = $fullgive['award_value'];
	 		$sumPrice = 0;
	 		if($award_type == 1){
	 			if(!empty($fullgive['items'])){
	 				foreach ($fullgive['items'] as $item){
	 					$sumPrice += $item['num'] * $item['price'];
	 				}
	 				$fullgive['sumPrice'] = $sumPrice;
	 				$cprice = $condition - $sumPrice;
	 				if($sumPrice >= $condition){
	 					$fullgive['tt'] = 1;
	 					$fullgivef[$condition] = $fullgive;
// 	 					return $fullgive;
	 				}
	 			}
	 		}else {
	 			if(!empty($fullgive['items'])){
	 				if($fullgive['reserve'] == 1){//金额
	 					foreach ($fullgive['items'] as $item){
	 						$sumPrice += $item['num'] * $item['price'];
	 					}
	 					if($sumPrice >= $condition){
	 						$fullgive['tt'] = 3;
	 						$fullgivel[] = $fullgive;
	 					}
	 				}elseif($fullgive['reserve'] == 2){//数量
	 					foreach ($fullgive['items'] as $item){
	 						$counts += $item['num'];
	 					}
	 					if($counts >= $condition){
	 						$fullgive['tt'] = 4;
	 						$fullgiveg[] = $fullgive;
	 					}
	 				}
	 			}
	 		}
	 	}
	 	if(!empty($fullgivef)){
	 		krsort($fullgivef);
	 		return reset($fullgivef);
	 	}else{
	 		if($fullgivel){
	 			ksort($fullgivel);
	 			return reset($fullgivel);
	 		}else {
	 			return $fullgiveg[0];
	 		}
	 	}
	 }
	 
}