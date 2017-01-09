<?php
class indexAction extends backendAction {

    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('menu');
        $this->item_order=M('item_order');
        $this->sid = $_SESSION['admin']['uid'];
    }

    public function index() {
    	//Log::write("test log", Log::DEBUG);
    	//Log::write("test INFO", Log::INFO);
    	$this->qiehuan();
    	$role_id = $_SESSION['admin']['role_id'];
        $top_menus = $this->_mod->admin_menu(0, $role_id);
        $this->assign('top_menus', $top_menus);
        $my_admin = array(
        		'username'=>$_SESSION['admin']['username'], 
        		'rolename'=>$_SESSION['admin']['role_name'],
        		'shopname'=>$_SESSION['admin']['shopname'],
        		'currname'=>$_SESSION['admin']['currname'],
        		'currid'=>$_SESSION['admin']['currid']
        );
        $this->assign('my_admin', $my_admin);
        $this->display();
    }

    public function panel() {
        $message = array();
        if (APP_DEBUG == true) {
            $message[] = array(
                'type' => 'error',
                'content' => "您网站的 DEBUG 没有关闭，出于安全考虑，我们建议您关闭程序 DEBUG。",
            );
        }
        if (!function_exists("curl_getinfo")) {
            $message[] = array(
                'type' => 'error',
                'content' => "系统不支持 CURL ,将无法采集商品数据。",
            );
        }
        $this->assign('message', $message);
        $system_info = array(
            'pinphp_version' => PIN_VERSION . ' RELEASE '. PIN_RELEASE .' [<a href="http://www.pinphp.com/" class="blue" target="_blank">查看最新版本</a>]',
            'server_domain' => $_SERVER['SERVER_NAME'] . ' [ ' . gethostbyname($_SERVER['SERVER_NAME']) . ' ]',
            'server_os' => PHP_OS,
            'web_server' => $_SERVER["SERVER_SOFTWARE"],
            'php_version' => PHP_VERSION,
            'mysql_version' => mysql_get_server_info(),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'max_execution_time' => ini_get('max_execution_time') . '秒',
            'safe_mode' => (boolean) ini_get('safe_mode') ?  L('yes') : L('no'),
            'zlib' => function_exists('gzclose') ?  L('yes') : L('no'),
            'curl' => function_exists("curl_getinfo") ? L('yes') : L('no'),
            'timezone' => function_exists("date_default_timezone_get") ? date_default_timezone_get() : L('no')
        );
        $this->assign('system_info', $system_info);
        
        $buycount= M('item')->where('status=1')->count();
         $nobuycount= M('item')->where('status=0')->count();
        
		$fukuan = $this->item_order->where(array('status'=>1,'uid'=>array('in',$this->getUidList())))->count();
        $fahuo= $this->item_order->where(array('status'=>2,'uid'=>array('in',$this->getUidList())))->count();
        $yfahuo= $this->item_order->where(array('status'=>3,'uid'=>array('in',$this->getUidList())))->count();
        $this->assign('count',
        array('fukuan'=>$fukuan,
        'fahuo'=>$fahuo,
        'yfahuo'=>$yfahuo,
        'buycount'=>$buycount,
        'nobuycount'=>$nobuycount
        )
        );
        $this->display();
    }

    public function login() {
        if (IS_POST) {
            $username = $this->_post('username', 'trim');
            $password = $this->_post('password', 'trim');
            $verify_code = $this->_post('verify_code', 'trim');
            if(session('verify') != md5($verify_code)){
                $this->error(L('verify_code_error'));
            }
            $admin = M('admin')->where(array('username'=>$username, 'status'=>1))->find();
           
            if (!$admin) {
                $this->error(L('admin_not_exist'));
            }
            if ($admin['password'] != md5($password)) {
                $this->error(L('password_error'));
            }
            /* dump($admin);
            exit(); */
            $role = M('admin_role')->where(array('id'=>$admin['role_id'], 'status'=>1))->find();
            $shop = M('shop') -> where(array('uid'=>$admin['uid']))->find();
            
            $shopCout = M('shop')->where('pid = ' . $shop['id'])->count();
            
            if ($shopCout > 0) {
            	$shopList = M('shop')->where('pid = ' . $shop['id'])->select();
            } else {
				$shopList = '';            	
            }
//             dump($shopList);
//             exit();
            session('admin', array(
                'id' => $admin['id'],
                'role_id' => $admin['role_id'],
                'role_name' => $role['name'],
            	'username' => $admin['username'],
            	'uid' => $admin['uid'],
            	'currid' => $admin['uid'],
            	'currname' => $shop['name'],
            	'shopname' => $shop['name'],
            	'shopid' => $shop['id'],
            	'shopcount'=>$shopCout,
            	'shopList'=>$shopList,
            	
            ));
            M('admin')->where(array('id'=>$admin['id']))->save(array('last_time'=>time(), 'last_ip'=>get_client_ip()));
            
            //记录日志
            $tag_arg = array ('admin_id' => $admin['username'],  'type' => '0' );
            //dump($tag_arg);
            tag ( 'login_log', $tag_arg );
            $this->success(L('login_success'), U('index/index'));
        } else {
            $this->display();
        }
    }

    public function logout() {
        $admin_id= $_SESSION['admin']['username'];
        $tag_arg = array ('admin_id' => $admin_id,  'type' => '1' );
        tag ('login_log', $tag_arg );
        session('admin', null);
        $this->success(L('logout_success'), U('index/login'));
        exit;
    }

    public function verify_code() {
        Image::buildImageVerify(4,1,'gif','50','24');
    }

    public function left() {
    	$role_id = $_SESSION['admin']['role_id'];
        $menuid = $this->_request('menuid', 'intval');
        if ($menuid) {
            $left_menu = $this->_mod->admin_menu($menuid, $role_id);
            foreach ($left_menu as $key=>$val) {
                $left_menu[$key]['sub'] = $this->_mod->admin_menu($val['id'], $role_id);
            }
        } 
        $this->assign('left_menu', $left_menu);
        $this->display();
    }

    public function often() {
        if (isset($_POST['do'])) {
            $id_arr = isset($_POST['id']) && is_array($_POST['id']) ? $_POST['id'] : '';
            $this->_mod->where(array('ofen'=>1))->save(array('often'=>0));
            $id_str = implode(',', $id_arr);
            $this->_mod->where('id IN('.$id_str.')')->save(array('often'=>1));
            $this->success(L('operation_success'));
        } else {
            $r = $this->_mod->admin_menu(0);
            $list = array();
            foreach ($r as $v) {
                $v['sub'] = $this->_mod->admin_menu($v['id']);
                foreach ($v['sub'] as $key=>$sv) {
                    $v['sub'][$key]['sub'] = $this->_mod->admin_menu($sv['id']);
                }
                $list[] = $v;
            }
            $this->assign('list', $list);
            $this->display();
        }
    }

    public function map() {
        $r = $this->_mod->admin_menu(0);
        $list = array();
        foreach ($r as $v) {
            $v['sub'] = $this->_mod->admin_menu($v['id']);
            foreach ($v['sub'] as $key=>$sv) {
                $v['sub'][$key]['sub'] = $this->_mod->admin_menu($sv['id']);
            }
            $list[] = $v;
        }
        $this->assign('list', $list);
        $this->display();
    }
    
    
    public function ajaxOrigin(){
    	$Mod = M('origin');
        $data = $Mod->field('value, data')->select();
    	$origin1 = array(
    			'value'=>'Andorra13',
    			'data'=>'ad3'
   				
    	);
    	$origin2 = array(
    			'value'=>'Andorra1',
    			'data'=>'ad'
    	);
    	
    	$origin['suggestions'] = $data;
    	echo json_encode ($origin);
    }
    
    
    
    
}