<?php
class oracleModel extends Model {
	
	
	public function _construct(){
		$dbstr = 'jdbc:oracle:thin:@' .C('DB_HOST_T'). ':' .C('DB_PORT_T'). ':' . C('DB_NAME_T');
		$this->tablePrefix = C('DB_PREFIX_T');
		$this->db(4, $dbstr);
	}
   
}