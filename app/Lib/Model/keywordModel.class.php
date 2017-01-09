<?php
class keywordModel extends RelationModel {
	
	
	protected $_link = array(
			//关联角色
			'media' => array(
					'mapping_type' => BELONGS_TO,
					'class_name' => 'media',
					'foreign_key' => 'media_id',
			)
	);
}