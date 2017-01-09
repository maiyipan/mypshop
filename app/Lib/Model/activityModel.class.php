<?php
class activityModel extends RelationModel {
    //关联关系
    protected $_link = array(
        'id' => array(
            'mapping_type' => HAS_MANY,
            'class_name' => 'shop_expand',
            'foreign_key' => 'activity_id',
        ),
    );
}