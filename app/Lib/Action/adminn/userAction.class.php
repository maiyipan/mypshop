<?php
/**
 * 用户信息管理
 */
class userAction extends backendAction
{

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('user');
    }

    protected function _search() {
        $map = array();
        if( $keyword = $this->_request('keyword', 'trim') ){
            $map['_string'] = "username like '%".$keyword."%' OR email like '%".$keyword."%'";
        }
        $this->assign('search', array(
            'keyword' => $keyword,
        ));
         $this->list_relation = true;
         
         $map['uid'] = array('in', $this->getUidList());
        return $map;
    }

    public function _before_insert($data) {
        if( ($data['password']!='')&&(trim($data['password'])!='') ){
            $data['password'] = $data['password'];
        }else{
            unset($data['password']);
        }
        $birthday = $this->_post('birthday', 'trim');
        if ($birthday) {
            $birthday = explode('-', $birthday);
            $data['byear'] = $birthday[0];
            $data['bmonth'] = $birthday[1];
            $data['bday'] = $birthday[2];
        }
        return $data;
    }

    public function _after_insert($id) {
        $img = $this->_post('img','trim');
        $this->user_thumb($id,$img);
    }

    public function _before_update($data) {
        if( ($data['password']!='')&&(trim($data['password'])!='') ){
            $data['password'] = md5($data['password']);
        }else{
            unset($data['password']);
        }
        $birthday = $this->_post('birthday', 'trim');
        if ($birthday) {
            $birthday = explode('-', $birthday);
            $data['byear'] = $birthday[0];
            $data['bmonth'] = $birthday[1];
            $data['bday'] = $birthday[2];
        }
        return $data;
    }

    public function _after_update($id){
        $img = $this->_post('img','trim');
        if($img){
            $this->user_thumb($id,$img);
        }
    }

    public function user_thumb($id,$img){
        $img_path= avatar_dir($id);
        //会员头像规格
        $avatar_size = explode(',', C('pin_avatar_size'));
        $paths =C('pin_attach_path');

        foreach ($avatar_size as $size) {
            if($paths.'avatar/'.$img_path.'/' . md5($id).'_'.$size.'.jpg'){
                @unlink($paths.'avatar/'.$img_path.'/' . md5($id).'_'.$size.'.jpg');
            }
            !is_dir($paths.'avatar/'.$img_path) && mkdir($paths.'avatar/'.$img_path, 0777, true);
            Image::thumb($paths.'avatar/temp/'.$img, $paths.'avatar/'.$img_path.'/' . md5($id).'_'.$size.'.jpg', '', $size, $size, true);
        }

        @unlink($paths.'avatar/temp/'.$img);
    }

    public function add_users(){
        if (IS_POST) {
            $users = $this->_post('username', 'trim');
            $users = explode(',', $users);
            $password = $this->_post('password', 'trim');
            $gender = $this->_post('gender', 'intavl');
            $reg_time= time();
            $data=array();
            foreach($users as $val){
                $data['password']=$password;
                $data['gender']=$gender;
                $data['reg_time']=$reg_time;
                if($gender==3){
                    $data['gender']=rand(0,1);
                }
                $data['username']=$val;
                $this->_mod->create($data);
                $this->_mod->add();
            }
            $this->success(L('operation_success'));
        } else {
            $this->display();
        }
    }

    public function ajax_upload_imgs() {
        //上传图片
        if (!empty($_FILES['img']['name'])) {
            $result = $this->_upload($_FILES['img'], 'avatar/temp/' );
            if ($result['error']) {
                $this->error($result['info']);
            }else {
                $data['img'] =  $result['info'][0]['savename'];
                $this->ajaxReturn(1, L('operation_success'), $data['img']);
            }


        } else {
            $this->ajaxReturn(0, L('illegal_parameters'));
        }
    }

    /**
     * ajax检测会员是否存在
     */
    public function ajax_check_name() {
        $name = $this->_get('username', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->name_exists($name,  $id)) {
            $this->ajaxReturn(0, '该会员已经存在');
        } else {
            $this->ajaxReturn();
        }
    }

    /**
     * ajax检测邮箱是否存在
     */
    public function ajax_check_email() {
        $name = $this->_get('email', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->email_exists($name,  $id)) {
            $this->ajaxReturn(0, '该邮箱已经存在');
        } else {
            $this->ajaxReturn();
        }
    }

    /**
     * 发送消息 
     */
    public function sendMsg(){
    	$data = array();
    	$data['touser'] = 'ou-gAj64Z360XMK3pD9x3i79Y-AE'; 
    	$data['msgtype'] = 'text';
    	$text = array ('content' => 'hi');
    	$data ['text'] = $text;
    	//Log::write("data:" . json_encode ( $data ));
    	$accountid = "test1";
    	$result = sendMsg($accountid,$data);
    	echo $result['token'];
    }
    /**
     * 发送日志 
     */
    public function log(){
    	
    	if(IS_AJAX){
    		$wechat_user = M('wechat_user')->where('id = '.$_GET['id'])->field('openid , nickname')->find();
    		$count = M('receive_log')->where(array('fromusername'=>$wechat_user['openid']))->count();
    		$spage_size = 5;// C('pin_wall_spage_size'); //每次加载个数
    		$p = $this->_get('p', 'intval', 1); //页码
    		$start = ($p - 1)*$spage_size;
    		$db_pre = C('DB_PREFIX');
    		$select = M('receive_log')->join($db_pre.'wechat_user  ON '.$db_pre.'wechat_user.openid = '.$db_pre.'receive_log.fromusername')
    								  ->where('id = '.$_GET['id'])->order('time')->limit($start.','.$spage_size);
    		$rankinglist =$select->select();//每次加载数据
    		$this->assign('data',$rankinglist);

    		$response = $this->fetch();
    		$this->ajaxReturn(1, '', $response);
    	}else {
    		$this->display();
    	}
    } 
    
    public function order(){
    	$id = $_GET['id'];
    	if(empty($id)){
    		$this->error( L ( 'illegal_parameters' ) );
    	}
    	$list=M('item_order')->where('userId='.$id)->select();
    	$this->assign('list',$list);
    	$this->display();
    }
    
    public function address(){
    	$id = $_GET['id'];
    	if(empty($id)){
    		$this->error( L ( 'illegal_parameters' ) );
    	}
    	$list=M('user_address')->where('uid='.$id)->select();
    	$this->assign('list',$list);
    	$this->display();
    }
    
    public function bind_coupon(){
    	$userid = $_POST['userid'];
    	$urlid = $_POST['urlid'];
    	if(empty($userid) || empty($urlid)){
    		$this->error( L ( 'illegal_parameters' ) );
    	}
    	$item_info = M('coupons_url')->field('url,shopid,itemtypename,typeid')-> where('urlid='.$urlid)->find();
    	if(!$item_info){
    		return;
    	}
    	$url = $item_info['url'];
    	$shopid = $item_info['shopid'];
    	$u = substr($url,strpos($url,'urlid'));
    	$params = explode("/", $u);
    	$data = array();
    	foreach ($params as $key => $val){
    		if($key % 2 == 0){
    			$k = $params[$key];
    			$v = $params[$key+1];
    			$data[$k] = $v;
    		}
    	}
    	if(!empty($data['discount'])){
    		$where = array(
    				'urlid'=>$data['urlid'],					//优惠券urlid
    				'userid'=>$userid,//用户id号
    				'discount'=>$data['discount'],
    				'begintime' => $data['begintime'],			//活动开始时间
    				'expiretime' =>$data['expiretime'] 		//活动结束时间
    		);
    	}
    	if(!empty($data['full'])||!empty($data['cut'])){
    		$where = array(
    				'urlid'=>$_GET['urlid'],					//优惠券urlid
    				'userid'=>$userid,//用户id号
    				'full'=>$data['full'],
    				'cut'=>$data['cut'],
    				'begintime' =>$data['begintime'] ,			//活动开始时间
    				'expiretime' =>$data['expiretime'] ,		//活动结束时间
    		);
    	}
    	if(!empty($data['gift'])){
    		$where = array(
    				'urlid'=>$data['urlid'],					//优惠券urlid
    				'userid'=>$userid,//用户id号
    				'gift'=>$data['gift'],
    				'begintime' => $data['begintime'],			//活动开始时间
    				'expiretime' => $data['expiretime'],		//活动结束时间
    		);
    	}
    	if(!empty($data['discount'])){
    		$where_arr = array('discount'=>$data['discount'],'urlid'=>$data['urlid']);
    		$cardID  = M('coupons_card')->where($where_arr)->max('cardid');
    	}
    	if(!empty($data['full'])||!empty($data['cut'])){
    		$cardID  = M('coupons_card')->where('full='.$data['full'].' and cut='.$data['cut'])->max('cardid');
    	}
    	if(!empty($data['gift'])){
    		$cardID  = M('coupons_card')->where('gift='.$data['gift'])->max('cardid');
    	}
    	$userinfo= $userid; // 用户id
    	$discount= $data['discount'];			//折扣
    	$full    = $data['full'];				//满
    	$cut     = $data['cut'];				//减
    	$gift    = $data['gift'];				//礼金
    	$share	 = $data['share'];
    	$cardID += $data['id'];
    	$begintime =$data['begintime'];
    	$expiretime = $data['expiretime'];
    	$urlid = $data['urlid'];
    	
    	$infos = M('coupons_card')->select();
    	$exclude_codes_array = array();
    	if($infos){
    		$x = 0;
    		foreach ($infos as $r => $infos){
    			$exclude_codes_array[$x] = $infos['random'];
    			$x ++;
    		}
    	}
    	$random = $this->generate_promotion_code(1,$exclude_codes_array,8);
    	if($cardID < $share || $cardID == $share){
    		$dataList[]=array(
    				'urlid'=>$urlid,
    				'cardid'=>$cardID,
    				'userid'=>$userinfo,
    				'shopid'=>$shopid,
    				'discount'=>$discount,				//type:折扣
    				'full'=>$full,						//type:满减
    				'cut'=>$cut,						//type:满减
    				'gift'=>$gift,						//type:礼金
    				'surplus'=>$gift,
    				'begintime' => $begintime,			//活动开始时间
    				'expiretime' => $expiretime,		//活动结束时间
    				'share'=>$share,					//活动优惠券总数量
    				'random' => "H".$random [0],		//优惠券码
    				'createtime'=>date("Y-m-d H-m-s") , //创建时间
    				//指定商品字段
    				'itemtypename'=>$item_info['itemtypename'], //是否指定商品1是0否
    				'typeid'=>$item_info['typeid']		  //指定商品编码
    	
    		);
    		M('coupons_card')->addAll($dataList);
    		if($discount != null){
    			$code_list =array(
    					'type'=>Z,
    					'random'=>"H".$random[0],					//兑换码
    					'uid'=>$userinfo,					//领取人员
    					'receive_time'=>date("Y-m-d H:i:s"),//领取时间
    					'shopid'=>$shopid
    			);
    			M('coupons_code')->add($code_list);
    			//Log::write(M('coupons_code')->getLastSql(), 'DEBUG');
    			M('discount')->addAll($dataList);
    		}else if ($full != null || $cut !=null){
    			$code_list =array(
    					'type'=>M,
    					'random'=>"H".$random[0],					//兑换码
    					'uid'=>$userinfo,						//领取人员
    					'receive_time'=>date("Y-m-d H:i:s"),//领取时间
    					'shopid'=>$shopid
    			);
    			M('coupons_code')->add($code_list);
    			M('full_cut')->addAll($dataList);
    		}elseif ($gift != null){
    			$code_list =array(
    					'type'=>B,
    					'random'=>"H".$random[0],					//兑换码
    					'uid'=>$userinfo,						//领取人员
    					'receive_time'=>date("Y-m-d H:i:s"),//领取时间
    					'shopid'=>$shopid
    			);
    			M('coupons_code')->add($code_list);
    			M('gift')->addAll($dataList);
    		}
    		$this->ajaxReturn(1, '优惠券领取成功', '');
    	}else {
    		$this->ajaxReturn(1, '优惠券已经领取完毕', '');
    	}
    }
    
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
    
	public function coupons(){
		if (IS_POST) {
			$prefix = C(DB_PREFIX);
			$uid = $this->shopId();
			$where['shopid'] = array('in', $this->getUidList());
			$where['expiretime'] = array('egt', date('Y-m-d'));
			$keyword = $this->_request ( 'keyword', 'trim' );
			if($keyword){
				$where['_string'] .= " (title like '%$keyword%' or sub_title like '%$keyword%')";
			}
			$list = M('coupons_url')->where($where)->select();
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

}