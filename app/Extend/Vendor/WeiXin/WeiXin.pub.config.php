<?php
class WeiXinConf_pub {
	
//获取网页授权code url
const CODE_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize?';

//网页获取openid url
const OPENID_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token?';


//网页授权拉取用户信息
const USERINFO_URL ='https://api.weixin.qq.com/sns/userinfo?';

//发送消息 url
const   SEND_CUSTOM_MESSAGE ='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=';

//发送模板消息
const SEND_TEMPLATE = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';

//发送菜单
const PUBLIC_MENU = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=';

/**
 * 获取用户基础信息
 * */
const BASE_USERINO = 'https://api.weixin.qq.com/cgi-bin/user/info?'; //access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN

}