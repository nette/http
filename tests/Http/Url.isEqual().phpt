<?php

/**
 * Test: Nette\Http\Url::isEqual()
 */

use Nette\Http\Url,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$url = new Url('http://exampl%65.COM/p%61th?text=foo%20bar+foo&value');
Assert::true( $url->isEqual('http://example.com/path?text=foo+bar%20foo&value') );
Assert::true( $url->isEqual('http://example.com/%70ath?value&text=foo+bar%20foo') );
Assert::false( $url->isEqual('http://example.com/Path?text=foo+bar%20foo&value') );
Assert::false( $url->isEqual('http://example.com/path?value&text=foo+bar%20foo#abc') );
Assert::false( $url->isEqual('http://example.com/path?text=foo+bar%20foo') );
Assert::false( $url->isEqual('https://example.com/path?text=foo+bar%20foo&value') );
Assert::false( $url->isEqual('http://example.org/path?text=foo+bar%20foo&value') );


$url = new Url('http://example.com');
Assert::true( $url->isEqual('http://example.com/') );
Assert::true( $url->isEqual('http://example.com') );


$url = new Url('http://example.com/?arr[]=item1&arr[]=item2');
Assert::true( $url->isEqual('http://example.com/?arr[0]=item1&arr[1]=item2') );
Assert::false( $url->isEqual('http://example.com/?arr[1]=item1&arr[0]=item2') );


$url = new Url('http://user:pass@example.com');
Assert::true( $url->isEqual('http://example.com') );


$url = new Url('ftp://user:pass@example.com');
Assert::false( $url->isEqual('ftp://example.com') );


$url = new Url;
$url->setScheme('http');
$url->setHost('example.com');
Assert::true( $url->isEqual('http://example.com') );
