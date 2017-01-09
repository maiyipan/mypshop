<?php

class sectionModel extends Model
{

    /**
     * 获取配置信息写入缓存
     */
    public function section_cache() {
        $section = array();
        $res = $this->getField('id,key,name, img');
        
        foreach ($res as $key=>$val) {
            //$setting['pin_'.$key] = unserialize($val['data']) ? unserialize($val['data']) : $val['data']; //.'_'.$val['uid']
            $section['section_'.$val['key']] = unserialize($val['img']) ? unserialize($val['img']) : $val['img'];
        }
        F('section', $section);
        return $section;
    }

    /**
     * 后台有更新则删除缓存
     */
    protected function _before_write($data, $options) {
        F('section', NULL);
    }
}