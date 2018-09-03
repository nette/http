<?php

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$response = new Nette\Http\Response;
$session = new Nette\Http\Session($factory->createHttpRequest(), $response);

$session->setOptions([]);

$response->cookiePath = '/user/';
$response->cookieDomain = 'nette.org';
$response->cookieSecure = true;

Assert::same([
	'referer_check' => '',
	'use_cookies' => 1,
	'use_only_cookies' => 1,
	'use_trans_sid' => 0,
	'use_strict_mode' => 1,
	'cookie_lifetime' => 0,
	'cookie_httponly' => true,
	'cookie_samesite' => 'Lax',
	'gc_maxlifetime' => 10800,
	'cookie_path' => '/user/',
	'cookie_domain' => 'nette.org',
	'cookie_secure' => true,
], $session->getOptions());

$session->setOptions([
	'cookie_domain' => '.domain.com',
]);

Assert::same('nette.org', $response->cookieDomain);
