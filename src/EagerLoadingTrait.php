<?php namespace Sb\Framework\Mvc\Model;

use Sb\Framework\Mvc\Model\EagerLoading\Loader;

trait EagerLoadingTrait {

	/**
	 * <code>
	 * <?php
	 *
	 * $limit  = 100;
	 * $offset = max(0, $this->request->getQuery('page', 'int') - 1) * $limit;
	 *
	 * $manufacturers = Manufacturer::with('Robots.Parts', [
	 *     'limit' => [$limit, $offset]
	 * ]);	
	 *
	 * foreach ($manufacturers as $manufacturer) {
	 *     foreach ($manufacturer->robots as $robot) {
	 *	       foreach ($robot->parts as $part) { ... }
	 *     }
	 * }
	 *
	 * </code>
	 *
	 * @param mixed ...$arguments
	 * @return Phalcon\Mvc\ModelInterface[]
	 */
	static public function with(...$arguments) {
		if (! empty ($arguments)) {
			$numArgs    = count($arguments);
			$lastArg    = $numArgs - 1;
			$parameters = NULL;

			if ($numArgs >= 2 && is_array($arguments[$lastArg])) {
				$parameters = $arguments[$lastArg];
				
				unset ($arguments[$lastArg]);

				if (isset ($parameters['columns'])) {
					throw new \LogicException('Results from database must be full models, do not use `columns` key');
				}
			}
		}
		else {
			throw new \BadMethodCallException(sprintf('%s requires at least one argument', __METHOD__));
		}

		$ret = static::find($parameters);

		if ($ret->count()) {
			$ret = Loader::fromResultset($ret, ...$arguments);
		}

		return $ret;
	}

	/**
	 * Same as EagerLoadingTrait::with() for a single record
	 *
	 * @param mixed ...$arguments
	 * @return false|Phalcon\Mvc\ModelInterface
	 */
	static public function findFirstWith(...$arguments) {
		if (! empty ($arguments)) {
			$numArgs    = count($arguments);
			$lastArg    = $numArgs - 1;
			$parameters = NULL;

			if ($numArgs >= 2 && is_array($arguments[$lastArg])) {
				$parameters = $arguments[$lastArg];
				
				unset ($arguments[$lastArg]);

				if (isset ($parameters['columns'])) {
					throw new \LogicException('Results from database must be full models, do not use `columns` key');
				}
			}
		}
		else {
			throw new \BadMethodCallException(sprintf('%s requires at least one argument', __METHOD__));
		}

		if ($ret = static::findFirst($parameters)) {
			$ret = Loader::fromModel($ret, ...$arguments);
		}

		return $ret;
	}

	/**
	 * <code>
	 * <?php
	 *
	 * $manufacturer = Manufacturer::findFirstById(51);
	 *
	 * $manufacturer->load('Robots.Parts');	
	 *
	 * foreach ($manufacturer->robots as $robot) {
	 *    foreach ($robot->parts as $part) { ... }
	 * }
	 * </code>
	 *
	 * @param mixed ...$arguments
	 * @return self
	 */
	public function load(...$arguments) {
		return Loader::fromModel($this, ...$arguments);
	}
}
