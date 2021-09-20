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
		$this->session->autoStart(false);
		return new \ArrayIterator($this->getData() ?? []);
	}


	/**
	 * Sets a variable in this session section.
	 * @param  mixed  $value
	 */
	public function set(string $name, $value, string $expiration = null): void
	{
		if ($value === null) {
			$this->remove($name);
		} else {
			$this->session->autoStart(true);
			$this->getData()[$name] = $value;
			$this->setExpiration($expiration, $name);
		}
	}


	/**
	 * Gets a variable from this session section.
	 * @return mixed
	 */
	public function get(string $name)
	{
		if (func_num_args() > 1) {
			throw new \ArgumentCountError(__METHOD__ . '() expects 1 arguments, given more.');
		}
		$this->session->autoStart(false);
		return $this->getData()[$name] ?? null;
	}


	/**
	 * Removes a variable or whole section.
	 * @param  string|string[]|null  $name
	 */
	public function remove($name = null): void
	{
		$this->session->autoStart(false);
		if (func_num_args() > 1) {
			throw new \ArgumentCountError(__METHOD__ . '() expects at most 1 arguments, given more.');

		} elseif (func_num_args()) {
			$data = &$this->getData();
			$meta = &$this->getMeta();
			foreach ((array) $name as $name) {
				unset($data[$name], $meta[$name]);
			}
		} else {
			unset($_SESSION['__NF']['DATA'][$this->name], $_SESSION['__NF']['META'][$this->name]);
		}
	}


	/**
	 * Sets a variable in this session section.
	 */
	public function __set(string $name, $value): void
	{
		$this->session->autoStart(true);
		$this->getData()[$name] = $value;
	}


	/**
	 * Gets a variable from this session section.
	 * @return mixed
	 */
	public function &__get(string $name)
	{
		$this->session->autoStart(true);
		$data = &$this->getData();
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
		$this->session->autoStart(false);
		return isset($this->getData()[$name]);
	}


	/**
	 * Unsets a variable in this session section.
	 */
	public function __unset(string $name): void
	{
		$this->remove($name);
	}


	/**
	 * Sets a variable in this session section.
	 */
	public function offsetSet($name, $value): void
	{
		$this->__set($name, $value);
	}


	#[\ReturnTypeWillChange]
	/**
	 * Gets a variable from this session section.
	 * @return mixed
	 */
	public function offsetGet($name)
	{
		return $this->get($name);
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
		$this->remove($name);
	}


	/**
	 * Sets the expiration of the section or specific variables.
	 * @param  ?string  $time
	 * @param  string|string[]|null  $variables  list of variables / single variable to expire
	 * @return static
	 */
	public function setExpiration($time, $variables = null)
	{
		$this->session->autoStart((bool) $time);
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
	 * @param  string|string[]|null  $variables  list of variables / single variable to expire
	 */
	public function removeExpiration($variables = null): void
	{
		$this->setExpiration(null, $variables);
	}


	private function &getData()
	{
		return $_SESSION['__NF']['DATA'][$this->name];
	}


	private function &getMeta()
	{
		return $_SESSION['__NF']['META'][$this->name];
	}
}
