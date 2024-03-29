<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Authors                                                                   |
// |  Sean Kerr (skerr@mojavi.org)                                             |
// |  Agavi Foundation (info@agavi.org)                                        |
// | Copyright (c) 2003-2005  Authors                                          |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

require_once(AG_SMARTY_DIR.'/libs/Smarty.class.php');

/**
 * $Id: SmartyView.class.php 87 2005-06-03 21:19:23Z bob $
 *
 *
 * @package    agavi
 * @subpackage view
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @author    zembla {@link http://forum.mojavi.org/index.php?showuser=329}
 * @author    Agavi Foundation (info@agavi.org)
 * @copyright (c) Authors, {@link http://www.agavi.org}
 * @since     0.9.0
 */
abstract class SmartyView extends View
{
	private static
		$smarty = null;

	public function initialize(Context $context)
	{
		$this->smarty = new Smarty();
		$this->smarty->clear_all_assign();
		$this->smarty->clear_config();
		$this->smarty->config_dir   = AG_CONFIG_DIR;
		$this->smarty->cache_dir    = AG_CACHE_DIR;
		$this->smarty->plugins_dir  = array("plugins","plugins_local");

		return(parent::initialize($context));
	}

	public function clearAttributes()
	{
		$this->smarty->clear_all_assign();
	}

	public function getAttributeNames()
	{
		return array_keys($this->smarty->get_template_vars());
	}

	public function & getAttribute($name)
	{
		return $this->smarty->get_template_vars($name);
	}

	public function & removeAttribute($name)
	{
		$retval = $this->smarty->get_template_vars($name);
		$this->smarty->clear_assign($name);
		return $retval;
	}

	public function setAttribute($name, $value)
	{
		$this->smarty->assign($name, $value);
	}

	public function setAttributeByRef($name, &$value)
	{
		$this->smarty->assign_by_ref($name, $value);
	}

	public function setAttributes($attributes)
	{
		$this->smarty->assign($attributes);
	}

	public function setAttributesByRef(&$attributes)
	{
		$this->smarty->assign_by_ref($attributes);
	}

	public function & getEngine()
	{
		return $this->smarty;
	}

	public function & render()
	{
		$retval = null;

		// execute pre-render check
		$this->preRenderCheck();

		// get the render mode
		$mode = $this->getContext()->getController()->getRenderMode();

		$this->getEngine()->template_dir = $this->getDirectory();
		$this->getEngine()->compile_dir  = AG_SMARTY_CACHE_DIR;

		if ($mode == View::RENDER_CLIENT && !$this->isDecorator()) {
			// render directly to the client
			$this->getEngine()->display($this->getTemplate());
		} else if ($mode != View::RENDER_NONE) {
			// render to variable
			$retval = $this->getEngine()->fetch($this->getTemplate());

			// now render our decorator template, if one exists
			if ($this->isDecorator()) {
				$retval =& $this->decorate($retval);
			}

			if ($mode == View::RENDER_CLIENT) {
				echo($retval);
				$retval = null;
			}
		}
		return $retval;
	}

	protected function & decorate(&$content)
	{
		// call our parent decorate() method
		parent::decorate($content);

		// render the decorator template and return the result
		$decoratorTemplate = $this->getDecoratorDirectory() . '/' . $this->getDecoratorTemplate();

		$retval = $this->getEngine()->fetch($decoratorTemplate);

		return $retval;
	}

}

?>
