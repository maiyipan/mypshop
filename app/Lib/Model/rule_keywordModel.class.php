<?php
class rule_keywordModel extends RelationModel {
	
	
	protected $_link = array(
			//关联角色
			'media' => array(
					'mapping_type' => BELONGS_TO,
					'class_name' => 'media',
					'foreign_key' => 'media_id',
					'condition' =>'shopid'
			),
			'keyword' => array(
					'mapping_type' => HAS_MANY,
					'class_name' => 'keyword',
					'foreign_key' => 'rule_keyword_id',
			)
	);
}