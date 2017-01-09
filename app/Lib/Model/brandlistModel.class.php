<?php
class brandlistModel extends Model {
    /**
     * 检测分类是否存在
     */
    public function name_exists($name, $id=0) {
        $where = "name='" . $name . "' AND id<>'" . $id . "'";
        $result = $this->where($where)->count('id');
        Log::write($this->getLastSql());
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    
}