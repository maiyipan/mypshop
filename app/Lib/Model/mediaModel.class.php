<?php
class mediaModel extends RelationModel
{
    //关联关系
    protected $_link = array(
        'item' => array(
            'mapping_type' => HAS_MANY,
            'class_name' => 'media_item',
            'foreign_key' => 'media_id',
        	'mapping_order' => 'id asc',
        ),
    );
   /*
    protected $_validate = array(
        array('name','require','{%role_name_empty}'),
        array('name','','{%role_name_exists}',0,'unique',1),
    );

    public function check_name($name, $id='')
    {
        $where = "name='$name'";
        if ($id) {
            $where .= " AND id<>'$id'";
        }
        $id = $this->where($where)->getField('id');
        if ($id) {
            return false;
        } else {
            return true;
        }
    } */
	
	public function getMediaById($id) {
		$where = array(
				'id'=>$id
		);
		$media = $this->relation(true)->where($where)->find();
		return $media;
	}
}