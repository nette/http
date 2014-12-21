<?php

/**
 * Test: Nette\Http\Url http://
 */

use Nette\Http\Url,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$url = new Url('http://username%3A:password%3A@hostn%61me:60/p%61th/script.php?%61rg=value#%61nchor');

Assert::same( 'http://hostname:60/p%61th/script.php?arg=value#anchor',  (string) $url );
Assert::same( 'http',  $url->scheme );
Assert::same( 'username:',  $url->user );
Assert::same( 'password:',  $url->password );
Assert::same( 'hostname',  $url->host );
Assert::same( 60,  $url->port );
Assert::same( '/p%61th/script.php',  $url->path );
Assert::same( '/p%61th/',  $url->basePath );
Assert::same( 'arg=value',  $url->query );
Assert::same( 'anchor',  $url->fragment );
Assert::same( 'hostname:60',  $url->authority );
Assert::same( 'http://hostname:60',  $url->hostUrl );
Assert::same( 'http://hostname:60/p%61th/script.php?arg=value#anchor',  $url->absoluteUrl );
Assert::same( 'http://hostname:60/p%61th/',  $url->baseUrl );
Assert::same( 'script.php?arg=value#anchor',  $url->relativeUrl );

$url->scheme = NULL;
Assert::same( '//username%3A:password%3A@hostname:60/p%61th/script.php?arg=value#anchor',  $url->absoluteUrl );
