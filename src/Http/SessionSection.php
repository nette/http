<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * Session section.
 */
class SessionSection implements \IteratorAggregate, \ArrayAccess
{
	use Nette\SmartObject;

	/** @var bool */
	public $warnOnUndefined = false;

	/** @var Session */
	private $session;

	/** @var string */
	private $name;


	/**
	 * Do not call directly. Use Session::getSection().
	 */
	public function __construct(Session $session, string $name)
	{
		$this->session = $session;
		$this->name = $name;
	}


	/**
	 * Returns an iterator over all section variables.
	 */
	public function getIterator(): \Iterator
	{
		$data = $this->getData(false);
		return new \ArrayIterator($data ?? []);
	}


	/**
	 * Sets a variable in this session section.
	 */
	public function __set(string $name, $value): void
	{
		$this->getData(true)[$name] = $value;
	}


	/**
	 * Gets a variable from this session section.
	 * @return mixed
	 */
	public function &__get(string $name)
	{
		$data = $this->getData(false);
		if ($this->warnOnUndefined && !array_key_exists($name, $data ?? [])) {
			trigger_error("The variable '$name' does not exist in session section");
		}

		return $data[$name];
	}


	/**
	 * Determines whether a variable in this session section is set.
	 */
	public function __isset(string $name): bool
	{
		if (!$this->session->exists()) {
			return false;
		}
		$data = $this->getData(false);
		return isset($data[$name]);
	}


	/**
	 * Unsets a variable in this session section.
	 */
	public function __unset(string $name): void
	{
		$data = &$this->getData(true);
		$meta = &$this->getMeta();
		unset($data[$name], $meta[$name]);
	}


	/**
	 * Sets a variable in this session section.
	 */
	public function offsetSet($name, $value): void
	{
		$this->__set($name, $value);
	}


	/**
	 * Gets a variable from this session section.
	 * @return mixed
	 */
	public function offsetGet($name)
	{
		return $this->__get($name);
	}


	/**
	 * Determines whether a variable in this session section is set.
	 */
	public function offsetExists($name): bool
	{
		return $this->__isset($name);
	}


	/**
	 * Unsets a variable in this session section.
	 */
	public function offsetUnset($name): void
	{
		$this->__unset($name);
	}


	/**
	 * Sets the expiration of the section or specific variables.
	 * @param  ?string  $time
	 * @param  string|string[]  $variables  list of variables / single variable to expire
	 * @return static
	 */
	public function setExpiration($time, $variables = null)
	{
		$meta = &$this->getMeta();
		if ($time) {
			$time = Nette\Utils\DateTime::from($time)->format('U');
			$max = (int) ini_get('session.gc_maxlifetime');
			if (
				$max !== 0 // 0 - unlimited in memcache handler
				&& ($time - time() > $max + 3) // 3 - bulgarian constant
			) {
				trigger_error("The expiration time is greater than the session expiration $max seconds");
			}
		}

		foreach (is_array($variables) ? $variables : [$variables] as $variable) {
			$meta[$variable]['T'] = $time ?: null;
		}
		return $this;
	}


	/**
	 * Removes the expiration from the section or specific variables.
	 * @param  string|string[]  $variables  list of variables / single variable to expire
	 */
	public function removeExpiration($variables = null): void
	{
		$meta = &$this->getMeta();
		foreach (is_array($variables) ? $variables : [$variables] as $variable) {
			unset($meta[$variable]['T']);
		}
	}


	/**
	 * Cancels the current session section.
	 */
	public function remove(): void
	{
		$this->session->start();
		unset($_SESSION['__NF']['DATA'][$this->name], $_SESSION['__NF']['META'][$this->name]);
	}


	private function &getData(bool $write)
	{
		if ($write || !session_id()) {
			$this->session->start();
		}
		return $_SESSION['__NF']['DATA'][$this->name];
	}


	private function &getMeta()
	{
		$this->session->start();
		return $_SESSION['__NF']['META'][$this->name];
	}
}
