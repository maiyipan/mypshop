<?php

class UserAction extends userbaseAction {
	
	public function ajaxlogin() {
		
		$user_name = $_POST ['user_name'];
		$password = $_POST ['password'];
		
		$user = M ( 'user' );
		$users = $user->field ( 'id,username' )->where ( "username='" . $user_name . "' and password='" . md5 ( $password ) . "'" )->find ();
		if (is_array ( $users )) {
			$data = array ('status' => 1 );
		
		} else {
			$data = array ('status' => 0 );
		}
		
		echo json_encode ( $data );
		exit ();
	
	
	}
	/**
	 * 用户登陆
	 */
	public function login() {
		//Log::write ( "login " . C ( 'pin_integrate_code' ) );
		$this->visitor->is_login && $this->redirect ( 'user/index' );
		if (IS_POST) {
			$username = $this->_post ( 'username', 'trim' );
			$password = $this->_post ( 'password', 'trim' );
			$remember = $this->_post ( 'remember' );
			if (empty ( $username )) {
				IS_AJAX && $this->ajaxReturn ( 0, L ( 'please_input' ) . L ( 'password' ) );
				$this->error ( L ( 'please_input' ) . L ( 'username' ) );
			}
			if (empty ( $username )) {
				IS_AJAX && $this->ajaxReturn ( 0, L ( 'please_input' ) . L ( 'password' ) );
				$this->error ( L ( 'please_input' ) . L ( 'password' ) );
			}
			//连接用户中心
			$passport = $this->_user_server ();
			$uid = $passport->auth ( $username, $password );
			if (! $uid) {
				IS_AJAX && $this->ajaxReturn ( 0, $passport->get_error () );
				$this->error ( $passport->get_error () );
			}
			//登陆
			$this->visitor->login ( $uid, $remember );
			//登陆完成钩子
			$tag_arg = array ('uid' => $uid, 'uname' => $username, 'action' => 'login' );
			tag ( 'login_end', $tag_arg );
			//同步登陆
			$synlogin = $passport->synlogin ( $uid );
			if (IS_AJAX) {
				$this->ajaxReturn ( 1, L ( 'login_successe' ) . $synlogin );
			} else {
				//跳转到登陆前页面（执行同步操作）
				$ret_url = $this->_post ( 'ret_url', 'trim' );
				$this->success ( L ( 'login_successe' ) . $synlogin, $ret_url );
			}
		} else {
			//Log::write ( "loger else" );
			/* 同步退出外部系统 */
			if (! empty ( $_GET ['synlogout'] )) {
				$passport = $this->_user_server ();
				$synlogout = $passport->synlogout ();
			}
			if (IS_AJAX) {
				$resp = $this->fetch ( 'dialog:login' );
				$this->ajaxReturn ( 1, '', $resp );
			} else {
				//Log::write ( "loger else else" );
				//来路
				$ret_url = isset ( $_SERVER ['HTTP_REFERER'] ) ? $_SERVER ['HTTP_REFERER'] : __APP__;
				$this->assign ( 'ret_url', $ret_url );
				$this->assign ( 'synlogout', $synlogout );
				$this->_config_seo ();
				$this->display ();
			}
		}
	}
	
	public function addaddress() {
		if (IS_POST) {
			$user_address = M ( 'user_address' );
			
			$consignee = $this->_post ( 'consignee', 'trim' );
			$sheng = $this->_post ( 'sheng', 'trim' );
			$shi = $this->_post ( 'shi', 'trim' );
			$qu = $this->_post ( 'qu', 'trim' );
			$address = $this->_post ( 'address', 'trim' );
			$phone_mob = $this->_post ( 'phone_mob', 'trim' );
			
			$data ['uid'] = $this->visitor->info ['id'];
			$data ['consignee'] = $consignee;
			$data ['sheng'] = $sheng;
			$data ['shi'] = $shi;
			$data ['qu'] = $qu;
			$data ['address'] = $address;
			$data ['mobile'] = $phone_mob;
			
			//echo $this->visitor->info['id'];
			

			if ($user_address->data ( $data )->add () !== false) {
				$this->redirect ( 'user/address' );
			}
		
		}
		$this->display ();
	}
	
	/**
	 * 用户退出
	 */
	public function logout() {
		$this->visitor->logout ();
		//同步退出
		$passport = $this->_user_server ();
		$synlogout = $passport->synlogout ();
		//跳转到退出前页面（执行同步操作）
		$this->success ( L ( 'logout_successe' ) . $synlogout, U ( 'user/index' ) );
	}
	
	/**
	 * 用户绑定
	 */
	public function binding() {
		$user_bind_info = object_to_array ( cookie ( 'user_bind_info' ) );
		$this->assign ( 'user_bind_info', $user_bind_info );
		$this->_config_seo ();
		$this->display ();
	}
	
	/**
	 * 用户注册
	 */
	public function register() {
		$this->visitor->is_login && $this->redirect ( 'user/index' );
		if (IS_POST) {
			
			//方式
			/*   $type = $this->_post('type', 'trim', 'reg');
            if ($type == 'reg') {
                //验证
                $agreement = $this->_post('agreement');
                !$agreement && $this->error(L('agreement_failed'));

                $captcha = $this->_post('captcha', 'trim');
                if(session('captcha') != md5($captcha)){
                    $this->error(L('captcha_failed'));
                }
            }*/
			$username = $this->_post ( 'user_name', 'trim' );
			$email = $this->_post ( 'email', 'trim' );
			$password = $this->_post ( 'password', 'trim' );
			$repassword = $this->_post ( 'password_confirm', 'trim' );
			if ($password != $repassword) {
				$this->error ( L ( 'inconsistent_password' ) ); //确认密码
			}
			$gender = $this->_post ( 'gender', 'intval', '0' );
			//用户禁止
			$ipban_mod = D ( 'ipban' );
			$ipban_mod->clear (); //清除过期数据
			$is_ban = $ipban_mod->where ( "(type='name' AND name='" . $username . "') OR (type='email' AND name='" . $email . "')" )->count ();
			
			$is_ban && $this->error ( L ( 'register_ban' ) );
			
			//连接用户中心
			$passport = $this->_user_server ();
			//注册
			$uid = $passport->register ( $username, $password, $email, $gender );
			
			
			
			! $uid && $this->error ( $passport->get_error () );
			//第三方帐号绑定
			/*   if (cookie('user_bind_info')) {
                $user_bind_info = object_to_array(cookie('user_bind_info'));
                $oauth = new oauth($user_bind_info['type']);
                $bind_info = array(
                    'pin_uid' => $uid,
                    'keyid' => $user_bind_info['keyid'],
                    'bind_info' => $user_bind_info['bind_info'],
                );
                $oauth->bindByData($bind_info);
                //临时头像转换
                $this->_save_avatar($uid, $user_bind_info['temp_avatar']);
                //清理绑定COOKIE
                cookie('user_bind_info', NULL);
            }*/
			//注册完成钩子
			$tag_arg = array ('uid' => $uid, 'uname' => $username, 'action' => 'register' );
			tag ( 'register_end', $tag_arg );
			//登陆
			$this->visitor->login ( $uid );
			//登陆完成钩子
			$tag_arg = array ('uid' => $uid, 'uname' => $username, 'action' => 'login' );
			tag ( 'login_end', $tag_arg );
			//同步登陆
			$synlogin = $passport->synlogin ( $uid );
			$this->redirect ( 'user/index' );
			// $this->success(L('register_successe').$synlogin, U('user/index'));
			

			exit ();
		} else {
			//关闭注册
			if (! C ( 'pin_reg_status' )) {
				$this->error ( C ( 'pin_reg_closed_reason' ) );
			}
			$this->_config_seo ();
			$this->display ();
		}
	}
	
	/**
	 * 第三方头像保存
	 */
	private function _save_avatar($uid, $img) {
		//获取后台头像规格设置
		$avatar_size = explode ( ',', C ( 'pin_avatar_size' ) );
		//会员头像保存文件夹
		$avatar_dir = C ( 'pin_attach_path' ) . 'avatar/' . avatar_dir ( $uid );
		! is_dir ( $avatar_dir ) && mkdir ( $avatar_dir, 0777, true );
		//生成缩略图
		$img = C ( 'pin_attach_path' ) . 'avatar/temp/' . $img;
		foreach ( $avatar_size as $size ) {
			Image::thumb ( $img, $avatar_dir . md5 ( $uid ) . '_' . $size . '.jpg', '', $size, $size, true );
		}
		@unlink ( $img );
	}
	
	/**
	 * 用户消息提示 
	 */
	public function msgtip() {
		$result = D ( 'user_msgtip' )->get_list ( $this->visitor->info ['id'] );
		$this->ajaxReturn ( 1, '', $result );
	}
	
	/**
	 * 基本信息修改
	 */
	public function index() {
		//Log::write ( "index" );
		$item_order = M ( 'item_order' );
		$order_detail = M ( 'order_detail' );
		if (! isset ( $_GET ['status'] )) {
			$status = 1;
		} else {
			$status = $_GET ['status'];
		}
		
		$item_orders = $item_order->order ( 'id desc' )->where ( 'status=' . $status . ' and userId=' . $this->visitor->info ['id'] )->select ();
		foreach ( $item_orders as $key => $val ) {
			$order_details = $order_detail->where ( "orderId='" . $val ['orderId'] . "'" )->select ();
			foreach ( $order_details as $val ) {
				$items = array ('title' => $val ['title'], 'img' => $val ['img'], 'price' => $val ['price'], 'quantity' => $val ['quantity'], 'itemId' => $val ['itemId'] );
				$item_orders [$key] ['items'] [] = $items;
			}
		}
		
		$this->assign ( 'item_orders', $item_orders );
		$this->assign ( 'status', $status );
		$this->assign ( 'currentid', 4);
		$this->_config_seo ();
		$this->display ();
	}
	
	/**
	 * 修改头像
	 */
	public function upload_avatar() {
		
		if (! empty ( $_FILES ['avatar'] ['name'] )) {
			//会员头像规格
			$avatar_size = explode ( ',', C ( 'pin_avatar_size' ) );
			//回去会员头像保存文件夹
			$uid = abs ( intval ( $this->visitor->info ['id'] ) );
			$suid = sprintf ( "%09d", $uid );
			$dir1 = substr ( $suid, 0, 3 );
			$dir2 = substr ( $suid, 3, 2 );
			$dir3 = substr ( $suid, 5, 2 );
			$avatar_dir = $dir1 . '/' . $dir2 . '/' . $dir3 . '/';
			//上传头像
			$suffix = '';
			foreach ( $avatar_size as $size ) {
				$suffix .= '_' . $size . ',';
			}
			$result = $this->_upload ( $_FILES ['avatar'], 'avatar/' . $avatar_dir, array ('width' => C ( 'pin_avatar_size' ), 'height' => C ( 'pin_avatar_size' ), 'remove_origin' => true, 'suffix' => trim ( $suffix, ',' ), 'ext' => 'jpg' ), md5 ( $uid ) );
			if ($result ['error']) {
				$this->ajaxReturn ( 0, $result ['info'] );
			} else {
				$data = __ROOT__ . '/data/upload/avatar/' . $avatar_dir . md5 ( $uid ) . '_' . $size . '.jpg?' . time ();
				$this->ajaxReturn ( 1, L ( 'upload_success' ), $data );
			}
		} else {
			$this->ajaxReturn ( 0, L ( 'illegal_parameters' ) );
		}
	}
	
	/**
	 * 修改密码
	 */
	public function password() {
		if (IS_POST) {
			$oldpassword = $this->_post ( 'oldpassword', 'trim' );
			$password = $this->_post ( 'password', 'trim' );
			$repassword = $this->_post ( 'repassword', 'trim' );
			! $password && $this->error ( L ( 'no_new_password' ) );
			$password != $repassword && $this->error ( L ( 'inconsistent_password' ) );
			$passlen = strlen ( $password );
			if ($passlen < 6 || $passlen > 20) {
				$this->error ( 'password_length_error' );
			}
			//连接用户中心
			$passport = $this->_user_server ();
			$result = $passport->edit ( $this->visitor->info ['id'], $oldpassword, array ('password' => $password ) );
			if ($result) {
				$msg = array ('status' => 1, 'info' => L ( 'edit_password_success' ) );
			} else {
				$msg = array ('status' => 0, 'info' => $passport->get_error () );
			}
			$this->assign ( 'msg', $msg );
		}
		$this->_config_seo ();
		$this->display ();
	}
	
	
	public function edit_address() {
		$user_address_mod = M ( 'user_address' );
		$id = $this->_get ( 'id', 'intval' );
		$info = $user_address_mod->find ( $id );
		
		$this->assign ( 'info', $info );
		$this->display ();
	}
	
	/**
	 * 收货地址
	 */
	public function address() {
		$user_address_mod = M ( 'user_address' );
		$id = $this->_get ( 'id', 'intval' );
		$type = $this->_get ( 'type', 'trim', 'edit' );
		if ($id) {
			if ($type == 'del') {
				$user_address_mod->where ( array ('id' => $id, 'uid' => $this->visitor->info ['id'] ) )->delete ();
				$msg = array ('status' => 1, 'info' => L ( 'delete_success' ) );
				$this->assign ( 'msg', $msg );
			} else {
				$info = $user_address_mod->find ( $id );
				$this->assign ( 'info', $info );
			}
		}
		if (IS_POST) {
			$consignee = $this->_post ( 'consignee', 'trim' );
			$address = $this->_post ( 'address', 'trim' );
			//   $zip = $this->_post('zip', 'trim');
			$mobile = $this->_post ( 'phone_mob', 'trim' );
			$sheng = $this->_post ( 'sheng', 'trim' );
			$shi = $this->_post ( 'shi', 'trim' );
			$qu = $this->_post ( 'qu', 'trim' );
			$id = $this->_post ( 'id', 'intval' );
			if ($id) {
				$result = $user_address_mod->where ( array ('id' => $id, 'uid' => $this->visitor->info ['id'] ) )->save ( array ('consignee' => $consignee, 'address' => $address, // 'zip' => $zip,
'mobile' => $mobile, 'sheng' => $sheng, 'shi' => $shi, 'qu' => $qu ) );
				if ($result) {
					$msg = array ('status' => 1, 'info' => L ( 'edit_success' ) );
				} else {
					$msg = array ('status' => 0, 'info' => L ( 'edit_failed' ) );
				}
			} else {
				$result = $user_address_mod->add ( array ('uid' => $this->visitor->info ['id'], 'consignee' => $consignee, 'address' => $address, 'zip' => $zip, 'mobile' => $mobile ) );
				if ($result) {
					$msg = array ('status' => 1, 'info' => L ( 'add_address_success' ) );
				} else {
					$msg = array ('status' => 0, 'info' => L ( 'add_address_failed' ) );
				}
			}
			$this->assign ( 'msg', $msg );
		}
		
		$address_list = $user_address_mod->where ( array ('uid' => $this->visitor->info ['id'] ) )->select ();
		$this->assign ( 'address_list', $address_list );
		$this->_config_seo ();
		$this->display ();
	}
	
	/**
	 * 检测用户
	 */
	public function ajax_check() {
		$type = $this->_get ( 'type', 'trim', 'email' );
		$user_mod = D ( 'user' );
		switch ($type) {
			case 'email' :
				$email = $this->_get ( 'J_email', 'trim' );
				$user_mod->email_exists ( $email ) ? $this->ajaxReturn ( 0 ) : $this->ajaxReturn ( 1 );
				break;
			
			case 'username' :
				$username = $this->_get ( 'J_username', 'trim' );
				$user_mod->name_exists ( $username ) ? $this->ajaxReturn ( 0 ) : $this->ajaxReturn ( 1 );
				break;
		}
	}
	
	
	/**
	 * wechat getopenid and login or 
	 */
	public function weixinLogin() {
		$ip = $_SERVER ["REMOTE_ADDR"];
		//Log::write('ip>>>>' .  $ip, 'DEBUG');
		//$shopid = $this->shopId;
		$shopid = $_GET['sid'];
		//Log::write('shopId>>>>' .  $shopid,'DEBUG');
		Vendor ( 'WeiXin.WeiXinPubHelper' );
		$getOpenid = new \GetOpenid ();
		
		$shopModel = D('shop');
		$sid = $shopModel->getUidForP($shopid);
		//Log::write('weixinLogin uid>>'. $sid);
		
		/* if (false === $shopconf = F('shop')) {
			$shopconf = D('shop') ->shop_cache();
		}
		C($shopconf); */
		
		
		$appid = C ( 'spconf_appid_'. $sid);
		$appsecret = C ( 'spconf_appsecret_'. $sid);
		//Log::write('appid >>'. $appid);
		//Log::write('$appsecret >>'. $appsecret);
		
		$sessionid  = session_id();
		$currentUrl = F("$sessionid");
		//Log::write('$currentUrl:'. $currentUrl);
		$this->visitor->is_login && $this->redirect ( 'index/index' );
		$getOpenid->setAppid ( $appid );
		$getOpenid->setAppsecret ( $appsecret );
		
		// 来路
		// =========步骤1：网页授权获取用户openid============
		// 通过code获得openid
		/**
		 * // 非微信浏览器禁止浏览
		 echo "亲，只能在微信内访问...";
		 exit;
		 }
		if (strpos ( $ip, '127.0.0' ) !== FALSE || strpos ( $ip, '192.168' ) !== FALSE ) { //|| $_SERVER['SERVER_NAME'] == 'jx.i-lz.cn'
		 * */
		 header("Content-type: text/html; charset=utf-8");
		 $user_agent = $_SERVER['HTTP_USER_AGENT'];
		 $debug = $_GET ['debug'];
		 if (strpos($user_agent, 'MicroMessenger') === false || strpos ( $ip, '127.0.0' ) !== FALSE || strpos ( $ip, '192.168' ) !== FALSE ) { //if (strpos($user_agent, 'MicroMessenger') === false) {   
		//if ($debug && $debug == 'debug'){
			//Log::write ( 'local test', 'INFO' ); 
			$openid = 'ou-gAj64Z360XMK3pD9x3i79Y-89434';
		} else {  
			if (! isset ( $_GET ['code'] )) {
				// 触发微信返回code码
				$callback_url = C ( 'pin_baseurl' ) . "/user/weixinLogin/sessinid/" . session_id ().'/sid/'.$shopid;
				$url = $getOpenid->createOauthUrlUserInfoForCode ( $callback_url );
				//Log::write ( 'oauth url:' . $url, 'DEBUG' );
				Header ( "Location: $url" );
				exit ();
			} else {
				// 获取code码，以获取openid
				$code = $_GET ['code'];
				//Log::write('oauth coe ---' . $code);
				$sessionid = $_GET ['sessinid'];
				$ret_url = F ( "$sessionid" );
				$getOpenid->setCode ( $code );
				$data = $getOpenid->getOpenId ();
				$openid = $data ['openid'];
				// 登录时缓存 网页授权的accesstoken
				$_SESSION ['web_access_token'] = $data;
			}
		} 
		
		//Log::write ( 'login openid:'. $openid );
		$password = $openid;
		$user = M ( 'user' );
		$wechat_user = M ( 'wechat_user' );
		$userWhere['openid'] = $openid;
		$userWhere['uid'] = $sid; 
		$wechat_users = $wechat_user->field ( 'id,nickname,openid' )->where ($userWhere)->find ();
		//Log::write($wechat_user->getLastSql(), 'DEBUG');
		if (is_array ( $wechat_users )) {
			$username = $wechat_users ['nickname'];
			$data = array (
					'status' => 1 
			);
			$passport = $this->_user_server ();
			//Log::write('uid>>>' . $username . '>>>' . $password);
			$uid = $passport->auth ( $username, $password);
			//Log::write('uid>>>' . $uid);
			if (! $uid) {
				IS_AJAX && $this->ajaxReturn ( 0, $passport->get_error () );
				$this->error ( $passport->get_error () );
			}
			session ( $uid . 'openid', $wechat_users );
			// 登陆
			$this->visitor->login ( $uid, $remember );
			// 登陆完成钩子
			$tag_arg = array (
					'uid' => $uid,
					'uname' => $username,
					'action' => 'login' 
			);
			// tag ( 'login_end', $tag_arg );
			// 同步登陆
			$synlogin = $passport->synlogin ( $uid );
			if (IS_AJAX) {
				$this->ajaxReturn ( 1, L ( 'login_successe' ) . $synlogin );
			} else {
			 
				// 跳转到登陆前页面（执行同步操作）
				$ret_url = $currentUrl; // $this->_post ( 'ret_url', 'trim' );
				if (empty ( $ret_url )) {
					$ret_url = U('index/index', array('sid'=> $shopid));
				}
				//$ret_url = U('index/index', array('sid'=> $shopid));
				 
				//Log::write('ret_url:' .$ret_url ); 
				//$this->redirect ( $ret_url );
				redirect ( $ret_url );
			}
			exit ();
		} else {
			// 需要注册时获取用户信息
			$access_token = $data ['access_token'];
			// 获取用户信息
			$getOpenid->setOpenid ( $openid );
			$getOpenid->setAccess_token ( $access_token );
			
			$userInfo = $getOpenid->getUserInfo ();
			$username = $userInfo ['nickname'];
			$gender = $userInfo ['sex'];
			$headimgurl = $userInfo ['headimgurl'];
			$data = array (
					'status' => 0 
			);
			$email = "i_lz_test@ilz.cn";
// 			$gender = "0"; 

			if (strpos($user_agent, 'MicroMessenger') === false)  {
				// if ($ip == '192.168.22.70' || $ip=='localhost' || $ip=='127.0.0.1' ) {
				$gender = "0";
				$username = '大军本地测试';
				$headimgurl = 'http://wx.qlogo.cn/mmopen/x1rhSIJx35zNiaMlpicYlQxeFvaHsCbPo3YcNiaLj4dT8CPjb7HHjUPuLYYgTrnNHD1EG9B4d5UDXa1lJ2kYBaOPJj5qLZ8qnIj/0';
				$userInfo ['openid'] = $openid;
				$userInfo ['sex'] = $gender;
				$userInfo ['nickname'] = $username;
				$userInfo ['headimgurl'] = $headimgurl;
				$userInfo['uid'] = $sid;
				
			} 
			// 用户禁止
			$ipban_mod = D ( 'ipban' );
			$ipban_mod->clear (); // 清除过期数据
			$is_ban = $ipban_mod->where ( "(type='name' AND name='" . $username . "') OR (type='email' AND name='" . $email . "')" )->count ();
			$is_ban && $this->error ( L ( 'register_ban' ) );
			// 连接用户中心
			$passport = $this->_user_server ();
			// 注册
			$uid = $passport->register ( $username, $password, $email, $gender, $headimgurl );
			
			//Log::write('the zhuce uid>>>>' . $uid);
			//保存shopid
			$userTm['uid'] = $sid;
			$userTm['id'] = $uid;
			M('user')->data($userTm)->save();
			session ( $uid . 'openid', $userInfo );
			$userInfo ['id'] = $uid;
			$userInfo ['uid'] = $sid;
			$wechat_user->data ( $userInfo )->add ();
			//Log::write ( $wechat_user->getLastSql () );
			
			! $uid && $this->error ( $passport->get_error () );
			
			// 注册完成钩子
			$tag_arg = array (
					'uid' => $uid,
					'uname' => $username,
					'action' => 'register' 
			);
			// tag ( 'register_end', $tag_arg );
			// 登陆
			$this->visitor->login ( $uid );
			// 登陆完成钩子
			$tag_arg = array (
					'uid' => $uid, 
					'uname' => $username,
					'action' => 'login' 
			);
			// tag ( 'login_end', $tag_arg );
			// 同步登陆
			$synlogin = $passport->synlogin ( $uid );
			if (IS_AJAX) {
				$this->ajaxReturn ( 1, L ( 'login_successe' ) . $synlogin );
			} else {
				$ret_url = $currentUrl; // $this->_post ( 'ret_url', 'trim' );
				if (empty ( $ret_url )) {
					$ret_url = U('index/index', array('sid'=> $shopid));
				}
				//Log::write('ret_url:' .$ret_url ); 
				redirect ( $ret_url );
			}
			exit ();
		}
	}
	
	public function getHeadImg(){
		try {
		
			$userid = $_GET['userid'];
			$wechat_user = M('wechat_user');
			$wechat_user_info =  $wechat_user->field('headimgurl')->where("openid='" . $userid ."'" )->find();
			if ($wechat_user_info){
				//Log::write($wechat_user_info['headimgurl']);
				$url = $wechat_user_info['headimgurl'];
				$curl = curl_init($url);
				$filename = date("Ymdhis").".jpg";
				curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
				$imageData = curl_exec($curl);
				curl_close($curl);
				
				header( "Content-type: image/jpeg");
				echo $imageData;
			}
		
		} catch (Exception $e) {
			//Log::write($e);
			
		}
	}
	
	public function sendCode() {
		$uid = $this->visitor->info ['id'];
		$phone = $this->_post ( 'phone' );
		$code = $this->generate_promotion_code ( 1, $exclude_codes_array, 6 );
		echo $code [0];
		
		$data = array ();
		$data ['phone'] = $phone;
		$data ['code'] = $code [0];
		$data ['status'] = 0;
		$_SESSION ['phone'] [$uid] = $data;
		
		/*
		 * $result = sendDuanXin('18613022123', $code[0]);
		 * if ($result['result'] == 'false'){
		 * $data = array ('status' => 0 );
		 * } else {
		 *
		 * $data = array ('status' => 1 );
		 * }
		 */
		$data = array (
				'status' => 0 
		);
		echo json_encode ( $data );
		exit ();
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
	public function test() {
		$passport = $this->_user_server ();
		// 注册
		$username = '米斯特大军';
		$password = 'oNLOos6c2Zub0pgkUj1E_4tAS5ik';
		$email = '33';
		$gender = 0;
		$headimgurl = 'dddd';
		$uid = $passport->register ( $username, $password, $email, $gender, $headimgurl );
		dump($uid);
	}
	public function testSend() {
		dump( C('pin_mail_server'));
		
		dump( D('mail_queue')->sendTest());
	}
	public function saveMail() {
		/* $setting['mail_server']['mode'] = 'smtp';
		$setting['mail_server']['host'] = 'smtp.exmail.qq.com';
		$setting['mail_server']['port'] = '25';
		$setting['mail_server']['from'] = 'jimyan@i-lz.cn';
		$setting['mail_server']['auth_username'] = 'jimyan@i-lz.cn';
		$setting['mail_server']['auth_password'] = '密码'; */
		$setting['mail_server']['mode'] = 'smtp';
		$setting['mail_server']['host'] = 'smtp-ent.21cn.com';
		$setting['mail_server']['port'] = '25';
		$setting['mail_server']['from'] = 'public@jiaxianfarm.com';
		$setting['mail_server']['auth_username'] = 'public@jiaxianfarm.com';
		$setting['mail_server']['auth_password'] = 'gk888888';
		foreach ($setting as $key => $val) {
			$val = is_array($val) ? serialize($val) : $val;
			D('setting')->where(array('name' => $key))->save(array('data' => $val));
			dump(D('setting')->getLastSql(), 'DEBUG');
		}
	}
	
}