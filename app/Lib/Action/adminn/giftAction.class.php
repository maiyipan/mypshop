<?php
class giftAction extends backendAction {
	public function _initialize() {
		parent::_initialize ();
		$this->_mod = D ( 'gift' );
	}
	
	public function index() {
		$this->list_relation = true;
		$big_menu = array ('title' => '增加礼金卡', 'iframe' => U ( 'gift/add' ), 'id' => 'add', 'width' => '450', 'height' => '190' );
		$this->assign ( 'big_menu', $big_menu );
		$where['shopid'] = $this->shopId();
		$count =  $this->_mod->where($where)->count();
		$pager = new Page($count, $pagesize);
		$page = $pager->show();
		$this->assign("page", $page);
		
		$select =  $this->_mod->where($where)->limit($pager->firstRow.','.$pager->listRows);
		$list = $select->select();
		$this->assign('list', $list);
		$this->assign('list_table', true);
		$this->display();
	}
	
	public function _before_insert() {
	
		// 、、 $data['discount'] = $discount;
	}
	
	public function add() {
		//Log::write ( "ok" );
		if (IS_POST) {
			$gift = $this->_post ( 'gift', 'trim' );
			$num = $this->_post ( 'num', 'trim' );
			$expiretime = $this->_post ( 'expiretime' );
			$begintime = $this->_post ( 'begintime' );
			
			
			$infos = $this->_mod->select ();
			$exclude_codes_array =array();
			
			if($infos){
				$x = 0;
       		 	foreach($infos as $r => $info){
        			$exclude_codes_array[$x] = $info['random'];
        			$x ++;
       		 }
			}
			
			$random = $this->generate_promotion_code ($num,$exclude_codes_array,8);
			for($i = 0; $i < $num; $i ++) {
				$dataList [] = array ('gift' => $gift,
									  'random' => $gift . $random[$i],
									  'surplus' => $gift,
									  'begintime' => $begintime,
									  'expiretime' => $expiretime,
									  'shopid'=>$this->shopId(),
								      'createtime'=>date("Y-m-d H-m-s") );
			}
			
			$this->_mod->addAll ( $dataList );
			
			IS_AJAX && $this->ajaxReturn ( 1, L ( 'operation_success' ), '', 'add' );
			$this->success ( L ( 'operation_success' ) );
		} else {
			$this->assign ( 'open_validator', true );
			if (IS_AJAX) {
				$response = $this->fetch ();
				$this->ajaxReturn ( 1, '', $response );
			} else {
				$this->display ();
			}
		}
	
	}
	
	
	
	
	public function phpexcel() {
// 		dump($_GET);
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

		$objActSheet->setCellValue ( 'A1', '礼金卡' );
		$objActSheet->setCellValue ( 'A2', '信息导出' );
		$objActSheet->setCellValue('F2','导出时间:'. date('Y-m-d H:i:s'));

		//现在开始输出列头了
		$objActSheet->setCellValue('A3','礼金');
		$objActSheet->setCellValue('B3','余额');
		$objActSheet->setCellValue('C3','礼金卡');
		$objActSheet->setCellValue('D3','开始时间');
		$objActSheet->setCellValue('E3','到期时间');
		$objActSheet->setCellValue('F3','创建时间');
		//具体有多少列，有多少就写多少，跟下面的填充数据对应上就可以

		//现在就开始填充数据了 （从数据库中）
		//Log::write("beign:".$_GET['begintime_max']);
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
		if($_GET['gift']){
			$where['gift'] = $_GET['gift'];
		}
		$infos = $this->_mod->where($where)->select ();
		 //Log::write($this->_mod->getLastSql(), 'DEBUG');
// 		 dump($this->_mod->getLastSql(), 'DEBUG');
		$baseRow = 4; //数据从N-1行开始往下输出 这里是避免头信息被覆盖
		
		
		if($infos){
       	 foreach($infos as $r => $info){
            $row = $baseRow + $r;
            $objPHPExcel->getActiveSheet ()->setCellValue ( A . $row, $info ['gift'] );
            $objPHPExcel->getActiveSheet ()->setCellValue ( B . $row, $info['surplus'] );
        	$objPHPExcel->getActiveSheet ()->setCellValue ( C . $row, 'B'.$info ['gift'].$info['random'] );
			$objPHPExcel->getActiveSheet ()->setCellValue ( D . $row, $info ['begintime'] );
			$objPHPExcel->getActiveSheet ()->setCellValue ( E . $row, $info ['expiretime'] );
			$objPHPExcel->getActiveSheet ()->setCellValue ( F . $row, $info ['createtime'] );
        }
   	   }
// 		exit();
		//导出
		$filename='礼金卡导出';
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
	
	
	public function _search(){
		$map = array();
		($begintime_min = $this->_request('begintime_min', 'trim')) && $map['begintime'][] = array('egt', $begintime_min) ;
		($begintime_max = $this->_request('begintime_max', 'trim'))  && $map['begintime'][] = array('elt', $begintime_max) ;
		($expiretime_min = $this->_request('expiretime_min', 'trim')) && $map['expiretime'][] = array('egt', $expiretime_min) ;
		($expiretime_max = $this->_request('expiretime_max', 'trim')) && $map['expiretime'][] = array('elt', $expiretime_max) ;
		($createtime_min = $this->_request('createtime_min', 'trim')) && $map['createtime'][] = array('egt',$createtime_min) ;
		($createtime_max = $this->_request('createtime_max', 'trim')) && $map['createtime'][] = array('elt', $createtime_max) ;
		
		$gift = $this->_request('gift', 'trim') ;
		$gift && $map['gift'] = array('eq',$gift);
		
		$random = $this->_request('random', 'trim');
		$random && $map['random'] = array('like','%'. $random .'%');
		$this->assign('search', array(
				'begintime_min' => $begintime_min,
				'begintime_max' => $begintime_max,
				'expiretime_min' => $expiretime_min,
				'expiretime_max' => $expiretime_max,
				'createtime_min' => $createtime_min,
				'createtime_max' => $createtime_max,
				'gift' => $gift,
				'random' => $random,
		));
		return $map;
	}
}