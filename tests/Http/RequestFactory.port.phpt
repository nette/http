<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * Test of Nette\Http\RequestFactory port detection
 */
class RequestFactoryPortTest extends Tester\TestCase
{
	/**
	 * @dataProvider providerCreateHttpRequest
	 */
	public function testCreateHttpRequest($expectedPort, array $server)
	{
		$_SERVER = $server;

		$factory = new Nette\Http\RequestFactory;
		Assert::same($expectedPort, $factory->fromGlobals()->getUrl()->getPort());
	}


	public function providerCreateHttpRequest(): array
	{
		return [
			[80, []],
			[8080, ['HTTP_HOST' => 'localhost:8080']],
			[8080, ['SERVER_NAME' => 'localhost:8080']],
			[8080, ['HTTP_HOST' => 'localhost:8080', 'SERVER_PORT' => '666']],
			[8080, ['SERVER_NAME' => 'localhost:8080', 'SERVER_PORT' => '666']],
			[80, ['HTTP_HOST' => 'localhost', 'SERVER_PORT' => '8080']],
			[8080, ['SERVER_NAME' => 'localhost', 'SERVER_PORT' => '8080']],

			[80, ['HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['HTTP_HOST' => 'localhost:8080', 'HTTP_X_FORWARDED_PORT' => '666']],
			[8080, ['SERVER_NAME' => 'localhost:8080', 'HTTP_X_FORWARDED_PORT' => '666']],
			[8080, ['HTTP_HOST' => 'localhost:8080', 'SERVER_PORT' => '80', 'HTTP_X_FORWARDED_PORT' => '666']],
			[8080, ['SERVER_NAME' => 'localhost:8080', 'SERVER_PORT' => '80', 'HTTP_X_FORWARDED_PORT' => '666']],
			[80, ['HTTP_HOST' => 'localhost', 'HTTP_X_FORWARDED_PORT' => '666']],
			[80, ['SERVER_NAME' => 'localhost', 'HTTP_X_FORWARDED_PORT' => '666']],
			[80, ['HTTP_HOST' => 'localhost', 'SERVER_PORT' => '8080', 'HTTP_X_FORWARDED_PORT' => '666']],
			[8080, ['SERVER_NAME' => 'localhost', 'SERVER_PORT' => '8080', 'HTTP_X_FORWARDED_PORT' => '666']],
			[44443, ['HTTPS' => 'on', 'SERVER_NAME' => 'localhost:44443', 'HTTP_X_FORWARDED_PORT' => '666']],
		];
	}


	/**
	 * @dataProvider providerCreateHttpRequestWithTrustedProxy
	 */
	public function testCreateHttpRequestWithTrustedProxy($expectedPort, array $server)
	{
		$_SERVER = array_merge(['REMOTE_ADDR' => '10.0.0.1'], $server);

		$factory = new Nette\Http\RequestFactory;
		$factory->setProxy(['10.0.0.1']);
		Assert::same($expectedPort, $factory->fromGlobals()->getUrl()->getPort());
	}


	public function providerCreateHttpRequestWithTrustedProxy(): array
	{
		return [
			[8080, ['HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['HTTP_HOST' => 'localhost:666', 'HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['SERVER_NAME' => 'localhost:666', 'HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['HTTP_HOST' => 'localhost:666', 'SERVER_PORT' => '80', 'HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['SERVER_NAME' => 'localhost:666', 'SERVER_PORT' => '80', 'HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['HTTP_HOST' => 'localhost', 'HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['SERVER_NAME' => 'localhost', 'HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['HTTP_HOST' => 'localhost', 'SERVER_PORT' => '666', 'HTTP_X_FORWARDED_PORT' => '8080']],
			[8080, ['SERVER_NAME' => 'localhost', 'SERVER_PORT' => '666', 'HTTP_X_FORWARDED_PORT' => '8080']],
			[44443, ['HTTPS' => 'on', 'SERVER_NAME' => 'localhost:666', 'HTTP_X_FORWARDED_PORT' => '44443']],
			[443, ['HTTP_FORWARDED' => 'for=192.168.1.1;host=example.com;proto=https']],
			[44443, ['HTTP_FORWARDED' => 'for=192.168.1.1;host=example.com:44443;proto=https']],
			[80, ['HTTP_FORWARDED' => 'for=192.168.1.1;host=example.com;proto=http']],
			[8080, ['HTTP_FORWARDED' => 'for=192.168.1.1;host=example.com:8080;proto=http']],
		];
	}
}

$test = new RequestFactoryPortTest;
$test->run();
