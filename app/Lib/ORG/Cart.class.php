<?php
class Cart{
	
	
	var $cartKey;
	public function __construct($shopid) {
		if(!isset($_SESSION['cart_' . $shopid])){
			$_SESSION['cart_' . $shopid] = array();
		}
	}
	
	public function setCartKey($shopid) {
		$this->cartKey = 'cart_' . $shopid;
	}
 

	/*
	添加商品
	param int $id 商品主键
		  string $name 商品名称
		  float $price 商品价格
		  int $num 购物数量
	*/
	public  function addItem($id,$name,$price,$num,$img, $goodsId , 
									$barcodeid,$oldprice,$size,$types = 0,$mbdscnt = 0,$shopId ,$limit_buy,$cate_id) {
		//如果该商品已存在则直接加其数量
		Log::write('cart_key>>>' . $this->cartKey);
		if (isset($_SESSION[$this->cartKey][$id])) {
			$havenum = $_SESSION[$this->cartKey][$id]['num'];
			if ($limit_buy != 0 && $havenum + $num >= $limit_buy) {
				$_SESSION[$this->cartKey][$id]['num'] = $limit_buy;
				return 2;
			} else {
				$this->incNum($id,$num);
				return 1;
			}
			
		}
		
		$item = array();
		$item['id'] = $id;
		$item['name'] = $name;
		$item['price'] = $price;
		if ($limit_buy != 0) {
			if ($num > $limit_buy) {
				$num = $limit_buy;
			}
		}
		$item['num'] = $num;
		$item['img'] = $img;
		$item['status'] = 0;
		$item['goodsId'] = $goodsId;
		$item['barcodeid'] = $barcodeid;
		$item['oldprice'] = $oldprice;
		$item['types'] = $types;
		$item['mbdscnt'] = $mbdscnt;
		$item['shopId'] =$shopId;
		$item['size'] = $size;
		$item['cate_id'] = $cate_id;
		$item['limit_buy'] = $limit_buy;
// 		$_SESSION['sid'] = $shopId;
		$_SESSION[$this->cartKey][$id] = $item;
		
	}

	/*
	修改购物车中的商品数量
	int $id 商品主键
	int $num 某商品修改后的数量，即直接把某商品
	的数量改为$num
	*/
	public function modNum($id,$num=1, $limit_buy = 0) {
		if (!isset($_SESSION[$this->cartKey][$id])) {
			return false;
		} else {
			$havenum = $_SESSION[$this->cartKey][$id]['num'];
			if ($limit_buy != 0 && $havenum + $num >= $limit_buy) {
				$_SESSION[$this->cartKey][$id]['num'] = $limit_buy;
				return 2;
			} else {
				$_SESSION[$this->cartKey][$id]['num'] = $num;
				return 1;
			}
		}



	}

	/*
	商品数量+1
	*/
	public function incNum($id,$num=1) {
		if (isset($_SESSION[$this->cartKey][$id])) {
			$_SESSION[$this->cartKey][$id]['num'] += $num;
		}
	}

	/*
	商品数量-1
	*/
	public function decNum($id,$num=1) {
		if (isset($_SESSION[$this->cartKey][$id])) {
			$_SESSION[$this->cartKey][$id]['num'] -= $num;
		}

		//如果减少后，数量为0，则把这个商品删掉
		if ($_SESSION[$this->cartKey][$id]['num'] <1) {
			$this->delItem($id);
		}
	}

	/*
	删除商品
	*/
	public function delItem($ids) {
// 		Log::write('delItem:' . $ids);
		$datas = $_SESSION[$this->cartKey];
		/* Log::write('delItem:' . $datas);
		foreach ($datas as  $data) {
			foreach ($data as $key => $val) {
				Log::write($key .'---' . $val);
			}
		} */
		
		unset($_SESSION[$this->cartKey][$ids]);
		/* $idArr = explode(",", $ids);
		foreach ($idArr as $id){
			$te = $_SESSION[$this->cartKey][$id];
			Log::write('ddd----d' . $te['id']);
			unset($_SESSION[$this->cartKey][$id]);
		}
		
		$datas = $_SESSION[$this->cartKey];
		Log::write('00delItem:' . $datas);
		foreach ($datas as  $data) {
			foreach ($data as $key => $val) {
				Log::write($key .'---' . $val);
			}
				
		} */
	}
	
	/*
	获取单个商品
	*/
	public function getItem($id) {
		return $_SESSION[$this->cartKey][$id];
	}

	/*
	查询购物车中商品的种类
	*/
	public function getCnt() {
		return count($_SESSION[$this->cartKey]);
	}
	
	/*
	查询购物车中商品的个数
	*/
	public function getNum(){
		if ($this->getCnt() == 0) {
			//种数为0，个数也为0
			return 0;
		}

		$sum = 0;
		$data = $_SESSION[$this->cartKey];
		foreach ($data as $item) {
			$sum += $item['num'];
		}
		return $sum;
	}

	/*
	购物车中商品的总金额
	*/
	public function getPrice() {
		//数量为0，价钱为0
		if ($this->getCnt() == 0) {
			return 0;
		}
		$price = 0.00;
		foreach ($_SESSION[$this->cartKey] as $item) {
			Log::write('getPrice--' . $item['status'] );
			if($item['status'] == 0){
				$price += $item['num'] * $item['price'];
			}
			
		}
		return sprintf("%01.2f", $price);
	}
	
	public function getOldPrice() {
		//数量为0，价钱为0
		if ($this->getCnt() == 0) {
			return 0;
		}
		$price = 0.00;
		foreach ($_SESSION[$this->cartKey] as $item) {
			Log::write('getOldPrice--' . $item['status'] );
			if($item['status'] == 0){
				$price += $item['num'] * $item['oldprice'];
			}
				
		}
		return sprintf("%01.2f", $price);
	}

	/*
	清空购物车
	*/
	public function clear() {
		$_SESSION[$this->cartKey] = array();
	}
	
	/**
	 * 去掉购物车中已经下订单的商品
	 * */
	public function clear_has_jiesuan(){
		foreach ( $_SESSION [$this->cartKey] as $item ) {
			if($item['status'] == 0){
				unset($_SESSION[$this->cartKey][$item['id']]);
			} else  {
				$_SESSION[$this->cartKey][$item['id']]['status'] = 0;
			}
		}
		//array_splice($_SESSION ['cart'],1,1);
	}
	
	/**
	 * 修改购物车中商品的状态 
	 * */
	public function change_item_status($id){
		if (isset($_SESSION[$this->cartKey][$id])) {
			Log::write('cartStatus'.$_SESSION['cart'][$id]['status']);
			if($_SESSION[$this->cartKey][$id]['status'] == 0){
				$_SESSION[$this->cartKey][$id]['status'] = 1;
			}  else {
				$_SESSION[$this->cartKey][$id]['status'] = 0;
			}
		}
		
	}
	
	/**
	 * 计算购物车中选中的商品的数量
	 * */
	public function countCheckedGoods(){
		$count = 0;
		foreach ( $_SESSION [$this->cartKey] as $item ) {
			if($item['status'] == 0){
				//unset($item ['id']);
				$count++;
			}
		}
		return $count;
		
	}


	/***
	 * get one item num
	 */
	public  function getOneItemNum($id) {
		return  $_SESSION[$this->cartKey][$id]['num'];
	}
}