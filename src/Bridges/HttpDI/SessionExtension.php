<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\HttpDI;

use Nette;
use Nette\Http\IResponse;
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
			'autoStart' => Expect::anyOf('smart', 'always', 'never', true, false)->firstIsDefault(),
			'expiration' => Expect::string()->dynamic(),
			'handler' => Expect::string()->dynamic(),
			'readAndClose' => Expect::bool(),
			'cookieSamesite' => Expect::anyOf(IResponse::SameSiteLax, IResponse::SameSiteStrict, IResponse::SameSiteNone, true)
				->firstIsDefault(),
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

		if (isset($config->cookieSecure)) {
			trigger_error("The item 'session\u{a0}›\u{a0}cookieSecure' is deprecated, use 'http\u{a0}›\u{a0}cookieSecure' (it has default value 'auto').", E_USER_DEPRECATED);
			unset($config->cookieSecure);
		}

		if ($config->cookieSamesite === true) {
			trigger_error("In 'session\u{a0}›\u{a0}cookieSamesite' replace true with 'Lax'.", E_USER_DEPRECATED);
			$config->cookieSamesite = IResponse::SameSiteLax;
		}

		$this->compiler->addExportedType(Nette\Http\IRequest::class);

		if ($this->debugMode && $config->debugger) {
			$session->addSetup('@Tracy\Bar::addPanel', [
				new Nette\DI\Definitions\Statement(Nette\Bridges\HttpTracy\SessionPanel::class),
			]);
		}

		$options = (array) $config;
		unset($options['expiration'], $options['handler'], $options['autoStart'], $options['debugger']);
		if ($config->autoStart === 'never') {
			$options['autoStart'] = false;
		}

		if (!empty($options)) {
			$session->addSetup('setOptions', [$options]);
		}

		if ($this->name === 'session') {
			$builder->addAlias('session', $this->prefix('session'));
		}

		if (!$this->cliMode) {
			$name = $this->prefix('session');

			if ($config->autoStart === 'smart') {
				$this->initialization->addBody('$this->getService(?)->autoStart(false);', [$name]);

			} elseif ($config->autoStart === 'always' || $config->autoStart === true) {
				$this->initialization->addBody('$this->getService(?)->start();', [$name]);
			}
		}
	}
}
