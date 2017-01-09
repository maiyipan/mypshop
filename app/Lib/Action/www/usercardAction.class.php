<?php
class usercardAction extends frontendAction{
	public function _initialize() {
		parent::_initialize ();
		if (! $this->visitor->is_login && in_array ( ACTION_NAME, array (
				'share_item',
				'fetch_item',
				'publish_item',
				'like',
				'unlike',
				'delete',
				'comment'
		) )) {
			//IS_AJAX && $this->ajaxReturn ( 0, L ( 'login_please' ) );
			$this->redirect ( 'index/index' );
		}
	}
	/**
	 * 会员主页
	 */
	public function index(){
		$uid = $this->visitor->info ['id'];
		$data=M('card');
		$data=$data->where("id=".$uid)->find();
		
		$this->assign ( 'val', $data);
		$this->display ();
	}
	/**
	 * 申请会员卡
	 */
	public function applyMerbership(){
		$this->display ();
	}
	/**
	 * 会员注册
	 * 需要访问第三方服务才能继续服务20150914
	 * */
	public function usercard_register(){
		//需要测试第三方服务才能再继续访问2015.9.14测试
		$uid = $this->visitor->info ['id'];
		$userInfo ['id'] = $uid;
		if(IS_POST){
			$UserMod = M ( 'user' );
			$cardMod = M ( 'card' );
			$codenum = M('verifi_code');
			$name=$_POST['name'];//姓名
			$mobile=$_POST['mobile'];//手机号
			$verifi_code=$_POST['verifi_code'];//验证码
// 			$credentials_code=$_POST['credentials_code'];//证件类型
			$credentials_num=$_POST['credentials_num'];//证件号码
			$birthday=$_POST['time'];//生日
			$address=$_POST['address'];//地址
			if($_POST['sex']==1){
				$sex='F';
			}else if($_POST['sex']==0) {
				$sex="M";
			}
			$email=$_POST['email'];//电子邮箱
			
			
			//验证码判断
			$data=$codenum->where("phone=".$mobile)->order('id desc')->find();
			if( $verifi_code != $data['codenum']){
				$ret_url = 'applyMerbership';
				$this->error('验证码输入错误', $ret_url );
			}
			
			
			$password = $this->_post ( 'password', 'trim' );
			$repassword = $this->_post ( 'password_confirm', 'trim' );
			if ($password != $repassword) {
				$this->error ( L ( 'inconsistent_password' ) ); //确认密码
			}
			//请求接口
			Vendor ( 'FrdifService.CustomService' );
			$client = new \CustomService ();
			$client -> setPosturl(C('MemberSystemUrl'));
			$client -> setUserid(C('MemberSystemUserid'));
			$client -> setPassword(C('MemberSystemPassword'));
			$client->setCmdid ( '1012' );
			//Log::write ( $mobile . ',' . $name .','.$credentials_num.','.$birthday.','.$address.','.$sex.','.$email);
			$client->setQueryParam ( $mobile . ',' . $name .','.$credentials_num.','.$birthday.','.$address.','.$sex.','.$email);
			//Log::write ( 'begin get data form webservice....', 'info' );
				
			$result = $client->processdata ();
			//Log::write ( 'the result:' . $resultData, 'info' );
			$i = 0;
			if ($result ['rtn'] != - 1) {
				$resultData = explode ( '	', $result ['outputpara'] );
				foreach ( $resultData as $key ) {
					//Log::write ( $i . '--' . $key, 'DEBUG' );
					$i ++;
				}
				if($resultData[0]==1){
					$card_num = $resultData [2]; // 卡号
					$member_account = $resultData [3]; // 账号
					$this->userbind($card_num,$password);
				}elseif ($resultData[0]==0){
					$this->error("输入数据有误，请重新输入！");
					$add=$resultData[1];//返回失败信息
				}	
			}else {
				$this->display();
			}
		}
	}
	/**
	 * 激活会员卡
	 */
	public function activateMenbership(){
		$uid = $this->visitor->info ['id'];
		$User = D ( 'user' );
		$Card = D ('card');
		$bindstatus = $User->queryBindStatus ( $uid );
		if ($bindstatus ['bindstatus'] == 1) {
			$where= array( 'card_num'=>$bindstatus['card_num'],'id'=>$uid);
			$cardInfo = $Card->where($where)->find();
			$this->assign('val', $cardInfo);
			$this->display('index');
		} else {
			$this->display ();
		}
	}
	/**
	 * 获取随机码图片
	 */
	public function Random_code(){
		Image::buildImageVerify(4,1,'gif','50','24','randomcode');
	}
	//发送验证码
	public function get_verification_code(){
		$phone=$_POST['phone'];
		$Random_code = $this->_post('Random_code', 'trim');
		//判断随机码是否存在
		if(empty($Random_code)){
			$this->ajaxReturn(0,'请输入随机码。');
		}
		//查询随机码是否存在，并不过期
		if(session('randomcode') != md5($Random_code)){
			$this->ajaxReturn(0,'随机码输入有误');
		}
		$corpid='302062';
		$codenum=date('His');
		$content  = C('pin_bind_msg_tpl');
		$content = str_replace('{num}',$codenum,$content);
		$sms_send=smsSend($phone, $corpid,$content);
		if($sms_send['Count'] == '-102'){
			$result['result_msg'] = $sms_send['ErrMsg'];
			$this->ajaxReturn(0,'发送失败。',$result);
		}
		 else {
		 	$result = array(
		 			'result_msg' => '验证码发送成功。',
		 			'result_code' => $sms_send,
		 	);
		 	//需要校验验证码
		 	$code=M('verifi_code');
		 	$data['codenum']=$codenum;
		 	$data['phone']=$phone;
		 	$code->add($data);
		 	$this->ajaxReturn(1,'发送成功。',$result);
		 }
	}
	/** 
	 * 会员卡绑定
	 *  **/
	public function usercardBind(){
		$uid = $this->visitor->info ['id'];
		$userInfo ['id'] = $uid;
		$UserMod = M ( 'user' );
		$cardMod = M ( 'card' );
		$codenum = M('verifi_code');
		if (IS_POST) {
			$queryParam = $this->_post ( 'card_num' );

			$password = $this->_post ( 'password' );
			$verifi_code=$this->_post('verifi_code');//验证码

			//验证码判断
			$map['phone'] = $queryParam;
			$codenum=$codenum->field('codenum')->where($map)->order('id desc')->limit(1) ->  find();
			// 			dump($codenum);
			if( $verifi_code != $codenum['codenum']){
				$ret_url = U('usercard/activateMenbership');//$ret_url = 'activateMenbership';
				//TODO 
				
				//$this->error('验证码输入错误', $ret_url );
			}
			$queryType = '1'; // 1 卡号 2 手机号
			if (strlen ( $queryParam ) == 11) {
				$queryType = 2;
			}
			//判断会员卡是否已经被绑定
			if($queryType == 1 ){
				$card_info = M('card')->where(array('card_num'=>$queryParam))->find();
			}else if($queryType == 2){
				$card_info = M('card')->where(array('mobile_a'=>$queryParam))->find();
				//Log::write(M('card')->getLastSql(), 'DEBUG');
			}

			if($card_info){
				//Log::write('card_info>>>'.$card_info['card_num'].'----mobile_a>>>'.$card_info['mobile_a']);
				$this->error('该会员卡已绑定过了，请更换会员卡再试',U('usercard/activateMenbership',array('sid'=>$this->shopId)));
			}

			
			// 请求接口
			Vendor ( 'FrdifService.HttpService' );
			$client = new \HttpService();
			$client->  setCmdid ( '1000' );

			$param = array(
				'_param0' =>2,
				'_param1' => $queryParam
			);

			$client->  setParam ($param );
			Log::write ( 'begin get data form httpservice>>>', 'info' );
			$result = $client->getInfo();
			Log::write ( 'the end>>>', 'info');

			$i = 0;
			if ($result  != -1) {
				$resultData = explode ( '	', $result  );

				$card_status = $resultData [5]; // 状态
				$detail_status = $resultData [6]; // 详细状态
				$expire_time = $resultData [8]; // 过期时间
				// TODO 加入密码
				$password_r = $resultData[26]; //密码
				//Log::write ( 'the result:' . $card_status . '--' . $detail_status. '--'.'[ '.$password.']'.'---' .$password_r, 'info' );
				// TODO 密码判断
				if ($card_status == 'Y' && $detail_status == '04' && $password == $password_r) { // 需要加过期时间的比较 && $password== $password_r
					// 保存卡信息
					$data ['id'] = $uid;
					$data ['member_account'] = $member_account = $resultData [0]; // 账号
					$data ['card_num'] = $card_num = $resultData [1]; // 卡号
					$data ['member_name'] = $card_num = $resultData [2]; // 会员名
					$data ['public_store'] = $public_store = $resultData [3]; // 门店
					$data ['card_category'] = $card_category = $resultData [4]; // 卡类别
					$data ['card_status'] = $card_status = $resultData [5]; // 状态
					$data ['detail_status'] = $detail_status = $resultData [6]; // 详细状态
					$data ['expire_time'] = $expire_time = $resultData [8]; // 过期时间
					$data ['public_time'] = $public_time = $resultData [7]; // 发行时间
					$data ['credentials_code'] = $credentials_code = $resultData [9]; // 证件代码
					$data ['credentials_num'] = $credentials_num = $resultData [10]; // 证件号码
					$data ['birthday'] = $birthday = $resultData [11]; // 生日
					$data ['age'] = $age = $resultData [12]; // 年龄
					$data ['public_type'] = $public_type = $resultData [13]; // 办卡方式
					$data ['employer'] = $employer = $resultData [14]; // 工作单位
					$data ['tel'] = $tel = $resultData [15]; // 联系电话
					$data ['mobile_a'] = $mobile_a = $resultData [16]; // 手机 1
					$data ['mobile_b'] = $mobile_b = $resultData [17]; // 手机 2
					$data ['email'] = $email = $resultData [18]; // 电子邮箱
					$data ['address'] = $address = $resultData [19]; // 通讯地址
					$data ['postcode'] = $postcode = $resultData [20]; // 邮编
					$data ['current_store'] = $current_store = $resultData [21]; // 当期积分
					$data ['history_store'] = $history_store = $resultData [22]; // 历史积分
					$data ['current_money'] = $current_money = $resultData [23]; // 当期消费金额
					$data ['history_money'] = $history_money = $resultData [24]; // 历史消费金额
					$data ['discount'] = $discount = $resultData [25]; // 折扣
					try {
						//Log::write("save card info..", 'DEBUG');
						$cardMod->data ( $data )->add ();
						////Log::write("save card info.. " . $cardMod->getLastSql(), 'DEBUG', 'DEBUG');
					} catch ( Exception $e ) {
						//Log::write ( 'err:' . $e );
					}
						
					$client->setCmdid ( '1010' );
					$queryType = '3'; // 3卡号 2 手机号
					if (strlen ( $queryParam ) == 11) {
						$queryType = 2;
					}

					$param = array(
						'_param0' =>2,
						'_param1' => $queryParam
					);

					$client->  setParam ($param );
					//Log::write ( 'get account info...' );
					$resultInfo = $client->getInfo();

					$i = 0;
					if ($resultInfo != - 1) {
						$resultDataInfo = explode('	', $resultInfo);
						$data ['card_score'] = $card_score = $resultDataInfo [2];
						$data ['card_balance'] = $card_balance = $resultDataInfo [3];
						$data ['id'] = $uid;
						$cardMod->save ( $data );
						
						$userInfo ['card_num'] = $data ['card_num'];
						$userInfo ['bindstatus'] = '1';
						$userInfo ['card_balance'] = $data ['card_balance'];
						$UserMod->where('id = '.$data ['id'])->save ( $userInfo ); // 绑定
						
						$ret_url = U('usercard/activateMenbership',array('sid'=>$this->shopId));
						$this->success ( '会员卡绑定成功', $ret_url );
					} else {
						//Log::write('get info error:' . $resultInfo['errormsg']);
						$ret_url = U('usercard/activateMenbership',array('sid'=>$this->shopId));
						$this->error('卡信息或密码有误，请重新绑定', $ret_url );
					}
						
				} else {
					//Log::write('get info error: 卡信息或密码有误' );
					$ret_url = U('usercard/activateMenbership',array('sid'=>$this->shopId));//'activateMenbership';
					$this->error('卡信息或密码有误，请重新绑定', $ret_url );
				}
		
			} else {
				//Log::write('get info error:' . $result['errormsg']);
				$ret_url = U('usercard/activateMenbership',array('sid'=>$this->shopId));
				$this->error('卡信息有误，请重新绑定', $ret_url );
			}
		}
	}
	
	public function userbind($card_num,$password){

		$queryParam = $card_num;
		$password = $password;
		
		$queryType = '1'; // 1 卡号 2 手机号
		if (strlen ( $queryParam ) == 11) {
			$queryType = 2;
		}
		// 请求接口
		Vendor ( 'FrdifService.CustomService' );
		$client = new \CustomService ();
		
		$client -> setPosturl(C('MemberSystemUrl'));
		$client -> setUserid(C('MemberSystemUserid'));
		$client -> setPassword(C('MemberSystemPassword'));
		$client->  setCmdid ( '1000' );
		//Log::write ( $queryType . ',' . $queryParam );
		$client->  setQueryParam ( $queryType . ',' . $queryParam );
		//Log::write ( 'begin get data form webservice....', 'info' );
		$result = $client->processdata ();
		//Log::write ( 'the result:' . $resultData, 'info' );
		
		$i = 0;
		if ($result ['rtn'] != - 1) {
			$resultData = explode ( '	', $result ['outputpara'] );
			foreach ( $resultData as $key ) {
				//Log::write ( $i . '--' . $key, 'DEBUG' );
				$i ++;
			}
			$card_status = $resultData [5]; // 状态
			$detail_status = $resultData [6]; // 详细状态
			$expire_time = $resultData [8]; // 过期时间
			// TODO 加入密码
			$password_r = $resultData[26]; //密码
			//Log::write ( 'the result:' . $card_status . '--' . $detail_status. '--'.'[ '.$password.']'.'---' .$password_r, 'info' );
			// 				dump();
			// TODO 密码判断
			if ($card_status == 'Y' && $detail_status == '04' && $password == $password_r) { // 需要加过期时间的比较 && $password== $password_r
				$userInfo ['card_num'] = $resultData [1];
				$userInfo ['bindstatus'] = '1';
				$UserMod->save ( $userInfo ); // 绑定
		
				// 保存卡信息
				$data ['id'] = $uid;
				$data ['member_account'] = $member_account = $resultData [0]; // 账号
				$data ['card_num'] = $card_num = $resultData [1]; // 卡号
				$data ['member_name'] = $card_num = $resultData [2]; // 会员名
				$data ['public_store'] = $public_store = $resultData [3]; // 门店
				$data ['card_category'] = $card_category = $resultData [4]; // 卡类别
				$data ['card_status'] = $card_status = $resultData [5]; // 状态
				$data ['detail_status'] = $detail_status = $resultData [6]; // 详细状态
				$data ['expire_time'] = $expire_time = $resultData [8]; // 过期时间
				$data ['public_time'] = $public_time = $resultData [7]; // 发行时间
				$data ['credentials_code'] = $credentials_code = $resultData [9]; // 证件代码
				$data ['credentials_num'] = $credentials_num = $resultData [10]; // 证件号码
				$data ['birthday'] = $birthday = $resultData [11]; // 生日
				$data ['age'] = $age = $resultData [12]; // 年龄
				$data ['public_type'] = $public_type = $resultData [13]; // 办卡方式
				$data ['employer'] = $employer = $resultData [14]; // 工作单位
				$data ['tel'] = $tel = $resultData [15]; // 联系电话
				$data ['mobile_a'] = $mobile_a = $resultData [16]; // 手机 1
				$data ['mobile_b'] = $mobile_b = $resultData [17]; // 手机 2
				$data ['email'] = $email = $resultData [18]; // 电子邮箱
				$data ['address'] = $address = $resultData [19]; // 通讯地址
				$data ['postcode'] = $postcode = $resultData [20]; // 邮编
				$data ['current_store'] = $current_store = $resultData [21]; // 当期积分
				$data ['history_store'] = $history_store = $resultData [22]; // 历史积分
				$data ['current_money'] = $current_money = $resultData [23]; // 当期消费金额
				$data ['history_money'] = $history_money = $resultData [24]; // 历史消费金额
				$data ['discount'] = $discount = $resultData [25]; // 折扣
				try {
					//Log::write("save card info..", 'DEBUG');
					$cardMod->data ( $data )->add ();
					////Log::write("save card info.. " . $cardMod->getLastSql(), 'DEBUG', 'DEBUG');
				} catch ( Exception $e ) {
					//Log::write ( 'err:' . $e );
				}
		
				$client->setCmdid ( '1010' );
				$queryType = '3'; // 3卡号 2 手机号
				if (strlen ( $queryParam ) == 11) {
					$queryType = 2;
				}
				//Log::write ( $queryType . ',' . $queryParam );
				//$queryType = 3;
				$client->setQueryParam ( $queryType . ',' . $queryParam );
				//Log::write ( 'get account info...' );
				$resultInfo = $client->processdata ();
		
				$i = 0;
				if ($resultInfo['rtn'] != - 1) {
					$resultDataInfo = explode('	', $resultInfo['outputpara']);
					$data ['card_score'] = $card_score = $resultDataInfo [2];
					$data ['card_balance'] = $card_balance = $resultDataInfo [3];
					$data ['id'] = $uid;
					$cardMod->save ( $data );
					
					$userInfo ['card_num'] = $data ['card_num'];
					$userInfo ['bindstatus'] = '1';
					$userInfo ['card_balance'] = $data ['card_balance'];
					$UserMod->where('id = '.$data ['id'])->save ( $userInfo ); // 绑定
					$ret_url = 'activateMenbership';
					$this->success ( L ( 'bindSuccess' ) . $username, $ret_url );
				} else {
					//Log::write('get info error:' . $resultInfo['errormsg']);
					$ret_url = 'activateMenbership';
					$this->error('1卡信息或密码有误，请重新绑定', $ret_url );
				}
		
			} else {
				//Log::write('get info error: 卡信息或密码有误' );
				$ret_url = 'activateMenbership';
				$this->error('2卡信息或密码有误，请重新绑定', $ret_url );
			}
		
		} else {
			//Log::write('get info error:' . $result['errormsg']);
			$ret_url = 'activateMenbership';
			$this->error('3卡信息有误，请重新绑定', $ret_url );
		}
		
	}
	/**
	 * 解绑
	 * */
	public function release_bound(){
		
		$uid = $this->visitor->info ['id'];
		$user = M('user')->where('id = '. $uid)->find();
		$card_num =  $user['card_num'];
		$data['bindstatus'] = 0;
		$userinfo = M('user')->where('id = '. $uid)->save($data);
		//Log::write(M('user')->getLastSql(), 'DEBUG');
		if($userinfo){
			$list =  M('card')->where('card_num='. $card_num)->delete();
			//Log::write(M('card')->getLastSql(), 'DEBUG');
			if($list){
					$data = array('url'=>U('my/index',array('sid'=>$this->shopId)));
					$this->ajaxReturn(1,'解除绑定成功',$data);
			}else{
					$this->ajaxReturn(0,'会员卡不存在');
			}
		}else{
			$this->ajaxReturn(0,'解绑失败，请重试。');
		}
	}
	
}