<?php
/**
 * DronePHP (http://www.dronephp.com)
 *
 * @link      http://github.com/Pleets/DronePHP
 * @copyright Copyright (c) 2016 DronePHP. (http://www.dronephp.com)
 * @license   http://www.dronephp.com/license
 */

namespace Drone\Mvc;

use Drone\Mvc\AbstractionController;

abstract class AbstractionModule
{
	/**
	 * @var string
	 */
	protected $moduleName;

	/**
	 * Constructor
	 *
	 * @param string                $moduleName
	 * @param AbstractionController $controller
	 */
	public function __construct($moduleName, AbstractionController $controller)
	{
		$this->moduleName = $moduleName;
		$this->init($controller);
	}

	/**
	 * Absract method to be executed before each controller in each module
	 *
	 * @param AbstractionController
	 */
	public abstract function init(AbstractionController $controller);

	/**
	 * Returns the moduleName attribute
	 *
	 * @return string
	 */
	public function getModuleName()
	{
		return $this->moduleName;
	}

	/**
	 * Returns an array with application settings
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return include 'module/' . $this->getModuleName() . '/config/module.config.php';
	}
}