<?php
class DemoAction extends baseAction{
	
// 	private $cardID = 0;
	/**
	 * 		 微信退款
	 * */
	public function weixinRefund($transaction_id, $out_trade_no, $out_refund_no, $total_fee, $refund_fee, $op_user_id){
		
		$weixinrefund= new WeixinPay();
		$refund_id=$weixinrefund->weixinRefund($transaction_id, $out_trade_no, $out_refund_no, $total_fee, $refund_fee, $op_user_id);
		//做一个数据存储
	}
	/**
	 * 统一支付接口类
	 */
	public function weixin_pay(){
		
		$weixinpay = new WeixinPay();
		$weixinpay->weixin_Pay($body, $out_trade_no, $total_fee);
		
	}
	public function creatercard(){
		dump("this");
		Vendor ( 'WeiXin.WeixinCardSDK' );
		$wxcard=new WxCard();
		$logo_url=$wxcard->wxCardUpdateImg();//上传logo
		dump($logo_url);
		$wxBatchGet=$wxcard->wxBatchGet();//拉取门店列表
		dump($wxBatchGet);
		$color=$wxcard->wxCardColor();//选取卡劵背景颜色
		dump($color);
		$color="#FFF";//$_POST['color'];//选取卡片颜色
		$title="￥10";//$_POST['title'];//卡劵标题
		$sub_title="满100可使用"; //$_POST['sub_title'];//获取小标题
		$card_type="CASH"; //$_POST['card_tpye'];//获取卡劵类型$type="GROUPON";(团购券：GROUPON，代金券：CASH，折扣券：DISCOUNT，礼金券：GIFT，优惠券：GENERAL_COUPON)
			
		$brand_name="深圳店";//$shopdata['name'];//获取商户名称
		$code_type=0;
		$notice="使用时向服务员出示此券";
		$service_phone=$shopdata['tel'];
		$description='无';
		$quantity='50000';
		$date_info= new DateInfo(1, 1397577600 ,1422724261);
		$sku= new Sku($quantity);
		
		$wxcard = new WxCard();
		$logo_url= $wxcard->wxCardUpdateImg();//获取logo地址
		
		$base_info= new BaseInfo($logo_url, $brand_name, $code_type, $title, $color, $notice, $service_phone, $description, $date_info, $sku);
		
		$base_info->set_sub_title( "" );
		$base_info->set_use_limit( 1 );
		$base_info->set_get_limit( 3 );
		$base_info->set_use_custom_code( false );
		$base_info->set_bind_openid( false );
		$base_info->set_can_share( true );
		$base_info->set_url_name_type( 1 );
		$base_info->set_custom_url( "http://www.qq.com" );
		
		$card = new Card($card_type, $base_info);
		if($card_type=='GROUPON'){
			$card->get_card()->set_deal_detail( "双人套餐\n -进口红酒一支。\n孜然牛肉一份。" );
		}
		if($card_type=='CASH'){
			dump("CASH");
			$card->get_card()->set_least_cost('100');//总金额 $least_cost
			$card->get_card()->set_reduce_cost('10');//减免金额$reduce_cost
		}
		if($card_type=='DISCOUNT'){
			$card->get_card()->set_discount("30");
		}
		if($card_type=='GIFT'){
			$card->get_card()->set_gift('可兑换音乐木盒一个。');
		}
		if($card_type=='GENERAL_COUPON'){
			$card->get_card()->set_default_detail($default_detail);
		}
		//--------------------------to json--------------------------------
		$jsonData=$card->toJson();
		dump("jasonData".$jsonData);
// 		exit();
		/* 数据库保存 */
		$cardData=M('card_coupons');
		$dataList[]=array(
				'uid'=>$this->uid,
				'logo_url'=>$logo_url,
				'code_type'=>$code_type,
				'brand_name'=>$brand_name,
				'title'=>$title,
				'sub_title'=>$sub_title,
				'color'=>$color,
				'notice'=>$notice,
				'description'=>$description,
				'sku'=>$sku,
				'quantity'=>$quantity,
				'type'=>$type,
				'begin_timestamp'=>1430000,
				'end_timestamp'=>1530000,
				'fixed_term'=>15,
				'fixed_begin_term'=>0
		);
// 		$cardData->addAll($dataList);
	
		
		//数字签名
		$signature = new Signature();
		$signature->add_data( "875e5cc094b78f230b0588c2a5f3c49f" );
		$signature->add_data( "wx57bf46878716c27e" );
		$signature->add_data( "213168808" );
		$signature->add_data( "12345" );
		$signature->add_data( "55555" );
		dump($signature->get_signature());
// 		return  $signature->get_signature();

 		
		$wxCardCreated=$wxcard->wxCardCreated($jsonData);//创建卡劵
		dump('cardCreated'.$wxCardCreated);  
		//
// 		}else {
// 			$this->display();
// 		}
	}	
	
	/**
	 * 创建领取url
	 **/
	public function url_create(){
		$title=$_POST['title'];
		$sub_title=$_POST['sub_title'];
		$explain=$_POST['explain'];
		$coupons=$_POST['coupons'];
		$begintime=$_POST['begintime'];
		$expiretime=$_POST['expiretime'];
		$share=$_POST['share'];
		$url= C('pin_baseurl')."/index.php/Demo/pulldown/id/1/coupons/".$coupons."/begintime/".$begintime."/expiretime/".$expiretime."/share/".$share;
		$data[]= array(
						'title'=>$title,//大标题
						'sub_title'=>$sub_title,//小标题
						'explain'=>$explain,//内容解释
						'coupons' => $coupons,//折扣
						'begintime' => $begintime,//开始时间
						'expiretime' => $expiretime,//结束时间
						'share'=>$share,//数量
						'url'=>$url,
		);
		dump($data);
		M('coupons_card')->addAll($data);
	}
	/** 
	 *获取优惠券 
	 ***/
	public function pulldown(){
		
			if(IS_POST){
				
				dump($_POST);
// 				$phone=M('coupons_card')->where("phone=".$_POST['phone'])->find();
				$phone = M('coupons_card')->where(array('phone'=>$_POST['phone'],'coupons'=>$_POST['coupons']))->find();
				dump($phone);
				if($phone){
					echo "您已经领取过优惠劵";
				}else {
// 					$cardID=M('coupons_card')->max('cardid');
					$cardID=M('coupons_card')->where('coupons='.$_POST['coupons'])->max('cardid');
					$phone = $_POST['phone'];
					$coupons=$_POST['coupons'];
					$share=$_POST['share'];
					$cardID +=$_POST['id'];
					$discount_num="1";
					$infos = M('coupons_card')->select ();
					$exclude_codes_array =array();
				
					if($infos){
						$x = 0;
						foreach($infos as $r => $info){
							$exclude_codes_array[$x] = $info['random'];
							$x ++;
						}
					}
					$random = $this->generate_promotion_code ($discount_num,$exclude_codes_array,8);
					if($cardID<$share){
						dump($cardID);
						for($i = 0; $i < $discount_num; $i ++) {
							$dataList[] = array (
									'cardid'=>$cardID,
									'phone'=>$phone,
									'coupons' => $coupons,
									'begintime' => $begintime,
									'expiretime' => $expiretime,
									'share'=>$share,
									'random' => "Z".$random [$i],
									'createtime'=>date("Y-m-d H-m-s") ,//创建时间
							);
						}
// 						dump($dataList);
						M('coupons_card')->addAll($dataList);
						echo "优惠劵领取成功";
						$this->success ( L ( 'operation_success' ),'{:U(my/coupon)}');
					}else {
						echo "优惠券已经被领完";
					}
				}
			}else {
				$list['coupons']=$_GET['coupons'];
				$list['share']=$_GET['share'];
				$list['id']=$_GET['id'];
// 				dump($list);
				$this->assign('list',$list);
				
				
				$this->display();
			}
			
// 			exit();			
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