<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


/**
 * Test of Nette\Http\RequestFactory schema detection
 */
class RequestFactorySchemeTest extends Tester\TestCase
{
	/**
	 * @covers       RequestFactory::getScheme
	 * @dataProvider providerCreateHttpRequest
	 */
	public function testCreateHttpRequest($expectedScheme, array $server)
	{
		$_SERVER = $server;

		$factory = new Nette\Http\RequestFactory;
		$url = $factory->fromGlobals()->getUrl();

		Assert::same($expectedScheme, $url->getScheme());
		Assert::same($expectedScheme === 'https' ? 443 : 80, $url->getPort());
	}


	public function providerCreateHttpRequest(): array
	{
		return [
			['http', ['SERVER_NAME' => 'localhost:80']],
			['http', ['SERVER_NAME' => 'localhost:80', 'HTTPS' => '']],
			['http', ['SERVER_NAME' => 'localhost:80', 'HTTPS' => 'off']],
			['http', ['SERVER_NAME' => 'localhost:80', 'HTTP_X_FORWARDED_PROTO' => 'https']],
			['http', ['SERVER_NAME' => 'localhost:80', 'HTTP_X_FORWARDED_PORT' => '443']],
			['http', ['SERVER_NAME' => 'localhost:80', 'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '443']],
			['https', ['SERVER_NAME' => 'localhost:443', 'HTTPS' => 'on']],
			['https', ['SERVER_NAME' => 'localhost:443', 'HTTPS' => 'anything']],
			['https', ['SERVER_NAME' => 'localhost:443', 'HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http']],
			['https', ['SERVER_NAME' => 'localhost:443', 'HTTPS' => 'on', 'HTTP_X_FORWARDED_PORT' => '80']],
			['https', ['SERVER_NAME' => 'localhost:443', 'HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http', 'HTTP_X_FORWARDED_PORT' => '80']],
		];
	}


	/**
	 * @covers       RequestFactory::getScheme
	 * @dataProvider providerCreateHttpRequestWithTrustedProxy
	 */
	public function testCreateHttpRequestWithTrustedProxy($expectedScheme, $expectedPort, array $server)
	{
		$_SERVER = array_merge(['REMOTE_ADDR' => '10.0.0.1'], $server);

		$factory = new Nette\Http\RequestFactory;
		$factory->setProxy(['10.0.0.1']);
		$url = $factory->fromGlobals()->getUrl();

		Assert::same($expectedScheme, $url->getScheme());
		Assert::same($expectedPort, $url->getPort());
	}


	public function providerCreateHttpRequestWithTrustedProxy(): array
	{
		return [
			['http', 80, ['SERVER_NAME' => 'localhost:80', 'HTTP_X_FORWARDED_PROTO' => 'http']],
			['http', 80, ['SERVER_NAME' => 'localhost:443', 'HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http']],
			['http', 80, ['SERVER_NAME' => 'localhost:443', 'HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'something-unexpected']],
			['http', 443, ['SERVER_NAME' => 'localhost:443', 'HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http', 'HTTP_X_FORWARDED_PORT' => '443']],
			['https', 443, ['SERVER_NAME' => 'localhost:80', 'HTTP_X_FORWARDED_PROTO' => 'https']],
			['https', 443, ['SERVER_NAME' => 'localhost:80', 'HTTPS' => 'off', 'HTTP_X_FORWARDED_PROTO' => 'https']],
			['https', 80, ['SERVER_NAME' => 'localhost:80', 'HTTPS' => 'off', 'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '80']],
		];
	}
}


$test = new RequestFactorySchemeTest;
$test->run();
