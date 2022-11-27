<?php

/**
 * Test: Nette\Http\Url http://
 */

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$url = new Url('http://username%3A:password%3A@hostn%61me:60/p%61th/script.php?%61rg=value#%61nchor');

Assert::same('http://username%3A:password%3A@hostname:60/p%61th/script.php?arg=value#anchor', (string) $url);
Assert::same('"http:\/\/username%3A:password%3A@hostname:60\/p%61th\/script.php?arg=value#anchor"', json_encode($url));
Assert::same('http', $url->scheme);
Assert::same('username:', $url->user);
Assert::same('password:', $url->password);
Assert::same('hostname', $url->host);
Assert::same(60, $url->port);
Assert::same(80, $url->getDefaultPort());
Assert::same('/p%61th/script.php', $url->path);
Assert::same('/p%61th/', $url->basePath);
Assert::same('arg=value', $url->query);
Assert::same('anchor', $url->fragment);
Assert::same('username%3A:password%3A@hostname:60', $url->authority);
Assert::same('http://username%3A:password%3A@hostname:60', $url->hostUrl);
Assert::same('http://username%3A:password%3A@hostname:60/p%61th/script.php?arg=value#anchor', $url->absoluteUrl);
Assert::same('http://username%3A:password%3A@hostname:60/p%61th/', $url->baseUrl);
Assert::same('script.php?arg=value#anchor', $url->relativeUrl);

$url->setPath('');
Assert::same('/', $url->getPath());

$url->setPath('/');
Assert::same('/', $url->getPath());

$url->setPath('x');
Assert::same('/x', $url->getPath());

$url->setPath('/x');
Assert::same('/x', $url->getPath());

$url->setScheme('');
Assert::same('//username%3A:password%3A@hostname:60/x?arg=value#anchor', $url->absoluteUrl);
Assert::null($url->getDefaultPort());

$url->setPath('');
Assert::same('/', $url->getPath());

$url->setHost('')->setPath('');
Assert::same('?arg=value#anchor', $url->absoluteUrl);

$url->setPath('');
Assert::same('', $url->getPath());

$url = new Url('https://0/0');
Assert::same('https://0', $url->hostUrl);
Assert::same(443, $url->getDefaultPort());
