<?php

/**
 * Test: Nette\Http\Session setOptions.
 */

use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->createHttpRequest(), new Nette\Http\Response);

Assert::same(array(
	'referer_check' => '',
	'use_cookies' => 1,
	'use_only_cookies' => 1,
	'use_trans_sid' => 0,
	'cookie_lifetime' => 0,
	'cookie_path' => '/',
	'cookie_domain' => '',
	'cookie_secure' => FALSE,
	'cookie_httponly' => TRUE,
	'gc_maxlifetime' => 10800,
	'cache_limiter' => NULL,
	'cache_expire' => NULL,
	'hash_function' => NULL,
	'hash_bits_per_character' => NULL,
), $session->getOptions());

$session->setOptions(array(
	'cookieDomain' => '.domain.com',
));
Assert::same(array(
	'cookie_domain' => '.domain.com',
	'referer_check' => '',
	'use_cookies' => 1,
	'use_only_cookies' => 1,
	'use_trans_sid' => 0,
	'cookie_lifetime' => 0,
	'cookie_path' => '/',
	'cookie_secure' => FALSE,
	'cookie_httponly' => TRUE,
	'gc_maxlifetime' => 10800,
	'cache_limiter' => NULL,
	'cache_expire' => NULL,
	'hash_function' => NULL,
	'hash_bits_per_character' => NULL,
), $session->getOptions());

$session->setOptions(array(
	'session.cookie_domain' => '.domain.org',
));
Assert::same(array(
	'cookie_domain' => '.domain.org',
	'referer_check' => '',
	'use_cookies' => 1,
	'use_only_cookies' => 1,
	'use_trans_sid' => 0,
	'cookie_lifetime' => 0,
	'cookie_path' => '/',
	'cookie_secure' => FALSE,
	'cookie_httponly' => TRUE,
	'gc_maxlifetime' => 10800,
	'cache_limiter' => NULL,
	'cache_expire' => NULL,
	'hash_function' => NULL,
	'hash_bits_per_character' => NULL,
), $session->getOptions());
