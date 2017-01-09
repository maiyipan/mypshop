<?php

defined('THINK_PATH') or exit();

class access_logBehavior extends Behavior {

    public function run(&$_data){
        $this->_access_log($_data);
    }

    /**
     * 记录登陆日志
     */
    private function _access_log($_data) {
		$access_log_mod = D ( 'access_log' );
		$access_log_mod->create ($_data 
		 );
		$access_log_mod->add ();
		//dump($access_log_mod->getLastSql());
	}
	
	
	/*
	array (
				'user_id' => $_data ['admin_id'],
				'action' => $_data ['action'],
				'operate' => $_data ['operate'],
				'argv' => $_data ['argv'],
				'argc' => $_data ['argc'],
				'gateway_interface' => $_data ['gateway_interface'],
				'server_name' => $_data ['server_name'],
				'server_software' => $_data ['server_software'],
				
				'server_protocol' => $_data ['server_protocol'],
				'request_method' => $_data ['request_method'],
				'query_string' => $_data ['query_string'],
				'document_root' => $_data ['document_root'],
				'http_accept' => $_data ['http_accept'],
				
				'http_accept_charset' => $_data ['http_accept_charset'],
				'http_accept_encoding' => $_data ['http_accept_encoding'],
				'http_accept_language' => $_data ['http_accept_language'],
				'http_connection' => $_data ['http_connection'],
				'http_host' => $_data ['http_host'],
				
				'http_referer' => $_data ['http_referer'],
				'http_user_agent' => $_data ['http_user_agent'],
				'https' => $_data ['https'],
				'remote_addr' => $_data ['remote_addr'],
				'remote_host' => $_data ['remote_host'],
				
				'remote_port' => $_data ['remote_port'],
				'script_filename' => $_data ['script_filename'],
				'server_admin' => $_data ['server_admin'],
				'server_port' => $_data ['server_port'],
				'server_signature' => $_data ['server_signature'],
				
				'path_translated' => $_data ['path_translated'],
				'script_name' => $_data ['script_name'],
				'request_uri' => $_data ['request_uri'],
				'php_auth_user' => $_data ['php_auth_user'],
				'php_auth_pw' => $_data ['php_auth_pw'],
				
				'auth_type' => $_data ['auth_type'] */
}