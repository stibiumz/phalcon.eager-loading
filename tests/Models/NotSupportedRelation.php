<?php

use Phalcon\Mvc\Model\Relation;

class NotSupportedRelation extends AbstractModel {
	protected $id;
	protected $name;
	protected $robot_id;

	public function getSource() {
		return 'bug';
	}

	public function initialize() {
		$this->belongsTo(['id','robot_id'], 'Robots', ['id','robot_id'], [
			'alias'      => 'Robot',
			'foreignKey' => TRUE
		]);
	}
}
