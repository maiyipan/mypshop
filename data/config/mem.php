<?php

/**
 * 会员系统参数配置 url userid password
 * const URL = 'http://121.201.8.142:7002/frdif/n_frdif.asmx?WSDL';
	const USERID = 'test';
	const PASSWORD = '00B54E5ADD61AC9C';
 * */

if ($_SERVER['SERVER_NAME'] == 'jx.i-lz.cn') { // 测试 jx

	return array(
			'MemberSystemUrl' =>'http://121.201.8.142:7002/frdif/n_frdif.asmx?WSDL',
			'MemberSystemUserid' => 'test',
			'MemberSystemPassword'=> '00B54E5ADD61AC9C',
	);
}  elseif ($_SERVER['SERVER_NAME'] == 'ikshop.i-lz.cn') {  //生产
	return array(
			'MemberSystemUrl' =>'http://121.201.8.142:7002/frdif/n_frdif.asmx?WSDL',
			'MemberSystemUserid' => 'test',
			'MemberSystemPassword'=> '00B54E5ADD61AC9C',
	);

} else {
	return array(
			'MemberSystemUrl' =>'http://121.201.8.142:7002/frdif/n_frdif.asmx?WSDL',
			'MemberSystemUserid' => 'test',
			'MemberSystemPassword'=> '00B54E5ADD61AC9C',
	);
}

//$client -> setPosturl(C('MemberSystemUrl'));
//$client -> setUserid(C('MemberSystemUserid'));
//$client -> setPassword(C('MemberSystemPassword'));