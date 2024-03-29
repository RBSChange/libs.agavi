<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2005  Agavi Foundation.                                |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

/**
 * ConsoleRequest provides support for console-only request information such as
 * command-line parameters.
 * 
 * @package    agavi
 * @subpackage request
 *
 * @author    Agavi Foundation (info@agavi.org)
 * @copyright (c) Agavi Foundation
 * @since     0.9.0
 * @version   $Id: ConsoleRequest.class.php 87 2005-06-03 21:19:23Z bob $
 */
class ConsoleRequest extends Request
{

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	// -------------------------------------------------------------------------

	/**
	 * Initialize this Request.
	 *
	 * @param Context A Context instance.
	 * @param array   An associative array of initialization parameters.
	 *
	 * @return bool true, if initialization completes successfully, otherwise
	 *              false.
	 *
	 * @throws <b>InitializationException</b> If an error occurs while
	 *                                        initializing this Request.
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  0.9.0
	 */
	public function initialize ($context, $parameters = null)
	{

		// load parameters
		$this->loadParameters($context);

		// set the default method
		$this->setMethod(self::CONSOLE);

	}

	// -------------------------------------------------------------------------

	/**
	 * Loads command line parameters into the parameter list.
	 *
	 * @param Context $context
	 * @return void
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  0.9.0
	 */
	private function loadParameters ($context)
	{
		$shortopts = $context->getController()->getParameter('shortopts');
		if (!is_array($longopts = $context->getController()->getParameter('longopts'))) {
			$longopts = array();
		}

		if (($params = @getopt($shortopts, $longopts)) === false) {
			throw new AgaviException('Invalid getopt options');
		}

		$this->setParameters($params);

	}

	// -------------------------------------------------------------------------

	/**
	 * Execute the shutdown procedure.
	 *
	 * @return void
	 *
	 * @author Agavi Foundation (info@agavi.org)
	 * @since  0.9.0
	 */
	public function shutdown ()
	{

		// nothing to do here

	}

}

?>
