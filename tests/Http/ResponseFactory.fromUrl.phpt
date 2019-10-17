<?php declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// a local HTTP server, so the test does not depend on the network
$socket = stream_socket_server('tcp://127.0.0.1:0');
$address = stream_socket_get_name($socket, remote: false);
fclose($socket);

$server = proc_open(
	[PHP_BINARY, '-S', $address, __DIR__ . '/files/ResponseFactory.fromUrl.router.php'],
	[1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
	$pipes,
);
for ($i = 0; $i < 50 && !@fsockopen('tcp://' . $address); $i++) { // @ the server is not listening yet
	usleep(100_000);
}

try {
	$factory = new Http\ResponseFactory;
	$response = $factory->fromUrl('http://' . $address . '/');

	Assert::same(200, $response->getCode());
	Assert::same('1.1', $response->getProtocolVersion());
	Assert::same('OK', $response->getReasonPhrase());
	Assert::same(['SAMEORIGIN'], $response->getHeaders()['X-Frame-Options']);
	Assert::same('hello', $response->getBody());
} finally {
	proc_terminate($server);
}


// not an HTTP resource
Assert::exception(
	fn() => $factory->fromUrl(__FILE__),
	Nette\InvalidArgumentException::class,
	"'%a%' did not return an HTTP response.",
);
