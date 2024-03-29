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
 * Controller directs application flow.
 *
 * @package    agavi
 * @subpackage controller
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @copyright (c) Sean Kerr, {@link http://www.mojavi.org}
 * @since     0.9.0
 * @version   $Id: Controller.class.php 87 2005-06-03 21:19:23Z bob $
 */
abstract class Controller extends ParameterHolder
{

	// +-----------------------------------------------------------------------+
	// | PRIVATE VARIABLES                                                     |
	// +-----------------------------------------------------------------------+

	private
		$actionStack     = null,
		$databaseManager = null,
		$maxForwards     = 20,
		$renderMode      = View::RENDER_CLIENT,
		$request         = null,
		$securityFilter  = null,
		$storage         = null,
		$user            = null;

	protected
		$context         = null;

	private static
		$instance = null;

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+
	
	/**
	 *
	 * The dispatch method must be implemented
	 * it's expected to:
	 *		put and parameters into the request object
	 *		call the controller's initialize method
	 *		forward to the requested module/action
	 */
	abstract function dispatch();
	
	/**
	 * Indicates whether or not a module has a specific action.
	 *
	 * @param string A module name.
	 * @param string An action name.
	 *
	 * @return bool true, if the action exists, otherwise false.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function actionExists ($moduleName, $actionName)
	{
		$file = AG_MODULE_DIR . '/' . $moduleName . '/actions/' . $actionName . 'Action.class.php';
		return is_readable($file);
	}

	// -------------------------------------------------------------------------

	/**
	 * Forward the request to another action.
	 *
	 * @param string A module name.
	 * @param string An action name.
	 *
	 * @return void
	 *
	 * @throws <b>ConfigurationException</b> If an invalid configuration setting
	 *                                       has been found.
	 * @throws <b>ForwardException</b> If an error occurs while forwarding the
	 *                                 request.
	 * @throws <b>InitializationException</b> If the action could not be
	 *                                        initialized.
	 * @throws <b>SecurityException</b> If the action requires security but
	 *                                  the user implementation is not of type
	 *                                  SecurityUser.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function forward ($moduleName, $actionName)
	{

		$actionName = str_replace('.', '/', $actionName);
		$actionName = preg_replace('/[^a-z0-9\-_\/]+/i', '', $actionName);
		$moduleName = preg_replace('/[^a-z0-9\-_]+/i', '', $moduleName);

		if ($this->actionStack->getSize() >= $this->maxForwards) {
			throw new ForwardException('Too many forwards have been detected for this request');
		}

		if (!AG_AVAILABLE) {

			// application is unavailable
			$moduleName = AG_UNAVAILABLE_MODULE;
			$actionName = AG_UNAVAILABLE_ACTION;

			if (!$this->actionExists($moduleName, $actionName))
			{

				// cannot find unavailable module/action
				$error = 'Invalid configuration settings: ' .
						 'AG_UNAVAILABLE_MODULE "%s", ' .
						 'AG_UNAVAILABLE_ACTION "%s"';

				$error = sprintf($error, $moduleName, $actionName);

				throw new ConfigurationException($error);

			}

		} else if (!$this->actionExists($moduleName, $actionName))
		{

			// the requested action doesn't exist

			// track the requested module so we have access to the data
			// in the error 404 page
			$this->request->setAttribute('requested_action', $actionName);
			$this->request->setAttribute('requested_module', $moduleName);

			// switch to error 404 action
			$moduleName = AG_ERROR_404_MODULE;
			$actionName = AG_ERROR_404_ACTION;

			if (!$this->actionExists($moduleName, $actionName))
			{

				// cannot find unavailable module/action
				$error = 'Invalid configuration settings: ' .
						 'AG_ERROR_404_MODULE "%s", ' .
						 'AG_ERROR_404_ACTION "%s"';

				$error = sprintf($error, $moduleName, $actionName);

				throw new ConfigurationException($error);

			}

		}

		// create an instance of the action
		$actionInstance = $this->getAction($moduleName, $actionName);

		// add a new action stack entry
		$this->actionStack->addEntry($moduleName, $actionName, $actionInstance);

		// include the module configuration
		ConfigCache::import(AG_MODULE_DIR . '/' . $moduleName . '/config/module.ini');

		if (constant('MOD_' . strtoupper($moduleName) . '_ENABLED')) {
			
			// check for a module config.php
			$moduleConfig = AG_MODULE_DIR . '/' . $moduleName . '/config.php';
			if (is_readable($moduleConfig)) {
				require_once($moduleConfig);
			}

			// initialize the action
			if ($actionInstance->initialize($this->context)) {

				// create a new filter chain
				$filterChain = new FilterChain();

				if (AG_AVAILABLE) {
					// the application is available so we'll register
					// global and module filters, otherwise skip them

					// does this action require security?
					if (AG_USE_SECURITY && $actionInstance->isSecure()) {

						if (!($this->user instanceof SecurityUser)) {
							$error = 'Security is enabled, but your User ' .
							         'implementation isn\'t a sub-class of ' .
							         'SecurityUser';
							
							throw new SecurityException($error);

						}

						// register security filter
						$filterChain->register($this->securityFilter);

					}
					
					// load filters
					$this->loadGlobalFilters($filterChain);
					$this->loadModuleFilters($filterChain);

				}

				// register the execution filter
				$execFilter = new ExecutionFilter();

				$execFilter->initialize($this->context);
				$filterChain->register($execFilter);

				// process the filter chain
				$filterChain->execute();

			} else
			{

				// action failed to initialize
				$error = 'Action initialization failed for module "%s", ' .
						 'action "%s"';

				$error = sprintf($error, $moduleName, $actionName);

				throw new InitializationException($error);

			}

		} else
		{

			// module is disabled
			$moduleName = AG_MODULE_DISABLED_MODULE;
			$actionName = AG_MODULE_DISABLED_ACTION;

			if (!$this->actionExists($moduleName, $actionName))
			{

				// cannot find mod disabled module/action
				$error = 'Invalid configuration settings: ' .
						 'AG_MODULE_DISABLED_MODULE "%s", ' .
						 'AG_MODULE_DISABLED_ACTION "%s"';

				$error = sprintf($error, $moduleName, $actionName);

				throw new ConfigurationException($error);

			}

			$this->forward($moduleName, $actionName);

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve an Action implementation instance.
	 *
	 * @param string A module name.
	 * @param string An action name.
	 *
	 * @return Action An Action implementation instance, if the action exists,
	 *                otherwise null.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @author Mike Vincent (mike@agavi.org)
	 * @since  0.9.0
	 */
	public function getAction ($moduleName, $actionName)
	{
		$file = AG_MODULE_DIR . '/' . $moduleName . '/actions/' . $actionName . 'Action.class.php';
		
		if (file_exists($file)) {
			require_once($file);
		}
		
		// Nested action check?
		$position = strrpos($actionName, '/');
		if ($position > -1) {
			$actionName = substr($actionName, $position + 1);
		}

		if (class_exists($moduleName . '_' . $actionName . 'Action', false)) {
			$class = $moduleName . '_' . $actionName . 'Action';
		} else {
			$class = $actionName . 'Action';
		}

		return new $class();

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the action stack.
	 *
	 * @return ActionStack An ActionStack instance, if the action stack is
	 *                     enabled, otherwise null.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function getActionStack ()
	{

		return $this->actionStack;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the current application context.
	 *
	 * @return Context A Context instance.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function getContext ()
	{

		return $this->context;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve a global Model implementation instance.
	 *
	 * @param string A model name.
	 *
	 * @return Model A Model implementation instance, if the model exists,
	 *               otherwise null.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function getGlobalModel ($modelName)
	{

		$file = AG_LIB_DIR . '/models/' . $modelName . 'Model.class.php';

			if(file_exists($file)) {
				require_once($file);
			} else {
				$pattern = AG_LIB_DIR . '/' . '*' . '/models/' . $modelName . 'Model.class.php';
				$files = glob($pattern);

				// only include the first file found
				require_once($files[0]);
			}

		$class = $modelName . 'Model';

		// create model instance and initialize it
		$model = new $class();
		$model->initialize($this->context);

		return $model;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the singleton instance of this class.
	 *
	 * @return Controller A Controller implementation instance.
	 *
	 * @throws <b>ControllerException</b> If a controller implementation
	 *                                    instance has not been created.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function getInstance ()
	{

		if (isset(self::$instance)) {
			return self::$instance;
		}

		// an instance of the controller has not been created
		$error = 'A Controller implementation instance has not been created';
		throw new ControllerException($error);

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve a Model implementation instance.
	 *
	 * @param string A module name.
	 * @param string A model name.
	 *
	 * @return Model A Model implementation instance, if the model exists,
	 *               otherwise null.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function getModel ($moduleName, $modelName)
	{

		$file = AG_MODULE_DIR . '/' . $moduleName . '/models/' . $modelName .
				'Model.class.php';

		require_once($file);

		$class = $modelName . 'Model';

		// fix for same name classes
		$moduleClass = $moduleName . '_' . $class;

		if (class_exists($moduleClass, false))
		{

			$class = $moduleClass;

		}

		// create model instance and initialize it
		$model = new $class();
		$model->initialize($this->context);

		return $model;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve the presentation rendering mode.
	 *
	 * @return int One of the following:
	 *             - View::RENDER_CLIENT
	 *             - View::RENDER_VAR
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function getRenderMode ()
	{

		return $this->renderMode;

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve a View implementation instance.
	 *
	 * @param string A module name.
	 * @param string A view name.
	 *
	 * @return View A View implementation instance, if the model exists,
	 *              otherwise null.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function getView ($moduleName, $viewName)
	{

		$file = AG_MODULE_DIR . '/' . $moduleName . '/views/' . $viewName .
				'View.class.php';

		require_once($file);

		$position = strrpos($viewName, '/');

		if ($position > -1)
		{

			$viewName = substr($viewName, $position + 1);

		}

		$class = $viewName . 'View';

		// fix for same name classes
		$moduleClass = $moduleName . '_' . $class;

		if (class_exists($moduleClass, false))
		{

			$class = $moduleClass;

		}

		return new $class();

	}

	// -------------------------------------------------------------------------

	/**
	 * Initialize this controller.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @author Mike Vincent (mike@agavi.org)
	 * @since  0.9.0
	 */
	protected function initialize ()
	{
		$this->maxForwards = defined('AG_MAX_FORWARDS') ? AG_MAX_FORWARDS : 20;
	
		$this->loadContext();
		$this->actionStack 			=& $this->context->getActionStack();
		$this->request 					=& $this->context->getRequest();
		$this->user 						=& $this->context->getUser();
		$this->databaseManager 	=& $this->context->getDatabaseManager();
		$this->securityFilter 	=& $this->context->getSecurityFilter();
		$this->storage 					=& $this->context->getStorage();
		
		register_shutdown_function(array($this, 'shutdown'));
	}

	protected function loadContext()
	{
		$this->context = Context::getInstance($this);
	}
	// -------------------------------------------------------------------------

	/**
	 * Load global filters.
	 *
	 * @param FilterChain A FilterChain instance.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function loadGlobalFilters ($filterChain)
	{

		static $list = array();

		// grab our global filter ini and preset the module name
		$config     = AG_CONFIG_DIR . '/filters.ini';
		$moduleName = 'global';

		if (!isset($list[$moduleName]) && is_readable($config))	{
			// load global filters
			require_once(ConfigCache::checkConfig('config/filters.ini'));
		}

		// register filters
		foreach ($list[$moduleName] as $filter)	{
			$filterChain->register($filter);
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Load module filters.
	 *
	 * @param FilterChain A FilterChain instance.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function loadModuleFilters ($filterChain)
	{

		// filter list cache file
		static $list = array();

		// get the module name
		$moduleName = $this->context->getModuleName();

		if (!isset($list[$moduleName]))	{
			// we haven't loaded a filter list for this module yet
			$config = AG_MODULE_DIR . '/' . $moduleName . '/config/filters.ini';
			if (is_readable($config)) {
				require_once(ConfigCache::checkConfig($config));
			} else {
				// add an emptry array for this module since no filters
				// exist
				$list[$moduleName] = array();
			}
		}

		// register filters
		foreach ($list[$moduleName] as $filter)	{
			$filterChain->register($filter);
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Indicates whether or not a module has a specific model.
	 *
	 * @param string A module name.
	 * @param string A model name.
	 *
	 * @return bool true, if the model exists, otherwise false.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function modelExists ($moduleName, $modelName)
	{

		$file = AG_MODULE_DIR . '/' . $moduleName . '/models/' . $modelName .	'Model.class.php';

		return is_readable($file);

	}

	// -------------------------------------------------------------------------

	/**
	 * Indicates whether or not a module exists.
	 *
	 * @param string A module name.
	 *
	 * @return bool true, if the module exists, otherwise false.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function moduleExists ($moduleName)
	{

		$file = AG_MODULE_DIR . '/' . $moduleName . '/config/module.ini';

		return is_readable($file);

	}

	// -------------------------------------------------------------------------

	/**
	 * Retrieve a new Controller implementation instance.
	 *
	 * @param string A Controller implementation name.
	 *
	 * @return Controller A Controller implementation instance.
	 *
	 * @throws <b>FactoryException</b> If a new controller implementation
	 *                                 instance cannot be created.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public static function newInstance ($class)
	{

		try
		{

			if (!isset(self::$instance))
			{

				// the class exists
				$object = new $class();

				if (!($object instanceof Controller))
				{

				    // the class name is of the wrong type
				    $error = 'Class "%s" is not of the type Controller';
				    $error = sprintf($error, $class);

				    throw new FactoryException($error);

				}

				// set our singleton instance
				self::$instance = $object;

				return $object;

			} else {

				$type = get_class(self::$instance);
				// an instance has already been created
				$error = 'A Controller implementation instance has already been created';
				throw new FactoryException($error);

			}

		} catch (AgaviException $e) {

			$e->printStackTrace();

		} catch (Exception $e)
		{

			// most likely an exception from a third-party library
			$e = new AgaviException($e->getMessage());

			$e->printStackTrace();

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Set the presentation rendering mode.
	 *
	 * @param int A rendering mode.
	 *
	 * @return void
	 *
	 * @throws <b>RenderException</b> - If an invalid render mode has been set.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  2.0.0
	 */
	public function setRenderMode ($mode)
	{

		if ($mode == View::RENDER_CLIENT || $mode == View::RENDER_VAR ||
			$mode == View::RENDER_NONE)
		{

			$this->renderMode = $mode;

			return;

		}

		// invalid rendering mode type
		$error = 'Invalid rendering mode: %s';
		$error = sprintf($error, $mode);

		throw new RenderException($error);

	}

	// -------------------------------------------------------------------------

	/**
	 * Execute the shutdown procedure.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function shutdown ()
	{

		$this->user->shutdown();

		session_write_close();

		$this->storage->shutdown();
		$this->request->shutdown();

		if (AG_USE_DATABASE) {
			$this->databaseManager->shutdown();
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Indicates whether or not a module has a specific view.
	 *
	 * @param string A module name.
	 * @param string A view name.
	 *
	 * @return bool true, if the view exists, otherwise false.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function viewExists ($moduleName, $viewName)
	{

		$file = AG_MODULE_DIR . '/' . $moduleName . '/views/' . $viewName .
				'View.class.php';

		return is_readable($file);

	}

	/**
	 * Indicates whether or not we were called using the CLI version of PHP.
	 *
	 * @return bool true, if we're using cli, otherwise false.
	 *
	 * @author Bob Zoller (bob@agavi.org)
	 * @since  1.0
	 */
	public function inCLI()
	{
		return php_sapi_name() == 'cli';
	}

}

?>
