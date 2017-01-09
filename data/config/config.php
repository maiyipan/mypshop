<?php
return array(
    'APP_GROUP_LIST' =>'sync,adminn,www', //分组
    'DEFAULT_GROUP' => 'www', //默认分组
    'DEFAULT_MODULE' => 'index', //默认控制器
	//'URL_CASE_INSENSITIVE' =>true,
    'TAGLIB_PRE_LOAD' => 'pin', //自动加载标签
    'APP_AUTOLOAD_PATH' => '@.Ilztag,@.Ilzlib,@.ORG', //自动加载项目类库
    'TMPL_ACTION_SUCCESS' => 'public:success',
    'TMPL_ACTION_ERROR' => 'public:error',
    'DATA_CACHE_SUBDIR'=>true, //缓存文件夹
    'DATA_PATH_LEVEL'=>3, //缓存文件夹层级
    'LOAD_EXT_CONFIG' => 'url,db,mem', //扩展配置  mem会员系统
    
    'SHOW_PAGE_TRACE' => false,
	'RUN_MODE' => true, // 0 本地  1 测试 2 生产
	'URL_404_REDIRECT' => 'public:404',
);