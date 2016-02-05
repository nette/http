<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bridges\HttpTracy;

use Nette;
use Tracy;


/**
 * Session panel for Debugger Bar.
 */
class SessionPanel extends Nette\Object implements Tracy\IBarPanel
{

	/**
	 * Renders tab.
	 * @return string
	 */
	public function getTab()
	{
		ob_start(NULL, 0, PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_CLEANABLE);
		require __DIR__ . '/templates/SessionPanel.tab.phtml';
		return ob_get_clean();
	}


	/**
	 * Renders panel.
	 * @return string
	 */
	public function getPanel()
	{
		ob_start(NULL, 0, PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_CLEANABLE);
		require __DIR__ . '/templates/SessionPanel.panel.phtml';
		return ob_get_clean();
	}

}
