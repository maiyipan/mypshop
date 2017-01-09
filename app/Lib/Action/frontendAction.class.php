<?php

/**
 * 前台控制器基类
 *
 * @author andery
 */
class frontendAction extends baseAction
{

    protected $visitor = null;

    protected $shopId = null;

    public function _initialize() {
		parent::_initialize ();
		
		$current_url = 'http://';
		if (isset ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] == 'on') {
			$current_url = 'https://';
		}
		if ($_SERVER ['SERVER_PORT'] != '80') {
			$current_url .= $_SERVER ['SERVER_NAME'] . ':' . $_SERVER ['SERVER_PORT'] . $_SERVER ['REQUEST_URI'];
		} else {
			$current_url .= $_SERVER ['SERVER_NAME'] . $_SERVER ['REQUEST_URI'];
		}
		$currentUrl = str_replace ( '/index.php', '', $current_url );
		
		// 初始化访问者
		$this->_init_visitor ();
		// 网站状态
		if (! C ( 'pin_site_status' )) {
			header ( 'Content-Type:text/html; charset=utf-8' );
			exit ( C ( 'pin_closed_reason' ) );
		}
		
		$tmpsid = $_GET ['sid'];
		// TODO 放开商城的验证
		$shopMod = D ( 'shop' );
		if ($tmpsid) {
			$shopWhere['uid'] = $tmpsid;
			$shopInfo = $shopMod->where ( $shopWhere )->find ();
			if (! $shopInfo || $shopInfo['status'] ==0 ) { // 店铺不存在
				$this->_404 ();
			} else {
				if ($tmpsid == $_SESSION ['sid']) {
					$this->shopId = $tmpsid;
				} else {
					if (null == $_SESSION ['sid']) { // 第一次进来
					                                // 是否为店铺，如果为父sid ，则给一个默认的店铺
						if ($shopMod->isPre ( $shopInfo ['id'] )) { // 是
							$this->shopId = $_SESSION ['sid'] = $shopMod->getDefaultShop ( $shopInfo ['id'] ) ['uid'];
						} else { // 否 直接是子店铺
							$this->shopId = $_SESSION ['sid'] = $tmpsid;
						}
					} else { // 切换了店铺 切换到父店铺，切换到别的父店铺，切换到自己的兄弟店铺，切换到其它堂店铺
						// 如果时其它父店铺或者堂兄弟店铺，则退出以前的店铺，进行重新登陆
						/* if ($shopInfo ['pid'] != $_SESSION ['shopInfo'] ['pid']  ) {
							$this->visitor->logout ();
							$passport = $this->_user_server ();
							$synlogout = $passport->synlogout ();
							$_SESSION = null;
							$this->visitor->is_login = false;
							$this->success ( '切换店铺' . $synlogout, $currentUrl );
						} else {
							$this->shopId = $_SESSION ['sid'] = $tmpsid;
						} */
						if ($shopInfo ['pid'] ==  $_SESSION ['shopInfo'] ['id']) {//父到子
							$this->shopId = $_SESSION ['sid'] = $tmpsid;
						} elseif ($shopInfo ['id'] ==  $_SESSION ['shopInfo'] ['pid']){ //子到父
							$this->shopId = $_SESSION ['sid'] = $tmpsid;
						} elseif ($shopInfo ['pid'] ==  $_SESSION ['shopInfo'] ['pid']) { //兄弟
							$this->shopId = $_SESSION ['sid'] = $tmpsid;
						} else { //清空
							$this->visitor->logout ();
							$passport = $this->_user_server ();
							$synlogout = $passport->synlogout ();
							$_SESSION = null;
							$this->visitor->is_login = false;
							$this->success ( '切换店铺' . $synlogout, $currentUrl );
						}
					}
				}
			}
		} else {
			if (null == $_SESSION ['sid']) {
				$this->_404 ();
			} else {
				$this->shopId = $_SESSION ['sid']; // '01233a79c297477d554051b1bb3650';
			}
		}
		//Log::write ( 'curren shop--' . $this->shopId );
		$realShop =  $shopMod->where ( array (
				'uid' => $this->shopId
		) )->find ();
		$_SESSION ['shopInfo'] = $realShop;
		
		if ($this->shopId) {
			$this->assign ( ('shopname'), $shopMod->getShopNmaeByUid ( $this->shopId ) );
			//$this->assign ( ('shopname'), $realShop['name'] );
			$temp =  $shopMod->where ( array (
					'uid' => $this->shopId
			) )->find ();
			$this->assign('index_order', $temp['index_order']);
		}
		$this->assign ( 'appid', C ( 'spconf_appid_' . $this->shopId ) ); // 
		$this->assign ( 'shopid', $this->shopId );
		
		// 第三方登陆模块
		//$this->_assign_oauth ();
		// 访问保存
		$this->saveAccessLog ();
		// 网站导航选中
		//$this->assign ( 'nav_curr', '' );
		
		//$this->_index_cate ();
		$this->getCartNum ();
        $this->weixinSDK();
    }

    private function getCartNum()
    {
        import('Think.ORG.Cart'); // 导入分页类
        $cart = new Cart($this->shopId);
        $cart->setCartKey($this->shopId);
        $count = 0;
        foreach ($_SESSION[$cart->cartKey] as $val) {
            $count += $val['num'];
        }
        $this->assign('amount', $count);
    }

    private function _index_cate()
    {
        // 分类
        if (false === $index_cate_list = F('index_cate_list')) {
            $item_cate_mod = M('item_cate');
            // 分类关系
            if (false === $cate_relate = F('cate_relate')) {
                $cate_relate = D('item_cate')->relate_cache();
            }
            // 分类缓存
            if (false === $cate_data = F('cate_data')) {
                $cate_data = D('item_cate')->cate_data_cache();
            }
            // 推荐到首页的大类
            $index_cate_list = $item_cate_mod->field('id,name,img')
                ->where(array(
                'pid' => '0',
                'is_index' => '1',
                'status' => '1'
            ))
                ->order('ordid')
                ->select();
            foreach ($index_cate_list as $key => $val) {
                // 推荐到首页的子类
                $where = array(
                    'status' => '1',
                    'is_index' => '1',
                    'spid' => array(
                        'like',
                        $val['id'] . '|%'
                    )
                );
                $index_cate_list[$key]['index_sub'] = $item_cate_mod->field('id,name,img')
                    ->where($where)
                    ->order('ordid')
                    ->select();
                // 普通子类
                $index_cate_list[$key]['sub'] = array();
                foreach ($cate_relate[$val['id']]['sids'] as $sid) {
                    if ($cate_data[$sid]['type'] == '0' && $cate_data[$sid]['pid'] != $val['id']) {
                        $index_cate_list[$key]['sub'][] = $cate_data[$sid];
                    }
                    if (count($index_cate_list[$key]['sub']) >= 6) {
                        break;
                    }
                }
            }
            F('index_cate_list', $index_cate_list);
        }
        
        // echo "<pre>";
        // var_dump($index_cate_list);
        // echo "</pre>";
        $this->assign('index_cate_list', $index_cate_list);
    }

    /**
     * 初始化访问者
     */
    private function _init_visitor()
    {
        $this->visitor = new user_visitor();
        $this->assign('visitor', $this->visitor->info);
    }

    /**
     * 第三方登陆模块
     */
    private function _assign_oauth()
    {
        if (false === $oauth_list = F('oauth_list')) {
            $oauth_list = D('oauth')->oauth_cache();
        }
        $this->assign('oauth_list', $oauth_list);
    }

    /**
     * SEO设置
     */
    protected function _config_seo($seo_info = array(), $data = array())
    {
        $page_seo = array(
            'title' => C('pin_site_title'),
            'keywords' => C('pin_site_keyword'),
            'description' => C('pin_site_description')
        );
        $page_seo = array_merge($page_seo, $seo_info);
        // 开始替换
        $searchs = array(
            '{site_name}',
            '{site_title}',
            '{site_keywords}',
            '{site_description}'
        );
        $replaces = array(
            C('pin_site_name'),
            C('pin_site_title'),
            C('pin_site_keyword'),
            C('pin_site_description')
        );
        preg_match_all("/\{([a-z0-9_-]+?)\}/", implode(' ', array_values($page_seo)), $pageparams);
        if ($pageparams) {
            foreach ($pageparams[1] as $var) {
                $searchs[] = '{' . $var . '}';
                $replaces[] = $data[$var] ? strip_tags($data[$var]) : '';
            }
            // 符号
            $searchspace = array(
                '((\s*\-\s*)+)',
                '((\s*\,\s*)+)',
                '((\s*\|\s*)+)',
                '((\s*\t\s*)+)',
                '((\s*_\s*)+)'
            );
            $replacespace = array(
                '-',
                ',',
                '|',
                ' ',
                '_'
            );
            foreach ($page_seo as $key => $val) {
                $page_seo[$key] = trim(preg_replace($searchspace, $replacespace, str_replace($searchs, $replaces, $val)), ' ,-|_');
            }
        }
        $this->assign('page_seo', $page_seo);
    }

    /**
     * 连接用户中心
     */
    protected function _user_server()
    {
        $passport = new passport(C('pin_integrate_code'));
        return $passport;
    }

    /**
     * 前台分页统一
     */
    protected function _pager($count, $pagesize)
    {
        $pager = new Page($count, $pagesize);
        $pager->rollPage = 5;
        $pager->setConfig('prev', '<');
        $pager->setConfig('theme', '%upPage% %first% %linkPage% %end% %downPage%');
        return $pager;
    }

    /**
     * 瀑布显示
     */
    public function waterfall($where = array(), $order = 'id DESC', $field = '', $page_max = '', $target = '')
    {
        $spage_size = C('pin_wall_spage_size'); // 每次加载个数
        $spage_max = C('pin_wall_spage_max'); // 每页加载次数
        $page_size = $spage_size * $spage_max; // 每页显示个数
        
        $item_mod = M('item');
        $where_init = array(
            'status' => '1'
        );
        $where = $where ? array_merge($where_init, $where) : $where_init;
        $count = $item_mod->where($where)->count('id');
        // 控制最多显示多少页
        if ($page_max && $count > $page_max * $page_size) {
            $count = $page_max * $page_size;
        }
        // 查询字段
        $field == '' && $field = 'id,title,intro,img,price,comments,comments_cache';
        // 分页
        $pager = $this->_pager($count, $page_size);
        $target && $pager->path = $target;
        $item_list = $item_mod->field($field)
            ->where($where)
            ->order($order)
            ->limit($pager->firstRow . ',' . $spage_size)
            ->select();
        foreach ($item_list as $key => $val) {
            isset($val['comments_cache']) && $item_list[$key]['comment_list'] = unserialize($val['comments_cache']);
        }
        $this->assign('item_list', $item_list);
        // 当前页码
        $p = $this->_get('p', 'intval', 1);
        $this->assign('p', $p);
        // 当前页面总数大于单次加载数才会执行动态加载
        if (($count - ($p - 1) * $page_size) > $spage_size) {
            $this->assign('show_load', 1);
        }
        // 总数大于单页数才显示分页
        $count > $page_size && $this->assign('page_bar', $pager->fshow());
        // 最后一页分页处理
        if ((count($item_list) + $page_size * ($p - 1)) == $count) {
            $this->assign('show_page', 1);
        }
    }

    /**
     * 瀑布加载
     */
    public function wall_ajax($where = array(), $order = 'id DESC', $field = '')
    {
        $spage_size = C('pin_wall_spage_size'); // 每次加载个数
        $spage_max = C('pin_wall_spage_max'); // 加载次数
        $p = $this->_get('p', 'intval', 1); // 页码
        $sp = $this->_get('sp', 'intval', 1); // 子页
                                              
        // 条件
        $where_init = array(
            'status' => '1'
        );
        $where = array_merge($where_init, $where);
        // 计算开始
        $start = $spage_size * ($spage_max * ($p - 1) + $sp);
        $item_mod = M('item');
        $count = $item_mod->where($where)->count('id');
        $field == '' && $field = 'id,uid,title,intro,img,price,likes,comments,comments_cache';
        $item_list = $item_mod->field($field)
            ->where($where)
            ->order($order)
            ->limit($start . ',' . $spage_size)
            ->select();
        //Log::write($item_mod->getLastSql(), 'DEBUG');
        foreach ($item_list as $key => $val) {
            // 解析评论
            isset($val['comments_cache']) && $item_list[$key]['comment_list'] = unserialize($val['comments_cache']);
        }
        $this->assign('item_list', $item_list);
        $resp = $this->fetch('public:waterfall');
        $data = array(
            'isfull' => 1,
            'html' => $resp
        );
        $count <= $start + $spage_size && $data['isfull'] = 0;
        $this->ajaxReturn(1, '', $data);
    }

    public function shopId()
    {
        return $this->shopId;
    }

    public function saveAccessLog()
    {
        $this->visitor->info;
        $tag_arg = array(
            'user_id' => $this->visitor->info['id'],
            'action' => MODULE_NAME,
            'operate' => ACTION_NAME,
            'scene' => $_GET['scene'],
            'argv' => $_SERVER['ARGV'],
            'argc' => $_SERVER['ARGC'],
            'gateway_interface' => $_SERVER['GATEWAY_INTERFACE'],
            'server_name' => $_SERVER['SERVER_NAME'],
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            
            'server_protocol' => $_SERVER['SERVER_PROTOCOL'],
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'query_string' => $_SERVER['QUERY_STRING'],
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'http_accept' => $_SERVER['HTTP_ACCEPT'],
            
            'http_accept_charset' => $_SERVER['HTTP_ACCEPT_CHARSET'],
            'http_accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'],
            'http_accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
            'http_connection' => $_SERVER['HTTP_CONNECTION'],
            'http_host' => $_SERVER['HTTP_HOST'],
            
            'http_referer' => $_SERVER['HTTP_REFERER'],
            'http_user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'https' => $_SERVER['HTTPS'],
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
            'remote_host' => $_SERVER['REMOTE_HOST'],
            
            'remote_port' => $_SERVER['REMOTE_PORT'],
            'script_filename' => $_SERVER['SCRIPT_FILENAME'],
            'server_admin' => $_SERVER['SERVER_ADMIN'],
            'server_port' => $_SERVER['SERVER_PORT'],
            'server_signature' => $_SERVER['SERVER_SIGNATURE'],
            
            'path_translated' => $_SERVER['PATH_TRANSLATED'],
            'script_name' => $_SERVER['SCRIPT_NAME'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'php_auth_user' => $_SERVER['PHP_AUTH_USER'],
            'php_auth_pw' => $_SERVER['PHP_AUTH_PW'],
            
            'auth_type' => $_SERVER['AUTH_TYPE']
        );
        
        tag('access_log', $tag_arg);
    }

    /**
     * 获取微信jsdk参数
     */
    public function weixinSDK()
    {
        if ($_SESSION ['signPackage'] != null) {
        	$signPackage = $_SESSION ['signPackage'];
        } else {
        	Vendor('WeiXin.jssdk');
        	$sid = $this->shopId;
        	$shopModel = D('shop');
        	$pid = $shopModel->getUidForP($sid);
        	$appid = C('spconf_appid_' . $pid);
        	$appsecret = C('spconf_appsecret_' . $pid);
        	$this->assign('appid', $appid);
        	$jssdk = new JSSDK($appid, $appsecret);
        	$signPackage = $jssdk->GetSignPackage();
        }
       
        $this->assign('signPackage', $signPackage);
    }

    /**
     * 瀑布加载
     */
    public function wall_item($where = array(), $order = 'id DESC', $field = '')
    {
        $userid = $this->visitor->info['id']; // 获取会员id号
        $spage_size = 6; // C('pin_wall_spage_size'); //每次加载个数 
        $p = $this->_get('p', 'intval', 1); // 页码
                                            
        // 条件
        $where_init = array(
            'status' => '1'
        );
        $where = array_merge($where_init, $where);
        $tempid = $where['uid'];
        unset($where['uid']);
        $where['weixin_item.uid'] = $tempid;
        
        $item_mod = D('item');
        
        // 总数
        $db_pre = C('DB_PREFIX');
        $item = $db_pre . 'item';
        
        $count = $item_mod->relation(true)
            ->join('LEFT JOIN weixin_item_like ulike ON ulike.id = weixin_item.id')
            ->join($db_pre . 'item_base base ON base.id = ' . $item . '.baseid')
            ->join($db_pre . 'promotion prom ON prom.condition = ' . $item . '.id')
            ->where($where)
            ->count($item . '.id');
        
            
            ////Log::write($item_mod->getLastSql());
            //Log::write('-count-' . $count);
        // 工多少页
        $pageSize = $count / $spage_size;
        
        $start = ($p -1)*$spage_size;
        //maiyipan 优化商品列表价格显示base.prime_price=>weixin_item.prime_price 2016-2-26
        $field == '' && $field = 'weixin_item.*,weixin_item.prime_price,
				                     base.cate_id,base.brand,base.title,
				                       base.intro,base.img,base.info,base.originplace,
									   ulike.userid as likeflag,
				                         prom.condition as prom
				                         ';
        $item_list = $item_mod->relation(true)
            ->join('LEFT JOIN weixin_item_like ulike ON ulike.id = weixin_item.id')
            ->join($db_pre . 'item_base base ON base.id = ' . $item . '.baseid')
            ->join($db_pre . 'promotion prom ON prom.condition = ' . $item . '.id')
            ->field($field)
            ->where($where)
            ->order($order)
			->limit($start. ',' .$spage_size)
            ->select();
        
        //Log::write($item_mod->getLastSql(), 'DEBUG');
        // exit();
        foreach ($item_list as $key => $val) {
            // 解析评论
            isset($val['comments_cache']) && $item_list[$key]['comment_list'] = unserialize($val['comments_cache']);
        }
        $this->assign('item_list', $item_list);
        $resp = $this->fetch('public:waterfall');
        $data = array(
            'isfull' => 1,
            'html' => $resp
        );
        // 数据查询完
        $count <= $start + $spage_size && $data['isfull'] = 0;
        $this->ajaxReturn(1, '', $data);
    }

    /**
     * 判断uid 店铺是否为独立运营， 独立运营则则返回uid，不独立运营则返回上级独立运营的uid
     */
    public function getuid()
    {
        $uid = $this->shopId;
        $shopModel = D('shop');
        /*
         * $shopModel-> isself();
         * if ($shopModel-> isself()) { //独立运营
         * return $uid;
         * } else { //查询上级对应的uid，是否为独立运营
         *
         * }
         */
        return $shopModel->getUidForP($uid);
    }
    /**
     * 添加后台操作订单 日志
     */
    function addwwwlogs($userId,$orderid,$mode,$otherinfo=''){
        $logsdata  = array(
            'uid'    => $this->shopId,//店铺id号
            'userId'=> $userId,  //操作人员
            'orderId'=> $orderid,  //订单号
            'mode'   => $mode,     //操作模块
            'ctime'  => date('Y-m-d H:i:s'),
            'otherinfo'=>$otherinfo
        );
        M('wwworder_logs')->add($logsdata);
    }
}