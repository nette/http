<?php

/**
 * Test: Nette\Http\Url ftp://
 */

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$url = new Url('ftp://ftp.is.co.za/rfc/rfc3986.txt');

Assert::same('ftp', $url->scheme);
Assert::same('', @$url->user); // deprecated
Assert::same('', @$url->password); // deprecated
Assert::same('ftp.is.co.za', $url->host);
Assert::same(21, $url->port);
Assert::same('/rfc/rfc3986.txt', $url->path);
Assert::same('', $url->query);
Assert::same('', $url->fragment);
Assert::same('ftp.is.co.za', $url->authority);
Assert::same('ftp://ftp.is.co.za', $url->hostUrl);
Assert::same('ftp://ftp.is.co.za/rfc/rfc3986.txt', $url->absoluteUrl);
