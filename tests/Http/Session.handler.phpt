<?php

/**
 * Test: Nette\Http\Session storage.
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class MySessionStorage implements SessionHandlerInterface
{
	private $path;

	function open($savePath, $sessionName)
	{
		$this->path = $savePath;
		return TRUE;
	}

	function close()
	{
		return TRUE;
	}

	function read($id)
	{
		return (string) @file_get_contents("$this->path/sess_$id");
	}

	function write($id, $data)
	{
		return (bool) file_put_contents("$this->path/sess_$id", $data);
	}

	function destroy($id)
	{
		return !is_file("$this->path/sess_$id") || @unlink("$this->path/sess_$id");
	}

	function gc($maxlifetime)
	{
		foreach (glob("$this->path/sess_*") as $filename) {
			if (filemtime($filename) + $maxlifetime < time()) {
				unlink($filename);
			}
		}
		return TRUE;
	}
}


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->createHttpRequest(), new Nette\Http\Response);

$session->setHandler(new MySessionStorage);
$session->start();
$_COOKIE['PHPSESSID'] = $session->getId();

$namespace = $session->getSection('one');
$namespace->a = 'apple';
$session->close();
unset($_SESSION);

$session->start();
$namespace = $session->getSection('one');
Assert::same('apple', $namespace->a);
