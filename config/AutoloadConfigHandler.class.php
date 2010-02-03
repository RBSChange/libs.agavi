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
 * AutoloadConfigHandler allows you to specify a list of classes that will
 * automatically be included for you upon first use.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @copyright (c) Sean Kerr, {@link http://www.mojavi.org}
 * @since     0.9.0
 * @version   $Id: AutoloadConfigHandler.class.php 87 2005-06-03 21:19:23Z bob $
 */
class AutoloadConfigHandler extends IniConfigHandler
{

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Execute this configuration handler.
	 *
	 * @param string An absolute filesystem path to a configuration file.
	 *
	 * @return string Data to be written to a cache file.
	 *
	 * @throws <b>ConfigurationException</b> If a requested configuration file
	 *                                       does not exist or is not readable.
	 * @throws <b>ParseException</b> If a requested configuration file is
	 *                               improperly formatted.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function & execute ($config)
	{

		// set our required categories list and initialize our handler
		$categories = array('required_categories' => array('autoload'));

		$this->initialize($categories);

		// parse the ini
		$ini = $this->parseIni($config);

		// init our data array
		$data = array();

		// let's do our fancy work
		foreach ($ini['autoload'] as $class => &$file)
		{

			$file = $this->replaceConstants($file);
			$file = $this->replacePath($file);

			if (!is_readable($file))
			{

				// the class path doesn't exist
				$error = 'Configuration file "%s" specifies class "%s" with ' .
						 'nonexistent or unreadable file "%s"';
				$error = sprintf($error, $config, $class, $file);

				throw new ParseException($error);

			}

			$tmp    = "\$classes['%s'] = '%s';";
			$data[] = sprintf($tmp, $class, $file);

		}

		// compile data
		$retval = "<?php\n" .
				  "// auth-generated by AutoloadConfigHandler\n" .
				  "// date: %s\n%s\n?>";
		$retval = sprintf($retval, date('m/d/Y H:i:s'), implode("\n", $data));

		return $retval;

	}

}

?>