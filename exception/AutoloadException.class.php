<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2005  Sean Kerr.                                       |
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
 * AutoloadException is thrown when a class that has been required cannot be
 * loaded.
 *
 * @package    agavi
 * @subpackage exception
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @copyright (c) Sean Kerr, {@link http://www.mojavi.org}
 * @since     0.9.0
 * @version   $Id: AutoloadException.class.php 87 2005-06-03 21:19:23Z bob $
 */
class AutoloadException extends AgaviException
{

	// +-----------------------------------------------------------------------+
	// | CONSTRUCTOR                                                           |
	// +-----------------------------------------------------------------------+

	/**
	 * Class constructor.
	 *
	 * @param string The error message.
	 * @param int    The error code.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function __construct ($message = null, $code = 0)
	{

		parent::__construct($message, $code);

		$this->setName('AutoloadException');

	}

}

?>
