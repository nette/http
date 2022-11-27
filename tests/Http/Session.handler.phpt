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


	#[ReturnTypeWillChange]
	public function open($savePath, $sessionName)
	{
		$this->path = $savePath;
		return true;
	}


	#[ReturnTypeWillChange]
	public function close()
	{
		return true;
	}


	#[ReturnTypeWillChange]
	public function read($id)
	{
		return (string) @file_get_contents("$this->path/sess_$id");
	}


	#[ReturnTypeWillChange]
	public function write($id, $data)
	{
		return (bool) file_put_contents("$this->path/sess_$id", $data);
	}


	#[ReturnTypeWillChange]
	public function destroy($id)
	{
		return !is_file("$this->path/sess_$id") || @unlink("$this->path/sess_$id");
	}


	#[ReturnTypeWillChange]
	public function gc($maxlifetime)
	{
		foreach (glob("$this->path/sess_*") as $filename) {
			if (filemtime($filename) + $maxlifetime < time()) {
				unlink($filename);
			}
		}

		return 0;
	}


	#[ReturnTypeWillChange]
	public function validateId($key)
	{
		return true;
	}
}


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->fromGlobals(), new Nette\Http\Response);

$session->setHandler(new MySessionStorage);
$session->start();

$namespace = $session->getSection('one');
$namespace->a = 'apple';
$session->close();
unset($_SESSION);

$session->start();
$namespace = $session->getSection('one');
Assert::same('apple', $namespace->a);
