<?php

/**
 * Test: Nette\Http\Url canonicalize.
 */

use Nette\Http\Url,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$url = new Url('http://hostname/path?arg=value&arg2=v%20a%26l%3Du%2Be');
$url->canonicalize();
Assert::same( 'http://hostname/path?arg=value&arg2=v a%26l%3Du%2Be', (string) $url );


$url = new Url('http://username%3A:password%3A@hostN%61me:60/p%61th%2f%25()?arg=value&arg2=v%20a%26l%3Du%2Be#%61nchor');
$url->canonicalize();
Assert::same( 'http://hostname:60/path%2F%25()?arg=value&arg2=v a%26l%3Du%2Be#anchor', (string) $url );
