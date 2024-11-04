<?php

/**
 * Test: Nette\Http\SessionSection basic usage.
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

$namespace = $session->getSection('one');
$namespace->set('a', 'apple');
$namespace->set('p', 'pear');
$namespace->set('o', 'orange');
Assert::same('pear', $namespace->get('p'));
Assert::null($namespace->get('undefined'));

foreach ($namespace as $key => $val) {
	$tmp[] = "$key=$val";
}

Assert::same([
	'a=apple',
	'p=pear',
	'o=orange',
], $tmp);

$namespace->remove('a');
Assert::same('p=pear&o=orange', http_build_query(iterator_to_array($namespace->getIterator())));

$namespace->remove(['x', 'p']);
Assert::same('o=orange', http_build_query(iterator_to_array($namespace->getIterator())));

$namespace->remove();
Assert::same('', http_build_query(iterator_to_array($namespace->getIterator())));
