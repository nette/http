<?php

/**
 * Test: Nette\Http\RequestFactory and host.
 */

use Nette\Http\RequestFactory,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


//$_SERVER = array(
//	'HTTP_HOST' => 'localhost',
//);
//$factory = new RequestFactory;
//Assert::same( 'http://localhost/', (string) $factory->createHttpRequest()->getUrl() );
//
//
//$_SERVER = array(
//	'HTTP_HOST' => 'www-x.nette.org',
//);
//$factory = new RequestFactory;
//Assert::same( 'http://www-x.nette.org/', (string) $factory->createHttpRequest()->getUrl() );
//
//
//$_SERVER = array(
//	'HTTP_HOST' => '192.168.0.1:8080',
//);
//$factory = new RequestFactory;
//Assert::same( 'http://192.168.0.1:8080/', (string) $factory->createHttpRequest()->getUrl() );
//
//
//$_SERVER = array(
//	'HTTP_HOST' => '[::1aF]:8080',
//);
//$factory = new RequestFactory;
//Assert::same( 'http://[::1af]:8080/', (string) $factory->createHttpRequest()->getUrl() );
//
//
//$_SERVER = array(
//	'HTTP_HOST' => "a.cz\n",
//);
//$factory = new RequestFactory;
//Assert::same( 'http:///', (string) $factory->createHttpRequest()->getUrl() );
//
//
//$_SERVER = array(
//	'HTTP_HOST' => 'AB',
//);
//$factory = new RequestFactory;
//Assert::same( 'http://ab/', (string) $factory->createHttpRequest()->getUrl() );
//

//$_SERVER = array(
//	'HTTP_HOST' => 'a' . str_repeat('.a', 4000000),
//);
//
//$time = -microtime(TRUE);
//$factory = new RequestFactory;
//$factory->createHttpRequest();
//$time += microtime(TRUE);
//Assert::true( $time < 1 );
//echo $time;

// Assert::same( 'http://ab/', (string) $factory->createHttpRequest()->getUrl() );


$_SERVER = [
	'REQUEST_URI' => '/aaaaaaaaaaaaaaaaaaaaaaaaa.php/' . str_repeat('A', 1e6) . '?' . str_repeat('B', 1e6) . '=' . str_repeat('C', 1e6),
	'SCRIPT_NAME' => '/aaaaaaaaaaaaaaaaaaaaaaaaa.php'
];


$factory = new RequestFactory;
$factory->createHttpRequest();

$time = -microtime(TRUE);
for ($i = 0; $i < 10; $i++) {
	$factory = new RequestFactory;
	$factory->createHttpRequest();
}
$time += microtime(TRUE);
printf("Time: %.2f ms\n", $time * 1e3);
