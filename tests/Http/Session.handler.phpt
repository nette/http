<?php

/**
 * Test: Nette\Http\Session storage.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class MySessionStorage implements SessionHandlerInterface
{
	private $path;


	public function open(string $savePath, string $sessionName): bool
	{
		$this->path = $savePath;
		return true;
	}


	public function close(): bool
	{
		return true;
	}


	public function read(string $id): string|false
	{
		return (string) @file_get_contents("$this->path/sess_$id");
	}


	public function write(string $id, string $data): bool
	{
		return (bool) file_put_contents("$this->path/sess_$id", $data);
	}


	public function destroy(string $id): bool
	{
		return !is_file("$this->path/sess_$id") || @unlink("$this->path/sess_$id");
	}


	public function gc(int $maxlifetime): int|false
	{
		foreach (glob("$this->path/sess_*") as $filename) {
			if (filemtime($filename) + $maxlifetime < time()) {
				unlink($filename);
			}
		}

		return 0;
	}


	public function validateId(string $id): bool
	{
		return true;
	}
}


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->fromGlobals(), new Nette\Http\Response);

$session->setHandler(new MySessionStorage);
$session->start();

$namespace = $session->getSection('one');
$namespace->set('a', 'apple');
$session->close();
unset($_SESSION);

$session->start();
$namespace = $session->getSection('one');
Assert::same('apple', $namespace->get('a'));
