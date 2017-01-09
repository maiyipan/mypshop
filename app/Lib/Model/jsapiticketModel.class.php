<?php
class jsapiticketModel extends RelationModel
{
    protected $_validate = array(
    );
    
    /**
     * 保存access_token
     * */
   public function addjsapiticket($data) {
    	$this->data ( $data )->add ();
    }
    /**
     * 更新 access_token
     * */
    public function updateAccess_token($data) {
    	$this->data ( $data )->where ( "accountid = '" . $data ['accountid'] . "'" )->save ();
    }
    
    /**
     * 查询access_token
     * */
    public function queryJsapiticket($accountid) {
    	$data = $this->where ( "accountid = '" . $accountid . "'" )->find ();
    	return $data;
    }
    
    
}