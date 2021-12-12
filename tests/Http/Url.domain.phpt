<?php

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$url = new Url('http://localhost/path?arg=value');
Assert::same('localhost', $url->getDomain());
Assert::same('localhost', $url->getDomain(0));
Assert::same('localhost', $url->getDomain(1));
Assert::same('localhost', $url->getDomain(2));
Assert::same('', $url->getDomain(-1));


$url = new Url('http://192.168.1.1/');
Assert::same('192.168.1.1', $url->getDomain());
Assert::same('192.168.1.1', $url->getDomain(0));
Assert::same('192.168.1.1', $url->getDomain(1));
Assert::same('192.168.1.1', $url->getDomain(2));
Assert::same('', $url->getDomain(-1));


$url = new Url('http://nette.org/');
Assert::same('nette.org', $url->getDomain());
Assert::same('nette.org', $url->getDomain(0));
Assert::same('org', $url->getDomain(1));
Assert::same('nette.org', $url->getDomain(2));
Assert::same('nette.org', $url->getDomain(3));
Assert::same('nette', $url->getDomain(-1));
Assert::same('', $url->getDomain(-2));


$url = new Url('http://www.nette.org/');
Assert::same('nette.org', $url->getDomain());
Assert::same('www.nette.org', $url->getDomain(0));
Assert::same('org', $url->getDomain(1));
Assert::same('nette.org', $url->getDomain(2));
Assert::same('www.nette.org', $url->getDomain(3));
Assert::same('www.nette', $url->getDomain(-1));
Assert::same('www', $url->getDomain(-2));
Assert::same('', $url->getDomain(-3));
