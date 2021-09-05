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
$namespace->a = 'apple';
$namespace->set('p', 'pear');
$namespace['o'] = 'orange';

Assert::same('apple', $namespace->a);
Assert::same('pear', $namespace->get('p'));
Assert::same('orange', $namespace['o']);

foreach ($namespace as $key => $val) {
	$tmp[] = "$key=$val";
}
Assert::same([
	'a=apple',
	'p=pear',
	'o=orange',
], $tmp);


Assert::true(isset($namespace['p']));
Assert::true(isset($namespace->o));
Assert::false(isset($namespace->undefined));

unset($namespace['a'], $namespace->o, $namespace->undef);
$namespace->remove('p');
$namespace->remove(['x']);




Assert::same('', http_build_query(iterator_to_array($namespace->getIterator())));
