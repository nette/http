<?php

/**
 * Test: Nette\Http\Session setOptions.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->fromGlobals(), new Nette\Http\Response);

Assert::same([
	'cookie_samesite' => 'Lax',
	'cookie_lifetime' => 0,
	'gc_maxlifetime' => 10800,
	'cookie_path' => '/',
	'cookie_domain' => '',
	'cookie_secure' => false,
], $session->getOptions());

$session->setOptions([
	'cookieDomain' => '.domain.com',
]);
Assert::same([
	'cookie_domain' => '.domain.com',
	'cookie_samesite' => 'Lax',
	'cookie_lifetime' => 0,
	'gc_maxlifetime' => 10800,
	'cookie_path' => '/',
	'cookie_secure' => false,
], $session->getOptions());

$session->setOptions([
	'session.cookie_domain' => '.domain.org',
]);
Assert::same([
	'cookie_domain' => '.domain.org',
	'cookie_samesite' => 'Lax',
	'cookie_lifetime' => 0,
	'gc_maxlifetime' => 10800,
	'cookie_path' => '/',
	'cookie_secure' => false,
], $session->getOptions());
