<?php declare(strict_types=1);

/**
 * Test: HttpExtension default proxyHeaders trusts X-Forwarded-For only.
 */

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$_SERVER = [
	'REMOTE_ADDR' => '10.0.0.1',
	'HTTP_FORWARDED' => 'for=6.6.6.6',
	'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
];

$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
$compiler->addConfig(['http' => ['proxy' => ['10.0.0.1']]]);
eval($compiler->compile());

$container = new Container;

// no proxyHeaders configured → default trusts X-Forwarded-For and ignores Forwarded
Assert::same('1.2.3.4', $container->getService('http.request')->getRemoteAddress());
