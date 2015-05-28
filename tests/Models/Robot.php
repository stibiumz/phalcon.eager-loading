<?php

use Phalcon\Mvc\Model\Relation;

class Robot extends AbstractModel {
	protected $id;
	protected $name;
	protected $parent_id;
	protected $manufacturer_id;

	public function initialize() {
		$this->belongsTo('manufacturer_id', 'Manufacturer', 'id', [
			'alias'      => 'Manufacturer',
			'foreignKey' => TRUE
		]);

		// Recursive relation
		$this->belongsTo('parent_id', 'Robot', 'id', [
			'alias'      => 'Parent',
			'foreignKey' => TRUE
		]);

		$this->hasMany('id', 'Robot', 'parent_id', [
			'alias'      => 'Children',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);

		$this->hasOne('id', 'Purpose', 'robot_id', [
			'alias'      => 'Purpose',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
		
		$this->hasMany('id', 'Bug', 'robot_id', [
			'alias'      => 'Bugs',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);

		$this->hasManyToMany('id', 'RobotPart', 'robot_id', 'part_id', 'Part', 'id', [
			'alias'      => 'Parts',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);

		// Wrong relation
		$this->hasMany(['id','parent_id'], 'NotSupportedRelation', ['id','robot_id'], [
			'alias'      => 'NotSupportedRelations',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
	}
}
