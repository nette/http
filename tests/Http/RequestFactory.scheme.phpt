<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * Test of Nette\Http\RequestFactory schema detection
 */
class RequestFactorySchemeTest extends Tester\TestCase
{

	/**
	 * @covers RequestFactory::getScheme
	 * @dataProvider providerCreateHttpRequest
	 */
	public function testCreateHttpRequest($expectedScheme, array $server)
	{
		$_SERVER = $server;

		$factory = new Nette\Http\RequestFactory;
		Assert::same($expectedScheme, $factory->createHttpRequest()->getUrl()->getScheme());
	}

	/**
	 * @return array
	 */
	public function providerCreateHttpRequest()
	{
		return [
			['http', []],
			['http', ['HTTPS' => '']],
			['http', ['HTTPS' => 'off']],
			['http', ['HTTP_X_FORWARDED_PROTO' => 'https']],
			['http', ['HTTP_X_FORWARDED_PORT' => '443']],
			['http', ['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '443']],

			['https', ['HTTPS' => 'on']],
			['https', ['HTTPS' => 'anything']],
			['https', ['HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http']],
			['https', ['HTTPS' => 'on', 'HTTP_X_FORWARDED_PORT' => '80']],
			['https', ['HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http', 'HTTP_X_FORWARDED_PORT' => '80']],
		];
	}

	/**
	 * @covers RequestFactory::getScheme
	 * @dataProvider providerCreateHttpRequestWithTrustedProxy
	 */
	public function testCreateHttpRequestWithTrustedProxy($expectedScheme, array $server)
	{
		$_SERVER = array_merge(['REMOTE_ADDR' => '10.0.0.1'], $server);

		$factory = new Nette\Http\RequestFactory;
		$factory->setProxy(['10.0.0.1']);
		Assert::same($expectedScheme, $factory->createHttpRequest()->getUrl()->getScheme());
	}

	/**
	 * @return array
	 */
	public function providerCreateHttpRequestWithTrustedProxy()
	{
		return [
			['http', ['HTTP_X_FORWARDED_PROTO' => 'http']],
			['http', ['HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http']],
			['http', ['HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'something-unexpected']],
			['http', ['HTTPS' => 'on', 'HTTP_X_FORWARDED_PROTO' => 'http', 'HTTP_X_FORWARDED_PORT' => '443']],

			['https', ['HTTP_X_FORWARDED_PROTO' => 'https']],
			['https', ['HTTPS' => 'off', 'HTTP_X_FORWARDED_PROTO' => 'https']],
			['https', ['HTTPS' => 'off', 'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '80']],
		];
	}

}

$test = new RequestFactorySchemeTest();
$test->run();
