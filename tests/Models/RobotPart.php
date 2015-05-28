<?php

class RobotPart extends AbstractModel {
	protected $robot_id;
	protected $part_id;

	public function initialize() {
		$this->belongsTo('robot_id', 'Robot', 'id', [
			'foreignKey' => TRUE
		]);

		$this->belongsTo('part_id', 'Part', 'id', [
			'foreignKey' => TRUE
		]);
	}
}
