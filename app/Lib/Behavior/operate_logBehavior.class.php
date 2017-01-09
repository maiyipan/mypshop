<?php

defined('THINK_PATH') or exit();

class operate_logBehavior extends Behavior {

    public function run(&$_data){
        $this->_operate_log($_data);
    }

    /**
     * 记录登陆日志
     */
    private function _operate_log($_data) {
            $operate_log_mod = D('operate_log');
            $operate_log_mod->create(array(
               'admin_id' => $_data['admin_id'],
            	'action' => $_data['action'],
            	'operate' => $_data['operate'],
            ));
            $operate_log_mod->add();
    }
}