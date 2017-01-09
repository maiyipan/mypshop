<?php
header ( "content-type:text/html;charset=utf-8" );
class WeixinAction extends Action {
	
	
	public function _initialize() {
		$uid = $_GET ['uid'];
		$this->_sid =$uid;
	}
	
	
	/**
	 * 多客服
	 */
	public function trcs($fromUsername, $toUsername, $time) {
		$textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
					</xml>";
		$msgType = "transfer_customer_service";
		$resultStr = sprintf ( $textTpl, $fromUsername, $toUsername, $time, $msgType );
		//Log::write ( 'kfhf>>' . $resultStr, 'DEBUG' );
		echo $resultStr;
		exit ();
	}
	/**
	 * 单文本
	 *
	 * @param unknown $key_list        	
	 * @param unknown $fromUsername        	
	 * @param unknown $toUsername        	
	 * @param unknown $time        	
	 * @param unknown $contentStr
	 *        	关键字object 已经是文本回复，需要根据contentStr['media_id']查取对应的回复
	 *        	
	 */
	public function wenben($fromUsername, $toUsername, $time, $contentStr) {
		$log = $contentStr;
		// ////文本链接的处理/ ///
		$str = $contentStr;
		$media = D ( 'media' )->getMediaById ( $contentStr ['media_id'] );
		if ($media ['media_type'] == 0) { // 文本回复
			if ($media ['item'] [0] ['content'] == 'kf') {
				//Log::write ( 'kf', 'DEBUG' );
				$this->trcs ( $fromUsername, $toUsername, $time );
			}
		}
		$reg = '/\shref=[\'\"]([^\'"]*)[\'"]/i';
		preg_match_all ( $reg, $str, $out_ary ); // 正则：得到href的地址
		$src_ary = $out_ary [1];
		if (! empty ( $src_ary )) // 存在
{
			$comment = $src_ary [0];
			if (stristr ( $comment, $_SERVER ['SERVER_NAME'] )) {
				if (stristr ( $comment, "?" )) {
					$links = $comment . "&key=" . $fromUsername;
					$contentStr = str_replace ( $comment, $links, $str );
				} else {
					$links = $comment . "?key=" . $fromUsername;
					$contentStr = str_replace ( $comment, $links, $str );
				}
			}
		}
		// ////文本链接的处理 END////
		$textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
					</xml>";
		$msgType = "text";
		$resultStr = sprintf ( $textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr );
		$this->sendlog ( $fromUsername, $resultStr );
		//Log::write ( "the echo:" . $resultStr );
		echo $resultStr;
		return $log;
	}
	// 关注回复
	public function subscribeReply($fromUsername, $toUsername, $time) {
		$key_word = M ( 'keyword' );
		$where['uid'] =  $this->_sid;
		$where['isfollow'] = 1;
		$key_list = $key_word->where ($where )->find ();
		if (is_array ( $key_list )) { // 是否存在
			$this->replyByMedia ( $fromUsername, $toUsername, $time, $key_list ['media_id'] );
		} else {
			$log = $this->wenbenNew ( $fromUsername, $toUsername, $time, "" );
			//$this->receivelog ( $postObj, $log );
		}
		exit ();
	}
	
	/**
	 * 回复：自动根据素材的类型回复文本或者图文
	 */
	public function replyByMedia($fromUsername, $toUsername, $time, $mediaid,$postObj) {
		$media = D ( 'media' )->getMediaById ( $mediaid );
		if ($media ['media_type'] == 0) { // 文本回复
			$log = $this->wenbenNew ( $fromUsername, $toUsername, $time, $media ['item'] [0] ['content'] );
			$this->receivelog ( $postObj, $log );
		} else { // 多图文
			$this->tuwen ( $fromUsername, $toUsername, $time, $media ,$postObj);
		}
	}
	
	// 组装多图
	public function tuwen($fromUsername, $toUsername, $time, $media,$postObj) {
		$log = "图文回复";
		// $items=M('media_item')->where("media_id=". $key_list['media_id'])->select();
		$items = $media ['item'];
		$size = count ( $items );
		$textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<ArticleCount>%s</ArticleCount>
					<Articles>";
		// dump(M('media_item')->getLastSql(), 'DEBUG');
		// exit();
		for($i = 0; $i < $size; $i ++) {
			if (stristr ( $items [$i] ['picurl'], $_SERVER ['SERVER_NAME'] )) {
				$images = $items [$i] ['picurl'];
			} else {
				$images = "http://" . $_SERVER ['SERVER_NAME'] . '/' . $items [$i] ['picurl'];
			}
			
			
			$url;
			if ($items[$i]['isurl'] == 0) {
				$url = $items [$i] ['url'];
			} else {
				$url = "http://" . $_SERVER ['SERVER_NAME'] . '/article/index/id/' . $items [$i] ['id'];
			}
			
			$textTpl .= " <item>
						<Title><![CDATA[" . $items [$i] ['title'] . "]]></Title>
						<Description><![CDATA[" . $titles [$i] . "]]></Description>
						<PicUrl><![CDATA[" . $images . "]]></PicUrl>
						<Url><![CDATA[" . $url . "]]></Url>
						</item>";
			
			
		}
		$textTpl .= "</Articles>
						</xml>";
		$msgType = "news";
		$resultStr = sprintf ( $textTpl, $fromUsername, $toUsername, $time, $msgType, $size );
		$this->sendlog ( $fromUsername, $resultStr ); // up_log日志存储
		echo $resultStr;
		return $log;
	}
	// 主程序
	public function index() {
		// 读取缓存
		if (false === $shopconf = F ( 'shop' )) {
			$conf = D ( 'shop' )->shop_cache ();
		}
		C ( F ( 'shop' ) );
		
		$uid = $_GET ['uid'];
		//Log::write ( 'uid-- :' . $uid );
		//Log::write ( 'the rquest type :' . IS_POST );
		import ( 'Think.ORG.Weixin' ); // 导入微信类
		
		$wechat = new Weixin ();
		$wechat->valid ();
		
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];
		//Log::write ( 'postStr' . $postStr, 'DEBUG' );
		
		try {
			if (! empty ( $postStr )) {
				$key_word = M ( 'keyword' );
				$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
				$fromUsername = trim ( $postObj->FromUserName ); // 发送方帐号（一个OpenID）
				$toUsername = $postObj->ToUserName; // 开发者微信号
				$keyword = trim ( $postObj->Content ); // 用户发来的信息
				$RX_TYPE = trim ( $postObj->MsgType ); // 类型
				$EventKey = trim ( $postObj->EventKey ); // 事件KEY值
				$Event = $postObj->Event; // 事件类型
				$time = time ();
				
				if ($RX_TYPE == 'image') { // 接收类型为图片
					$this->defaultReply ( $fromUsername, $toUsername, $time, $postObj );
				} else if ($RX_TYPE == 'voice') { // 接收类型为语音
					$this->defaultReply ( $fromUsername, $toUsername, $time, $postObj );
				} else if ($RX_TYPE == 'video' || $RX_TYPE == 'shortvideo') { // 接收类型为视频
				                                                              // 回复一个视频
					$this->defaultReply ( $fromUsername, $toUsername, $time, $postObj );
				} else if ($RX_TYPE == 'location') { // 接收类型为位置
					$this->defaultReply ( $fromUsername, $toUsername, $time, $postObj );
				} elseif ($RX_TYPE == 'link') {
					$this->defaultReply ( $fromUsername, $toUsername, $time, $postObj );
				} else if ($RX_TYPE == 'event') {
					if ($Event == 'CLICK') { // **自定义点击事件**//
						$this->sendEvent ( $EventKey, $key_word, $fromUsername, $toUsername, $time, $postObj );
					} else if ($Event == 'VIEW') { // 跳转链接
						$log = "跳转链接";
						$this->receivelog ( $postObj, $log );
						return null;
					} else if ($Event == 'SCAN') {
						$scan = $postObj->EventKey;
						$this->sceneEvent ( $fromUsername, $toUsername, $time, $postObj );
					} else if ($Event == 'subscribe') {
						$this->subscribeEvent ( $fromUsername, $toUsername, $time, $postObj, $uid );
					} else if ($Event == 'unsubscribe') { // 取消订阅
						$this->unsubscribeEvent($fromUsername, $toUsername, $time, $uid);
					}
				} else if (! empty ( $keyword )) {
					try {
						//Log::write ( 'keyword>>>>' . $keyword, 'DEBUG' );
						// 1 查询关键词
						unset($where);
						$where['kyword'] = $keyword;
						$where['uid'] = $uid;
						$key_list = $key_word->where ($where )->find ();
						////Log::write('test fin>>' . $key_word->getLastSql(), 'DEBUG',  'DEBUG'); 
						if (is_array ( $key_list )) {
							$this->replyByMedia ( $fromUsername, $toUsername, $time, $key_list ['media_id'] ,$postObj);
						} else { // 默认回复
							$this->defaultReply ( $fromUsername, $toUsername, $time ,$postObj);
						}
					} catch ( Exception $e ) {
						//Log::write ( 'error: ' . $e );
						$this->wenben ( $fromUsername, $toUsername, $time, "2输入有误，请从新输入！" );
						exit ();
					}
				} else {
					$this->wenben ( $fromUsername, $toUsername, $time, "输入有误2，请从新输入！" );
					exit ();
				}
			} else {
				// echo "";
				$this->wenben ( $fromUsername, $toUsername, $time, "输入有误1，请从新输入！" );
				exit ();
			}
		} catch ( Exception $e ) {
			//Log::write ( 'error: ' . $e );
			$this->wenben ( $fromUsername, $toUsername, $time, "1输入有误，请从新输入！" );
			exit ();
		}
	}
	// 默认自动回复消息
	public function defaultReply($fromUsername, $toUsername, $time, $postObj) {
		// 自动回复
		$key_word = M ( 'keyword' );
		$where['uid'] = $this->_sid;
		$where['ismess'] = 1;
		$key_list = $key_word->where ($where)->find ();
		if (is_array ( $key_list )) { // 是否存在
			$this->replyByMedia ( $fromUsername, $toUsername, $time, $key_list ['media_id'] ,$postObj);
		} else {
			$log = $this->wenben ( $fromUsername, $toUsername, $time, "" ,$postObj);
		}
		exit ();
	}
	// 触发事件发送消息
	public function sendEvent($EventKey, $key_word, $fromUsername, $toUsername, $time, $postObj) {
		if ($EventKey != '') {
			$key_list = $key_word->where ( "kyword='" . $EventKey . "'" )->find ();
			if (is_array ( $key_list )) {
				if ($key_list ['type'] == 1) { // 文本
				                               // $log=$this->wenben ( $fromUsername, $toUsername, $time, $key_list ['kecontent'] );
					$log = $this->wenben ( $fromUsername, $toUsername, $time, $key_list );
					$this->receivelog ( $postObj, $log );
				} else { // 图文
					$log = $this->tuwen ( $key_list, $textTpl, $fromUsername, $toUsername, $time, $count );
					$this->receivelog ( $postObj, $log );
				}
			} else {
				$this->defaultReply ( $fromUsername, $toUsername, $time, $postObj );
			}
		}
		exit ();
	}
	// 关注事件(这里添加了$uid)
	public function subscribeEvent($fromUsername, $toUsername, $time, $postObj, $uid) {
		//Log::write ( 'subscribeEvent--' . $fromUsername, 'debug' );
		$wechat_user = M ( 'wechat_user' )->field ( 'id,openid' )->where ( "openid='" . $fromUsername . "'" )->find ();
		//Log::write ( M ( 'wechat_user' )->getLastSql () );
		$wxInterface = new WeixinInterface ( $uid );
		$data = $wxInterface->userInfo ( $fromUsername, $uid );
		
		//Log::write('info>>' . $data['nickname'],'DEBUG');
		//Log::write('info>>' . $data['openid'],'DEBUG');
		$date = time ();
		$user ['username'] = trim ( $data ['nickname'] );
		$user ['password'] = md5 ( $data ['openid'] );
		$user ['email'] = 'test@i_lz.cn';
		$user ['gender'] = $data ['sex'];
		$user ['reg_time'] = $date;
		$user ['last_time'] = $date;
		$user ['headimgurl'] = $data ['headimgurl'];
		$user ['uid'] =$uid;
		if ($wechat_user) {
			//Log::write('info-->>' . $data['openid'],'DEBUG');
			$where['openid'] = $fromUsername;
			M ('wechat_user')->where($where)->data($data)->save();
			unset($where);
// 			$where['openid'] = $fromUsername;
			$where['uid'] = $uid;
			$where['id'] = $wechat_user['id'];
			M ( 'user' )->where($where)->data($user)->save();
			//Log::write(M ( 'user' )->getLastSql(), 'DEBUG');
		} else {
			//Log::write ('new user>>', 'DEBUG');
			$data['uid'] = $uid;
			$userid = M ('wechat_user' )->add($data );
			$user['id'] = $userid;
			$userid = M ('user' )->add ( $user );
		}
		
		/**
		 * 关注回复
		 */
		$this->subscribeReply ( $fromUsername, $toUsername, $time );
		exit ();
	}
	
	
	// 取消关注事件
	public function unsubscribeEvent($fromUsername, $toUsername, $time, $uid) {
		$data['subscribe'] = 0;
		$where = array(
			'openid'=>	$fromUsername
		);
		$user = M ( 'wechat_user' )->where($where )->data($data)->save();
		////Log::write("unsub>>>" .  M ( 'wechat_user' )->getLastSql(), 'DEBUG', 'DEBUG');
	}
	
	
	// 已关注扫描二维码事件
	public function sceneEvent($fromUsername, $toUsername, $time, $postObj) {
		$user = M ( 'wechat_user' )->field ( 'id,openid' )->where ( "openid='" . $fromUsername . "'" )->find ();
		//Log::write ( 'user:' . $user );
		if ($user != '') {
			$EventKey = trim ( $postObj->EventKey );
			//Log::write ( 'user:005' );
			// 暂时做默认回复
			$this->defaultReply ( $fromUsername, $toUsername, $time, $postObj );
		} else {
			$log = $this->wenben ( $fromUsername, $toUsername, $time, "您输入有误，请从新输入！" );
			$this->receivelog ( $postObj, $log );
		}
	}
	
	
	// 发送日志记录(以后可以删除)
	public function sendlog($fromUsername, $resultStr) {
		$Send = M ( 'up_log' );
		$time = time ();
		$data ['name'] = "" . $fromUsername;
		$data ['time'] = "" . $time;
		$data ['content'] = "" . $resultStr;
		$Send->add ( $data );
	}
	public function test() {
		$media = D ( 'media' )->getMediaById ( 1 );
		dump ( $media );
		dump ( $media ['item'] [0] ['content'] );
		exit ();
	}
	
	/**
	 * 单文本
	 *
	 * @param unknown $key_list        	
	 * @param unknown $fromUsername        	
	 * @param unknown $toUsername        	
	 * @param unknown $time        	
	 * @param unknown $contentStr        	
	 *
	 */
	public function wenbenNew($fromUsername, $toUsername, $time, $contentStr) {
		$log = $contentStr;
		// ////文本链接的处理/ ///
		$str = $contentStr;
		/*
		 * $reg = '/\shref=[\'\"]([^\'"]*)[\'"]/i';
		 * preg_match_all ( $reg, $str, $out_ary ); // 正则：得到href的地址
		 * $src_ary = $out_ary [1];
		 * if (! empty ( $src_ary )) // 存在
		 * {
		 * $comment = $src_ary [0];
		 * if (stristr ( $comment, $_SERVER ['SERVER_NAME'] )) {
		 * if (stristr ( $comment, "?" )) {
		 * $links = $comment . "&key=" . $fromUsername;
		 * $contentStr = str_replace ( $comment, $links, $str );
		 * } else {
		 * $links = $comment . "?key=" . $fromUsername;
		 * $contentStr = str_replace ( $comment, $links, $str );
		 * }
		 * }
		 * }
		 */
		// ////文本链接的处理 END////
		$textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
					</xml>";
		$msgType = "text";
		$resultStr = sprintf ( $textTpl, $fromUsername, $toUsername, $time, $msgType, $str );
		$this->sendlog ( $fromUsername, $resultStr );
		//Log::write ( "the echo:" . $resultStr );
		echo $resultStr;
		return $log;
	}
	
	/**
	 * 保存微信用户信息
	 * 如果以前已经关注，则更新信息，如果第一次关注，则保存信息
	 * */
	public function updateWeUser(){
		
	}
	
	
	// 接收日志记录
	public function receivelog($postObj, $log) {
		//Log::write('post>>>>>>>'.$postObj);
		// 接收到的数据存到数据库里
		$news = M ( 'receive_log' );
		// $data['touser']= "" . $postObj->ToUserName;
		$time = time ();
		$data ['fromusername'] = "" . $postObj->FromUserName;
		$data ['time'] = "" . $time;
		$data ['msgtype'] = "" . $postObj->MsgType;
		$data ['mediaid'] = "" . $postObj->MediaId;
	
		$data ['content'] = "" . $postObj->Content; // 文本
	
		$data ['picurl'] = "" . $postObj->PicUrl; // 图片地址
	
		$data ['format'] = "" . $postObj->Format; // 语音
	
		$data ['thumbmediaid'] = "" . $postObj->ThumbMediaId; // 视频
	
		$data ['location_x'] = "" . $postObj->Location_X; // 地理经度
		$data ['location_y'] = "" . $postObj->Location_Y; // 地理纬度
		$data ['lable'] = "" . $postObj->Label; // 地理位置
	
		$data ['title'] = "" . $postObj->Title; // 链接标题
		$data ['desciption'] = "" . $postObj->Description; // 链接标题
		$data ['url'] = "" . $postObj->Url; // 链接地址
	
		$data ['event'] = "" . $postObj->Event; //
		$data ['event_key'] = "" . $postObj->EventKey; //
		$data ['ticket'] = "" . $postObj->Ticket; //
		$data ['msgid'] = "" . $postObj->MsgId; //
	
		$data ['latitude'] = "" . $postObj->Latitude;
		$data ['longitude'] = "" . $postObj->Longitude;
		$data ['precision'] = "" . $postObj->Precision;
	
		$data ['reply'] = $log;
		$news->add ( $data );
		//Log::write ( "the echo:" . $news );
		return true;
	}
}