<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\HttpDI;

use Nette;
use Nette\Schema\Expect;


/**
 * Session extension for Nette DI.
 */
class SessionExtension extends Nette\DI\CompilerExtension
{
	/** @var bool */
	private $debugMode;

	/** @var bool */
	private $cliMode;


	public function __construct(bool $debugMode = false, bool $cliMode = false)
	{
		$this->debugMode = $debugMode;
		$this->cliMode = $cliMode;
	}


	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'debugger' => Expect::bool(false),
			'autoStart' => Expect::anyOf('smart', true, false)->default('smart'),
			'expiration' => Expect::string()->dynamic(),
			'handler' => Expect::string()->dynamic(),
			'readAndClose' => Expect::bool(),
		])->otherItems('mixed');
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$session = $builder->addDefinition($this->prefix('session'))
			->setFactory(Nette\Http\Session::class);

		if ($config->expiration) {
			$session->addSetup('setExpiration', [$config->expiration]);
		}
		if ($config->handler) {
			$session->addSetup('setHandler', [$config->handler]);
		}
		if (($config->cookieDomain ?? null) === 'domain') {
			$config->cookieDomain = $builder::literal('$this->getByType(Nette\Http\IRequest::class)->getUrl()->getDomain(2)');
		}
		if (($config->cookieSecure ?? null) === 'auto') {
			$config->cookieSecure = $builder::literal('$this->getByType(Nette\Http\IRequest::class)->isSecured()');
		}
		if (($config->cookieSamesite ?? null) === true) {
			$config->cookieSamesite = 'Lax';
		}
		$this->compiler->addExportedType(Nette\Http\IRequest::class);

		if ($this->debugMode && $config->debugger) {
			$session->addSetup('@Tracy\Bar::addPanel', [
				new Nette\DI\Definitions\Statement(Nette\Bridges\HttpTracy\SessionPanel::class),
			]);
		}

		$options = (array) $config;
		unset($options['expiration'], $options['handler'], $options['autoStart'], $options['debugger']);
		if ($options['readAndClose'] === null) {
			unset($options['readAndClose']);
		}
		if (!empty($options)) {
			$session->addSetup('setOptions', [$options]);
		}

		if ($this->name === 'session') {
			$builder->addAlias('session', $this->prefix('session'));
		}
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		if ($this->cliMode) {
			return;
		}

		$initialize = $class->getMethod('initialize');
		$name = $this->prefix('session');

		if ($this->config->autoStart === 'smart') {
			$initialize->addBody('$this->getService(?)->exists() && $this->getService(?)->start();', [$name, $name]);

		} elseif ($this->config->autoStart) {
			$initialize->addBody('$this->getService(?)->start();', [$name]);
		}
	}
}
