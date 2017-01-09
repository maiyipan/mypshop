<?php
class UserCardPay {
	//如果$actual为正则会员 支付，如果$actual为负则会员退款
	
    /**
     * $tradeCode 
     * $userid = 'wxtest';
     * $actual 退款金额
     * $card_num 卡号
     * $public_store  门店编号  默认为001
     * */
	public function balanceCommit_pay($tradeCode, $actual,$card_num, $public_store){
//        Vendor('FrdifService.CustomService');
//        $client = new \CustomService();
//        $client->setPosturl(C('MemberSystemUrl'));
//        $client->setUserid(C('MemberSystemUserid'));
//        $client->setPassword(C('MemberSystemPassword'));
//        $client->setCmdid('1011');
//        $queryParam = $tradeCode . ',' . $card_num . ',' . $public_store . ',' . $actual;
//        $client->setQueryParam($queryParam);
//        Log::write('queryParam' . $queryParam, 'DEBUG');
//        $result = $client->processdata();
//        Log::write("the pay result---" . $result['rtn'] . '--' . $result['outputpara']);
//
//        return $result;


            Vendor('FrdifService.HttpService');
            $client = new \HttpService();
            $client->setCmdid('1011');
            $param = array(
                '_param0' =>$tradeCode,
                '_param1' => $card_num,
                '_param2' => $public_store,
                '_param3' => $actual
            );
            $client->setParam($param);
            //Log::write('queryParam' . $queryParam,'DEBUG');
            $result= $client->getInfo();
            Log::write("the pay result---"  . $result);
            return $result;
    }
}
