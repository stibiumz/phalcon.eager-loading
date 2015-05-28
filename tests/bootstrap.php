<?php 

const DB_CONFIG = [
	'host'     => 'localhost',
	'port'     => '3306',
	'username' => 'root',
	'password' => '',
	'dbname'   => 'eager_loading_tests',
	'charset'  => 'utf8mb4',
];

$di = new Phalcon\DI;

$di->set('modelsMetadata', function () {
	return new Phalcon\Mvc\Model\Metadata\Memory;
}, TRUE);

$di->set('modelsManager', function () {
	return new Phalcon\Mvc\Model\Manager;
}, TRUE);

$di->set('db', function () {
	return new Phalcon\Db\Adapter\Pdo\Mysql(DB_CONFIG);
}, TRUE);

require_once __DIR__ . '/../src/EagerLoadingTrait.php';
require_once __DIR__ . '/../src/EagerLoading/QueryBuilder.php';
require_once __DIR__ . '/../src/EagerLoading/Loader.php';
require_once __DIR__ . '/../src/EagerLoading/EagerLoad.php';

spl_autoload_register(function ($class) {
	if (ctype_alpha($class)) {
		$file = __DIR__ . "/Models/{$class}.php";

		if (file_exists($file)) {
			require $file;
		}
	}
});
