<?php

/**
 * Test: Nette\Http\Session storage.
 * @phpversion 5.4
 */

use Nette\Http\Session,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class MySessionStorageExtension extends \SessionHandler
{
}


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->createHttpRequest(), new Nette\Http\Response);

$session->setOptions(array('save_handler' => 'redis', 'save_path' => 'tcp://127.0.0.1:6379')); //or anything different from default - memcached, memcache, mysql..
Assert::same('files', ini_get('session.save_handler'));
$session->setHandler(new MySessionStorageExtension);
Assert::same('files', ini_get('session.save_handler'));
$session->start(); //and configure();
Assert::same('user', ini_get('session.save_handler'));
