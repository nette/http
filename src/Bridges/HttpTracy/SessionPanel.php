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
	use Nette\SmartObject;

	/**
	 * Renders tab.
	 */
	public function getTab(): string
	{
		ob_start(function () {});
		require __DIR__ . '/templates/SessionPanel.tab.phtml';
		return ob_get_clean();
	}


	/**
	 * Renders panel.
	 */
	public function getPanel(): string
	{
		ob_start(function () {});
		require __DIR__ . '/templates/SessionPanel.panel.phtml';
		return ob_get_clean();
	}
}
