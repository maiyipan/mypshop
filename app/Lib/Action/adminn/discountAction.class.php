<?php
class discountAction extends backendAction {
	public $list_relation = true;
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'discount' );
	}
	
	public function index(){
		$map = $this->_search();
		$mod = D($this->_name);
		!empty($mod) && $this->_list($mod, $map);
		$this->display();
	}
	protected function _search() {
		
		//Log::write("_search----dis",'DEBUG');
		$map = array();
		($begintime_min = $this->_request('begintime_min', 'trim')) && $map['begintime'][] = array('egt', $begintime_min) ;
		($begintime_max = $this->_request('begintime_max', 'trim'))  && $map['begintime'][] = array('elt', $begintime_max) ;
		($expiretime_min = $this->_request('expiretime_min', 'trim')) && $map['expiretime'][] = array('egt', $expiretime_min) ;
		($expiretime_max = $this->_request('expiretime_max', 'trim')) && $map['expiretime'][] = array('elt', $expiretime_max) ;
		
		$discount = $this->_request('discount', 'trim') ;
		$discount && $map['discount'] = array('eq',$discount);
		$map ['shopid'] =  array('in', $this->getUidList());// $this->shopId();
		
		$random = $this->_request('random', 'trim');
		$random = ltrim($random,'Z');
		$random && $map['random'] = array('like','%'. $random .'%');
		$this->assign('search', array(
				'begintime_min' => $begintime_min,
				'begintime_max' => $begintime_max,
				'expiretime_min' => $expiretime_min,
				'expiretime_max' => $expiretime_max,
				'createtime_min' => $createtime_min,
				'createtime_max' => $createtime_max,
				'discount' => $discount,
				'random' => $random,
		));
		return $map;
	}
	
	
	public function add() {
		//Log::write ( "ok",'DEBUG' );
		if (IS_POST) {
// 			dump($_POST);
			$discount = $this->_post ( 'discount', 'trim' );
			$discount_num = $this->_post ( 'discount_num', 'trim' );
			$expiretime = $this->_post ( 'expiretime' );
			$begintime = $this->_post ( 'begintime' );
			$name = $this->_post('name');//活动名称
			$commend = $this->_post ( 'commend' );//获取类型标签，通用或指定
			
			$itemid= $_POST['itemid'];//指定商品id号添加
			
 			$count=count($itemid);
 			$infos = $this->_mod->select ();
			$exclude_codes_array =array();
			
			if($infos){
				$x = 0;
       		 	foreach($infos as $r => $info){
        			$exclude_codes_array[$x] = $info['random'];
        			$x ++;
       		 }
			}
			$random = $this->generate_promotion_code ($discount_num,$exclude_codes_array,8);
			if($commend=="指定商品"){
				$commend=1;
			}else {
				$commend=0;
			}
			$typeid='D'.date('ymdhms');
			for($i = 0; $i < $discount_num; $i ++) {
				$dataList [] = array (
							  'shopid'=>$this->shopId(),
							  'discount' => $discount,
							  'random' =>  str_replace('.','',$discount).$random [$i], 
							  'begintime' => $begintime,
							  'expiretime' => $expiretime,
						      'createtime'=>date("Y-m-d H-m-s") ,
							  'itemtypename'=>$commend,
							  'typeid'=>$typeid,
							  'name'=>$name,
										);				
								}
			for ($j= 0;$j<$count;$j++){
				$data[]=array(
						'typeid'=>$typeid,
						'itemid'=>$itemid[$j],
				);
			}
			if($commend=1){
				M('coupons')->addAll($data);
			}
			$this->_mod->addAll ( $dataList );
			$this->success ( L ( 'operation_success' ),U('discount/index') );
		} else {
				$this->display ();
		}
	}
	/**
	 * 添加指定商品
	 */
	public function itemadd(){
		if (IS_POST) {
			$prefix = C(DB_PREFIX);
			$uid = $this->shopId();
			$where[$prefix.'item.uid'] = array('in', $this->getUidList());
			$keyword = $this->_request ( 'keyword', 'trim' );
			$where['_string'] = " status = 1 and title like '%$keyword%' or ".$prefix."item.goodsId = '$keyword'";
			$list = M('item')->field($prefix.'item.id, price ,b.goodsId,b.title,b.img')
					->join($prefix.'item_base as b ON '.$prefix.'item.baseid=b.id')
					->where($where)->limit('0,20')->select();
			foreach ($list as &$item){
				$item['img'] = attach($item['img']);
			}
			$this->ajaxReturn(1, '', $list);
		}else{
			if (IS_AJAX) {
				$response = $this->fetch();
				$this->ajaxReturn(1, '', $response);
			} else {
				$this->display();
			}
		}
		
	}
	/**
	 * excel 导出
	 */
	public function phpexcel() {
		/*=====
		这里写具体的数据调用与数据处理
		======*/
		Vendor ( "Excel.PHPExcel"); //导入thinkphp第三方类库

		//创建一个读Excel模版的对象
		$objReader = PHPExcel_IOFactory::createReader ( Excel5 );
		$objPHPExcel = $objReader->load (APP_NAME . '/' . 'ExcelTpl/'."template.xls"); //读取模板，模版放在根目录
		//获取当前活动的表
		$objActSheet = $objPHPExcel->getActiveSheet ();
		$objActSheet->setTitle ('ynameing' );//设置excel标题

		$objActSheet->setCellValue ( 'A1', '优惠券' );
		$objActSheet->setCellValue ( 'A2', '信息导出' );
		$objActSheet->setCellValue('F2','导出时间:'. date('Y-m-d H:i:s'));

		//现在开始输出列头了
		$objActSheet->setCellValue('A3','折扣');
		$objActSheet->setCellValue('B3','折扣券');
		$objActSheet->setCellValue('C3','开始时间');
		$objActSheet->setCellValue('D3','到期时间');
		$objActSheet->setCellValue('E3','创建时间');
		//具体有多少列，有多少就写多少，跟下面的填充数据对应上就可以
		
		//现在就开始填充数据了 （从数据库中）
		//Log::write("beign:".$_GET['begintime_max'],'DEBUG');
		if($_GET['begintime_min']){
			$where['begintime'] =array(
					array('egt',$_GET['begintime_min']),array('elt',$_GET['begintime_max'])
				);
		}
		if($_GET['expiretime_min']){
			$where['expiretime'] = array(
					array('egt',$_GET['expiretime_min']),array('elt',$_GET['expiretime_max'])
				);
		}
		if($_GET['createtime_min']){
			$where['createtime'] = array(
				array('egt',$_GET['createtime_min']),array('elt',$_GET['createtime_max'])
				);
		}
		if($_GET['discount']){
			$where['discount'] = $_GET['discount'];
		}
		$info = $this->_mod->where($where)->select ();
		 //Log::write($this->_mod->getLastSql(), 'DEBUG');
		$baseRow = 4; //数据从N-1行开始往下输出 这里是避免头信息被覆盖
		
		
		if($info){
       	 foreach($info as $r => $info){
            $row = $baseRow + $r;
            $objPHPExcel->getActiveSheet ()->setCellValue ( A . $row, $info ['discount']);
        	$objPHPExcel->getActiveSheet ()->setCellValue ( B . $row, 'Z'.$info ['discount'].$info['random'] );
			$objPHPExcel->getActiveSheet ()->setCellValue ( C . $row, $info ['begintime'] );
			$objPHPExcel->getActiveSheet ()->setCellValue ( D . $row, $info ['expiretime'] );
			$objPHPExcel->getActiveSheet ()->setCellValue ( E . $row, $info ['createtime'] );
        }
   	   }
//    	   dump($info);
//    	   exit();
		//导出
		$filename='折扣券导出';
		$filename = iconv('utf-8','gb2312', $filename);
		
		header('Content-type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename=' . $filename . '.xls');
        header("Cache-Control: max-age=0");
        $objWriter = PHPExcel_IOFactory::createWriter ( $objPHPExcel, 'Excel5' );
        $objWriter->save('php://output');
        exit;
	}
	
/** 
		 * @param int $no_of_codes//定义一个int类型的参数 用来确定生成多少个优惠码 
		 * @param array $exclude_codes_array//定义一个exclude_codes_array类型的数组 
		 * @param int $code_length //定义一个code_length的参数来确定优惠码的长度 
		 * @return array//返回数组 
		 */
		function generate_promotion_code($no_of_codes, $exclude_codes_array = '', $code_length = 4) {
		$characters = "0123456789";
		$promotion_codes = array (); //这个数组用来接收生成的优惠码 
		for($j = 0; $j < $no_of_codes; $j ++) {
			$code = "";
			for($i = 0; $i < $code_length; $i ++) {
				$code .= $characters [mt_rand ( 0, strlen ( $characters ) - 1 )];
			}
			//如果生成的4位随机数不再我们定义的$promotion_codes函数里面 
			if (! in_array ( $code, $promotion_codes )) {
				if (is_array ( $exclude_codes_array )) // 
				{
					if (! in_array ( $code, $exclude_codes_array )) //排除已经使用的优惠码 
					{
						$promotion_codes [$j] = $code; //将生成的新优惠码赋值给promotion_codes数组 
					} else {
						$j --;
					}
				} else {
					$promotion_codes [$j] = $code; //将优惠码赋值给数组 
				}
			} else {
				$j --;
			}
		}
		return $promotion_codes; 
	}

}