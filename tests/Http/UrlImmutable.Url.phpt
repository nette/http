<?php

/**
 * Test: Nette\Http\UrlImmutable http://
 */

declare(strict_types=1);

use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$url = new UrlImmutable(new Url('http://username%3A:password%3A@hostn%61me:60/p%61th/script.php?%61rg=value#%61nchor'));

Assert::same('http://username%3A:password%3A@hostname:60/p%61th/script.php?arg=value#anchor', (string) $url);
Assert::same('"http:\/\/username%3A:password%3A@hostname:60\/p%61th\/script.php?arg=value#anchor"', json_encode($url));
Assert::same('http', $url->scheme);
Assert::same('username:', $url->user);
Assert::same('password:', $url->password);
Assert::same('hostname', $url->host);
Assert::same(60, $url->port);
Assert::same('hostname', $url->getDomain());
Assert::same('hostname', $url->getDomain(0));
Assert::same('', $url->getDomain(-1));
Assert::same('/p%61th/script.php', $url->path);
Assert::same('arg=value', $url->query);
Assert::same(['arg' => 'value'], $url->getQueryParameters());
Assert::same('anchor', $url->fragment);
Assert::same('username%3A:password%3A@hostname:60', $url->authority);
Assert::same('http://username%3A:password%3A@hostname:60', $url->hostUrl);
Assert::same('http://username%3A:password%3A@hostname:60/p%61th/script.php?arg=value#anchor', $url->absoluteUrl);

$url = new UrlImmutable('https://0/0');
Assert::same('https://0', $url->hostUrl);
