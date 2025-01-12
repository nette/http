<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\HttpTracy;

use Nette;
use Tracy;


/**
 * Session panel for Debugger Bar.
 */
class SessionPanel implements Tracy\IBarPanel
{
	/**
	 * Renders tab.
	 */
	public function getTab(): string
	{
		return Nette\Utils\Helpers::capture(function () {
			require __DIR__ . '/dist/tab.phtml';
		});
	}


	/**
	 * Renders panel.
	 */
	public function getPanel(): string
	{
		return Nette\Utils\Helpers::capture(function () {
			require __DIR__ . '/dist/panel.phtml';
		});
	}
}
