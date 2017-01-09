<?php
class memberAction extends Action {
	public function index() {
		$ip = $_SERVER ["REMOTE_ADDR"];
		
		//Log::write ( $ip . '--' . getenv ( "HTTP_X_FORWARDED_FOR" ) . '--' . getenv ( "REMOTE_ADDR" ) );
		
		if ($ip == '127.0.0.1') {
			$cardMod = M ( 'card' );
			$users = $this->getBindUser ();
			Vendor ( 'FrdifService.HttpService' );
			$client = new \HttpService ();
			$client->setCmdid ( '1010' );

			$queryType = '3'; // 账号查询为3
			for($i = 0; $i < count ( $users ); $i ++) {
				$queryParam = $users [$i] ['card_num'];
				//$client->setParam ( $queryType . ',' . $queryParam );
				$client->setParam ( array(
					'_param0' =>$queryType,
					'_param1' => $queryParam
				));
				//Log::write ( 'get account info...' );
				$result = $client->getInfo();
				$i = 0;
				if ($result  != - 1) {
					$resultDataInfo = explode ( '	', $result );
					$data ['card_num'] = $card_num = $resultDataInfo [0];
					$data ['card_score'] = $card_score = $resultDataInfo [2];
					$data ['card_balance'] = $card_balance = $resultDataInfo [3];
					$cardMod->where ( 'card_num=' . $card_num )->save ( $data );
				} else {
					Log::write ( 'sync info err:' . $result);
				}
			}
		} else  {
			echo 'forbid ip';
		}
	} 
	
	
	
	/**
	 * 查询所有绑定的用户
	 * 
	 * */
	public function getBindUser(){
		$userMode = M('user');
		$userInfo  = $userMode->field('card_num')->where('bindstatus=1')->select();
		foreach ($userInfo as $user) {
			//Log::write("user-" . $user['card_num']);
		}
		
		return $userInfo;
		
	}
}