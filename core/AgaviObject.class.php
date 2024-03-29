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
 * $Id: AgaviObject.class.php 87 2005-06-03 21:19:23Z bob $
 *
 * AgaviObject provides useful methods that all Agavi classes inherit.
 *
 * @package    agavi
 * @subpackage core
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @copyright (c) Sean Kerr, {@link http://www.mojavi.org}
 * @since     0.9.0
 * @version   $Rev$
 */
abstract class AgaviObject
{

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Retrieve a string representation of this object.
	 *
	 * @return string A string containing all public variables available in
	 *                this object.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function toString ()
	{

		$output = '';
		$vars   = get_object_vars($this);

		foreach ($vars as $key => &$value)
		{

			if (strlen($output) > 0)
			{

				$output .= ', ';

			}

			$output .= $key . ': ' . $value;

		}

		return $output;

	}

}

?>
