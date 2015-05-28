<?php

use Phalcon\Mvc\Model\Relation;

class Manufacturer extends AbstractModel {
	protected $id;
	protected $name;

	public function initialize() {
		$this->hasMany('id', 'Robot', 'manufacturer_id', [
			'alias'      => 'Robots',
			'foreignKey' => [
				'action' => Relation::ACTION_CASCADE
			]
		]);
	}
}
