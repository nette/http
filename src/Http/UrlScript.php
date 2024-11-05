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
	private string $scriptPath;
	private string $basePath;


	public function __construct(string|Url $url = '/', string $scriptPath = '')
	{
		parent::__construct($url);
		$this->setScriptPath($scriptPath);
	}


	public function withPath(string $path, string $scriptPath = ''): static
	{
		$dolly = parent::withPath($path);
		$dolly->setScriptPath($scriptPath);
		return $dolly;
	}


	private function setScriptPath(string $scriptPath): void
	{
		$path = $this->getPath();
		$scriptPath = $scriptPath ?: $path;
		$pos = strrpos($scriptPath, '/');
		if ($pos === false || strncmp($scriptPath, $path, $pos + 1)) {
			throw new Nette\InvalidArgumentException("ScriptPath '$scriptPath' doesn't match path '$path'");
		}

		$this->scriptPath = $scriptPath;
		$this->basePath = substr($scriptPath, 0, $pos + 1);
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
		return substr($this->getPath(), strlen($this->scriptPath));
	}


	/** @internal */
	protected function mergePath(string $path): string
	{
		return $this->basePath . $path;
	}
}
