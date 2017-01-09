<?php

defined('THINK_PATH') or exit();

class login_logBehavior extends Behavior {

    public function run(&$_data){
        $this->_login_log($_data);
    }

    /**
     * 记录登陆日志
     */
    private function _login_log($_data) {
            $login_log_mod = D('login_log');
            $login_log_mod->create(array(
               'admin_id' => $_data['admin_id'],
            	'type' => $_data['type'],
            ));
            $login_log_mod->add();
    }
}