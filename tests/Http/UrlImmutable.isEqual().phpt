<?php

/**
 * Test: Nette\Http\UrlImmutable::isEqual()
 */

declare(strict_types=1);

use Nette\Http\UrlImmutable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$url = new UrlImmutable('http://exampl%65.COM/p%61th?text=foo%20bar+foo&value');
Assert::true($url->isEqual('http://example.com/path?text=foo+bar%20foo&value'));
Assert::true($url->isEqual('http://example.com/%70ath?value&text=foo+bar%20foo'));
Assert::false($url->isEqual('http://example.com/Path?text=foo+bar%20foo&value'));
Assert::false($url->isEqual('http://example.com/path?value&text=foo+bar%20foo#abc'));
Assert::false($url->isEqual('http://example.com/path?text=foo+bar%20foo'));
Assert::false($url->isEqual('https://example.com/path?text=foo+bar%20foo&value'));
Assert::false($url->isEqual('http://example.org/path?text=foo+bar%20foo&value'));


$url = new UrlImmutable('http://example.com');
Assert::true($url->isEqual('http://example.com/'));
Assert::true($url->isEqual('http://example.com'));


$url = new UrlImmutable('http://example.com/?arr[]=item1&arr[]=item2');
Assert::true($url->isEqual('http://example.com/?arr[0]=item1&arr[1]=item2'));
Assert::false($url->isEqual('http://example.com/?arr[1]=item1&arr[0]=item2'));


$url = new UrlImmutable('http://example.com/?a=9999&b=127.0.0.1&c=1234&d=123456789');
Assert::true($url->isEqual('http://example.com/?d=123456789&a=9999&b=127.0.0.1&c=1234'));


$url = new UrlImmutable('http://example.com/?a=123&b=456');
Assert::false($url->isEqual('http://example.com/?a=456&b=123'));


$url = new UrlImmutable('http://user:pass@example.com');
Assert::false($url->isEqual('http://example.com'));


$url = new UrlImmutable('ftp://user:pass@example.com');
Assert::false($url->isEqual('ftp://example.com'));
