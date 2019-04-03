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

	/** @var array|null  session data storage */
	private $data;

	/** @var array|bool  session metadata storage */
	private $meta = false;


	/**
	 * Do not call directly. Use Session::getSection().
	 */
	public function __construct(Session $session, string $name)
	{
		$this->session = $session;
		$this->name = $name;
	}


	private function start(): void
	{
		if ($this->meta === false) {
			$this->session->start();
			$this->data = &$_SESSION['__NF']['DATA'][$this->name];
			$this->meta = &$_SESSION['__NF']['META'][$this->name];
		}
	}


	/**
	 * Returns an iterator over all section variables.
	 */
	public function getIterator(): \Iterator
	{
		$this->start();
		return new \ArrayIterator($this->data ?? []);
	}


	/**
	 * Sets a variable in this session section.
	 */
	public function __set(string $name, $value): void
	{
		$this->start();
		$this->data[$name] = $value;
	}


	/**
	 * Gets a variable from this session section.
	 * @return mixed
	 */
	public function &__get(string $name)
	{
		$this->start();
		if ($this->warnOnUndefined && !array_key_exists($name, $this->data)) {
			trigger_error("The variable '$name' does not exist in session section");
		}

		return $this->data[$name];
	}


	/**
	 * Determines whether a variable in this session section is set.
	 */
	public function __isset(string $name): bool
	{
		if ($this->session->exists()) {
			$this->start();
		}
		return isset($this->data[$name]);
	}


	/**
	 * Unsets a variable in this session section.
	 */
	public function __unset(string $name): void
	{
		$this->start();
		unset($this->data[$name], $this->meta[$name]);
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
		$this->start();
		if ($time) {
			$time = Nette\Utils\DateTime::from($time)->format('U');
			$max = (int) ini_get('session.gc_maxlifetime');
			if ($max !== 0 && ($time - time() > $max + 3)) { // 0 - unlimited in memcache handler, 3 - bulgarian constant
				trigger_error("The expiration time is greater than the session expiration $max seconds");
			}
		}

		foreach (is_array($variables) ? $variables : [$variables] as $variable) {
			$this->meta[$variable]['T'] = $time ?: null;
		}
		return $this;
	}


	/**
	 * Removes the expiration from the section or specific variables.
	 * @param  string|string[]  $variables  list of variables / single variable to expire
	 */
	public function removeExpiration($variables = null): void
	{
		$this->start();
		foreach (is_array($variables) ? $variables : [$variables] as $variable) {
			unset($this->meta[$variable]['T']);
		}
	}


	/**
	 * Cancels the current session section.
	 */
	public function remove(): void
	{
		$this->start();
		$this->data = null;
		$this->meta = null;
	}
}
