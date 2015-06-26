<?php

use Sb\Framework\Mvc\Model\EagerLoading\Loader;
use Sb\Framework\Mvc\Model\EagerLoading\QueryBuilder;

class EagerLoadingTests extends PHPUnit_Framework_TestCase {
	public function testBelongsTo() {
		$rawly = Bug::findFirstById(1);
		$rawly->robot;

		$eagerly = Loader::fromModel(Bug::findFirstById(1), 'Robot');

		$this->assertTrue(property_exists($eagerly, 'robot'));
		$this->assertInstanceOf('Robot', $eagerly->robot);
		$this->assertEquals($rawly->robot->readAttribute('id'), $eagerly->robot->readAttribute('id'));

		// Reverse
		$rawly = Robot::findFirstById(2);
		$rawly->bugs = $this->_resultSetToEagerLoadingEquivalent($rawly->bugs);

		$eagerly = Loader::fromModel(Robot::findFirstById(2), 'Bugs');

		$this->assertTrue(property_exists($eagerly, 'bugs'));
		$this->assertContainsOnlyInstancesOf('Bug', $eagerly->bugs);

		$getIds = function ($obj) {
			return $obj->readAttribute('id');
		};

		$this->assertEquals(array_map($getIds, $rawly->bugs), array_map($getIds, $eagerly->bugs));
		$this->assertEmpty(Loader::fromModel(Robot::findFirstById(1), 'Bugs')->bugs);

		// Test from multiple
		$rawly = $this->_resultSetToEagerLoadingEquivalent(Bug::find(['limit' => 10]));
		foreach ($rawly as $bug) {
			$bug->robot;
		}

		$eagerly = Loader::fromResultset(Bug::find(['limit' => 10]), 'Robot');

		$this->assertTrue(is_array($eagerly));
		$this->assertTrue(array_reduce($eagerly, function ($res, $bug) { return $res && property_exists($bug, 'robot'); }, TRUE));

		$getIds = function ($obj) {
			return property_exists($obj, 'robot') && isset ($obj->robot) ? $obj->robot->readAttribute('id') : NULL;
		};

		$this->assertEquals(array_map($getIds, $rawly), array_map($getIds, $eagerly));
	}

	public function testBelongsToDeep() {
		$rawly = Manufacturer::findFirstById(1);
		$rawly->robots = $this->_resultSetToEagerLoadingEquivalent($rawly->robots);

		foreach ($rawly->robots as $robot) {
			$robot->parent;
		}

		$eagerly = Loader::fromModel(Manufacturer::findFirstById(1), 'Robots.Parent');

		$this->assertTrue(property_exists($eagerly->robots[0], 'parent'));
		$this->assertNull($eagerly->robots[0]->parent);
		$this->assertInstanceOf('Robot', $eagerly->robots[2]->parent);

		$getIds = function ($obj) {
			return property_exists($obj, 'parent') && isset ($obj->parent) ? $obj->parent->readAttribute('id') : NULL;
		};

		$this->assertEquals(array_map($getIds, $eagerly->robots), array_map($getIds, $rawly->robots));
	}

	public function testHasOne() {
		$rawly = Robot::findFirstById(1);
		$rawly->purpose;

		$eagerly = Loader::fromModel(Robot::findFirstById(1), 'Purpose');

		$this->assertTrue(property_exists($eagerly, 'purpose'));
		$this->assertInstanceOf('Purpose', $eagerly->purpose);
		$this->assertEquals($rawly->purpose->readAttribute('id'), $eagerly->purpose->readAttribute('id'));
	}

	public function testHasMany() {
		$rawly = Manufacturer::findFirstById(1);
		$rawly->robots;

		$eagerly = Loader::fromModel(Manufacturer::findFirstById(1), 'Robots');

		$this->assertTrue(property_exists($eagerly, 'robots'));
		$this->assertTrue(is_array($eagerly->robots));
		$this->assertSame(count($eagerly->robots), $rawly->robots->count());

		$getIds = function ($arr) {
			$ret = [];

			foreach ($arr as $r) {
				if (is_object($r))
					$ret[] = $r->readAttribute('id');
			}

			return $ret;
		};

		$this->assertEquals(
			$getIds($this->_resultSetToEagerLoadingEquivalent($rawly->robots)),
			$getIds($eagerly->robots)
		);
	}

	public function testHasManyToMany() {
		$rawly = Robot::findFirstById(1);
		$rawly->parts;

		$eagerly = Loader::fromModel(Robot::findFirstById(1), 'Parts');

		$this->assertTrue(property_exists($eagerly, 'parts'));
		$this->assertTrue(is_array($eagerly->parts));
		$this->assertSame(count($eagerly->parts), $rawly->parts->count());

		$getIds = function ($arr) {
			$ret = [];

			foreach ($arr as $r) {
				if (is_object($r))
					$ret[] = $r->readAttribute('id');
			}

			return $ret;
		};

		$this->assertEquals(
			$getIds($this->_resultSetToEagerLoadingEquivalent($rawly->parts)),
			$getIds($eagerly->parts)
		);
	}

	public function testModelMethods() {
		$this->assertTrue(is_array(Robot::with('Parts')));
		$this->assertTrue(is_object(Robot::findFirstById(1)->load('Parts')));
		$this->assertTrue(is_object(Robot::findFirstWith('Parts', ['id = 1'])));
	}

	/**
	 * @dataProvider dp1
	 */
	public function testShouldThrowBadMethodCallExceptionIfArgumentsWereNotProvided($method) {
		$this->setExpectedException('BadMethodCallException');
		call_user_func(['Robot', $method]);
	}

	public function dp1() {
		return [['with'], ['findFirstWith']];
	}

	/**
	 * @dataProvider dp2
	 */
	public function testShouldThrowLogicExceptionIfTheEntityWillBeIncomplete($method, $args) {
		$this->setExpectedException('LogicException');
		call_user_func_array(['Robot', $method], $args);
	}

	public function dp2() {
		return [
			['with', ['Parts', ['columns' => 'id']]],
			['findFirstWith', ['Parts', ['columns' => 'id']]],
			['with', [['Parts' => function ($builder) { $builder->columns(['id']); }]]],
		];
	}

	/**
	 * @dataProvider dp3
	 */
	public function testShouldThrowInvalidArgumentExceptionIfLoaderSubjectIsNotValid($args) {
		$this->setExpectedException('InvalidArgumentException');
		(new ReflectionClass(Loader::class))->newInstance($args);
	}

	public function dp3() {
		return [
			[range(0, 5)],
			[[Robot::findFirstById(1), Bug::findFirstById(1)]]
		];
	}

	/**
	 * @dataProvider dp4
	 */
	public function testShouldThrowRuntimeExceptionIfTheRelationIsNotDefinedOrSupported($args) {
		$this->setExpectedException('RuntimeException');
		(new ReflectionClass(Loader::class))->newInstanceArgs($args)->execute();
	}

	public function dp4() {
		return [
			[[Robot::findFirst(), 'NotSupportedRelations']],
			[[Robot::findFirst(), 'NonexistentRelation']],
		];
	}

	public function testManyEagerLoadsAndConstraints() {
		$manufacturers = Manufacturer::with([
			'Robots' => function ($builder) {
				$builder->where('id < 25');
			},
			'Robots.Bugs' => function ($builder) {
				$builder->limit(2);
			},
			'Robots.Parts'
		], ['id < 50']);

		$this->assertEquals(
			array_sum(array_map(function ($o) { return count($o->robots); }, $manufacturers)),
			Robot::count(['id < 25 AND manufacturer_id < 50'])
		);

		$this->assertEquals(
			array_sum(array_map(function ($o) {
				$c = 0; foreach ($o->robots as $r) $c += count($r->bugs); return $c;
			}, $manufacturers)),
			2
		);

		$manufacturers = Manufacturer::with([
			'Robots.Bugs' => function ($builder) {
				$builder->where('id > 10000');
			}
		], ['limit' => 5, 'order' => 'id ASC']);

		$this->assertEquals(
			array_sum(array_map(function ($o) { return count($o->robots); }, $manufacturers)),
			Robot::count(['manufacturer_id < 6'])
		);

		$robots = array ();
		foreach ($manufacturers as $m) $robots = array_merge($robots, $m->robots);

		$this->assertEquals(
			array_sum(array_map(function ($o) { return count($o->bugs); }, $robots)),
			0
		);
	}

	public function testManyEagerLoadsAndConstraintsWithLoaderFromResultset() {
		$manufacturers = Loader::fromResultset(
			Manufacturer::find(),
			[
				'Robots.Bugs' => function (QueryBuilder $builder) {
					$builder->where('Bug.id > 10');
				}
			]
		);

		$robots = array ();
		foreach ($manufacturers as $m) $robots = array_merge($robots, $m->robots);

		$this->assertEquals(
			array_sum(array_map(function ($o) { return count($o->bugs); }, $robots)),
			134
		);
	}

	public function testIssue4() {
		// Has many -> Belongs to
		// Should be the same for Has many -> Has one
		$this->assertEquals((new Loader(Robot::findFirstById(1), 'Bugs.Robot'))->execute()->get()->bugs, []);
	}

	protected function _resultSetToEagerLoadingEquivalent($val) {
		$ret = $val;

		if ($val instanceof Phalcon\Mvc\Model\Resultset\Simple) {
			$ret = [];

			if ($val->count() > 0) {
				foreach ($val as $model) {
					$ret[] = $model;
				}
			}
		}

		return $ret;
	}
}
