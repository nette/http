<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * Immutable representation of a URL with application base-path.
 *
 * <pre>
 *      baseUrl    basePath  relativePath  relativeUrl
 *         |          |        |               |
 * /---------------/-----\/--------\-----------------------------\
 * http://nette.org/admin/script.php/pathinfo/?name=param#fragment
 *                 \_______________/\________/
 *                        |              |
 *                   scriptPath       pathInfo
 * </pre>
 *
 * @property-read string $scriptPath
 * @property-read string $basePath
 * @property-read string $relativePath
 * @property-read string $baseUrl
 * @property-read string $relativeUrl
 * @property-read string $pathInfo
 */
class UrlScript extends UrlImmutable
{
	/** @var string */
	private $scriptPath;

	/** @var string */
	private $basePath;


	public function __construct($url = '/', string $scriptPath = '')
	{
		parent::__construct($url);
		$this->scriptPath = $scriptPath;
		$this->build();
	}


	/** @return static */
	public function withPath(string $path, string $scriptPath = '')
	{
		$dolly = clone $this;
		$dolly->scriptPath = $scriptPath;
		$parent = \Closure::fromCallable([UrlImmutable::class, 'withPath'])->bindTo($dolly);
		return $parent($path);
	}


	public function getScriptPath(): string
	{
		return $this->scriptPath;
	}


	public function getBasePath(): string
	{
		return $this->basePath;
	}


	public function getRelativePath(): string
	{
		return substr($this->getPath(), strlen($this->basePath));
	}


	public function getBaseUrl(): string
	{
		return $this->getHostUrl() . $this->basePath;
	}


	public function getRelativeUrl(): string
	{
		return substr($this->getAbsoluteUrl(), strlen($this->getBaseUrl()));
	}


	/**
	 * Returns the additional path information.
	 */
	public function getPathInfo(): string
	{
		return (string) substr($this->getPath(), strlen($this->scriptPath));
	}


	protected function build(): void
	{
		parent::build();
		$path = $this->getPath();
		$this->scriptPath = $this->scriptPath ?: $path;
		$pos = strrpos($this->scriptPath, '/');
		if ($pos === false || strncmp($this->scriptPath, $path, $pos + 1)) {
			throw new Nette\InvalidArgumentException("ScriptPath '$this->scriptPath' doesn't match path '$path'");
		}
		$this->basePath = substr($this->scriptPath, 0, $pos + 1);
	}
}
