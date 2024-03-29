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
 * WebController provides web specific methods to Controller such as, url
 * redirection.
 *
 * @package    agavi
 * @subpackage controller
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @copyright (c) Sean Kerr, {@link http://www.mojavi.org}
 * @since     0.9.0
 * @version   $Id: WebController.class.php 87 2005-06-03 21:19:23Z bob $
 */
abstract class WebController extends Controller
{

	// +-----------------------------------------------------------------------+
	// | PRIVATE VARIABLES                                                     |
	// +-----------------------------------------------------------------------+

	private
		$contentType = null;

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Generate a formatted Agavi URL.
	 *
	 * @param string An existing URL for basing the parameters.
	 * @param array  An associative array of URL parameters.
	 *
	 * @return string A URL to a Agavi resource.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function genURL ($url = null, $parameters = array())
	{

		if ($url == null)
		{

			$url = $_SERVER['SCRIPT_NAME'];

		}

		if (AG_URL_FORMAT == 'PATH')
		{

			// use PATH format
			$divider  = '/';
			$equals   = '/';
			$url     .= '/';

		} else
		{

			// use GET format
			$divider  = '&';
			$equals   = '=';
			$url     .= '?';

		}

		// loop through the parameters
		foreach ($parameters as $key => &$value)
		{

			$url .= urlencode($key) . $equals . urlencode($value) . $divider;

		}

		// strip off last divider character
		$url = rtrim($url, $divider);

		// replace &'s with &amp;
		$url = str_replace('&', '&amp;', $url);

		return $url;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the requested content type.
	 *
	 * @return string A content type.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function getContentType ()
	{

		return $this->contentType;

	}

	// -------------------------------------------------------------------------

	/**
	 * Initialize this controller.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	protected function initialize ()
	{

		// initialize parent
		parent::initialize();

		// set our content type
		$this->contentType = $this->getContext()
						          ->getRequest()
						          ->getParameter('ctype', AG_CONTENT_TYPE);

	}

	// -------------------------------------------------------------------------

	/**
	 * Redirect the request to another URL.
	 *
	 * @param string An existing URL.
	 * @param int    A delay in seconds before redirecting. This only works on
	 *               browsers that do not support the PHP header.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function redirect ($url, $delay = 0)
	{

		// shutdown the controller
		$this->shutdown();

		// redirect
		header('Location: ' . $url);

		$echo = '<html>' .
				'<head>' .
				'<meta http-equiv="refresh" content="%d;url=%s"/>' .
				'</head>' .
				'</html>';

		$echo = sprintf($echo, $delay, $url);

		echo $echo;

		exit;

	}

	// -------------------------------------------------------------------------

	/**
	 * Set the content type for this request.
	 *
	 * @param string A content type.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function setContentType ($type)
	{

		$this->contentType = $type;

	}

}

?>
