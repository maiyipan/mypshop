<?php
class menuModel extends Model {
    
    protected $_validate = array(
        array('name', 'require', '{%menu_name_require}'), //菜单名称为必须
        array('name', 'require', '{%module_name_require}'), //模块名称必须
        array('name', 'require', '{%action_name_require}'), //方法名称必须
    );

    public function admin_menu($pid, $role_id,$with_self=false) {
        $pid = intval($pid);
        $condition = array('pid' => $pid);
        if ($with_self) {
            $condition['id'] = $pid;
            $condition['_logic'] = 'OR';
        }
        $map['_complex'] = $condition;
        $map['display'] = 1;
        $mod = M("menu");
        $menus = $mod->where($map)
                   ->join('RIGHT JOIN weixin_admin_auth on weixin_menu.id = weixin_admin_auth.menu_id AND weixin_admin_auth.role_id = '. $role_id)
                        ->order('ordid')->select();
        //Log::write($mod->getLastSql(), 'DEBUG');
       // dump($mod->getLastSql());
        return $menus;
    }
    
    public function sub_menu($pid = '',$role_id, $big_menu = false) {
        $array = $this->admin_menu($pid, $role_id,false);
        $numbers = count($array);
        if ($numbers==1 && !$big_menu) {
            return '';
        }
        return $array;
    }
    
    public function get_level($id,$array=array(),$i=0) {
        foreach($array as $n=>$value){
            if ($value['id'] == $id) {
                if($value['pid']== '0') return $i;
                $i++;
                return $this->get_level($value['pid'],$array,$i);
            }
        }
    }
}