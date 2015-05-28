<?php

use Phalcon\Mvc\Model\Relation;

class Part extends AbstractModel {
	protected $id;
	protected $name;

	public function initialize() {
		$this->hasManyToMany('id', 'RobotPart', 'part_id', 'robot_id', 'Robot', 'id', [
			'alias'      => 'Robots',
			'foreignKey' => [
				'action' => Relation::ACTION_RESTRICT
			]
		]);
	}
}
