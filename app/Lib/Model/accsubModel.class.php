<?php
class accsubModel extends oracleModel {
	
	public function _construct(){
		parent::_construct();
		$this->tableName = $this->tablePrefix. '';
	}
	
	public function getInfo(){
		
		return $this->select();
	}
	
}