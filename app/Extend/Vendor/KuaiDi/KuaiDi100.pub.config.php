<?php

		
class KuaiDi100Conf_pub {
	
const APPKEY = '4b1d05c678553725'; //请将XXXXXX替换成您在http://kuaidi100.com/app/reg.html申请到的KEY
//310551490635
//const url = 'http://api.kuaidi100.com/api?id=' . $AppKey . '&com=' . $typeCom . '&nu=' . $typeNu . '&show=2&muti=1&order=asc';
//http://api.kuaidi100.com/api?id=4b1d05c678553725&com=shunfeng&nu=310551490635&show=2&muti=1&order=asc
const URL = 'http://api.kuaidi100.com/api?';

//请勿删除变量$powered 的信息，否者本站将不再为你提供快递接口服务。
const POWERED = '查询数据由：<a href="http://kuaidi100.com" target="_blank">KuaiDi100PubHelper.Com （快递100）</a> 网站提供 ';
const SHOW = '2'; //返回类型： 0：返回json字符串， 1：返回xml对象， 2：返回html对象， 3：返回text文本。如果不填，默认返回json字符串。
const  MUTI = '1' ;//返回信息数量： 1:返回多行完整的信息， 0:只返回一行信息。 不填默认返回多行。
const ORDER = 'desc'; //排序： desc：按时间由新到旧排列， asc：按时间由旧到新排列。 不填默认返回倒序（大小写不敏感）
}