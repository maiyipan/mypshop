<?php
defined('THINK_PATH') or exit();
class itemorder_logBehavior extends Behavior{
	public function run(&$_data){
		$this->_order_log($_data);
	}
	
	/**
	 * 订单状态日志
	 */
	private function _order_log($_data) {
		$order_log_mod = D('order_log');
		$order_log_mod->create(array(
				'admin_id' => $_data['admin_id'],
				'type' => $_data['type'],
		));
		$order_log_mod->add();
	}
	
}