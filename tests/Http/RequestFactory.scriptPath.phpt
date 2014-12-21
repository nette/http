<?php

/**
 * Test: Nette\Http\RequestFactory scriptPath detection.
 */

use Nette\Http\RequestFactory,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new RequestFactory;

test(function() use ($factory) {
	$_SERVER = array(
		'REQUEST_URI' => '/projects/modules-usage/www/',
		'SCRIPT_FILENAME' => 'W:/projects/Modules-Usage/www/index.php',
		'SCRIPT_NAME' => '/projects/modules-usage/www/index.php',
	);

	Assert::same( '/projects/modules-usage/www/', $factory->createHttpRequest()->getUrl()->getScriptPath() );
});


test(function() use ($factory) {
	$_SERVER = array(
		'REQUEST_URI' => '/projects/modules-usage/www/default/add-item',
		'SCRIPT_FILENAME' => 'W:/projects/Modules-Usage/www/index.php',
		'SCRIPT_NAME' => '/projects/Modules-Usage/www/index.php',
	);

	Assert::same( '/projects/modules-usage/www/', $factory->createHttpRequest()->getUrl()->getScriptPath() );
});


test(function() use ($factory) {
	$_SERVER = array(
		'REQUEST_URI' => '/www/index.php',
		'SCRIPT_FILENAME' => 'w:\projects\modules-usage\www\index.php',
		'SCRIPT_NAME' => '/www/index.php',
	);

	Assert::same( '/www/index.php', $factory->createHttpRequest()->getUrl()->getScriptPath() );
});


test(function() use ($factory) {
	$_SERVER = array(
		'REQUEST_URI' => '/www/',
		'SCRIPT_FILENAME' => 'w:\projects\modules-usage\www\index.php',
		'SCRIPT_NAME' => '/www/',
	);

	Assert::same( '/www/', $factory->createHttpRequest()->getUrl()->getScriptPath() );
});


test(function() use ($factory) {
	$_SERVER = array(
		'REQUEST_URI' => '/test/in',
		'SCRIPT_NAME' => '/test/index.php',
	);

	Assert::same( '/test/', $factory->createHttpRequest()->getUrl()->getScriptPath() );
});


test(function() use ($factory) {
	$_SERVER = array(
		'REQUEST_URI' => '/test//',
		'SCRIPT_NAME' => '/test/index.php',
	);

	Assert::same( '/test/', $factory->createHttpRequest()->getUrl()->getScriptPath() );
});


// http://forum.nette.org/cs/5932-lepsi-detekce-requesturi-a-scriptpath
test(function() use ($factory) {
	$_SERVER = array(
		'REQUEST_URI' => '/sign/in/',
		'SCRIPT_NAME' => '/sign/in/',
	);

	Assert::same( '/sign/in/', $factory->createHttpRequest()->getUrl()->getScriptPath() );
});


// http://forum.nette.org/cs/9139-spatny-urlscript-scriptpath
test(function() use ($factory) {
	$_SERVER = array(
		'REQUEST_URI' => '/configuration/',
		'SCRIPT_NAME' => '/configuration/www/index.php',
	);

	Assert::same( '/configuration/', $factory->createHttpRequest()->getUrl()->getScriptPath() );
});
