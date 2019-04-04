<?php
/**
 * DronePHP (http://www.dronephp.com)
 *
 * @link      http://github.com/Pleets/DronePHP
 * @copyright Copyright (c) 2016-2018 Pleets. (http://www.pleets.org)
 * @license   http://www.dronephp.com/license
 * @author    Darío Rivera <fermius.us@gmail.com>
 */

namespace Drone\Mvc;

use Drone\Mvc\Exception;

/**
 * Router class
 *
 * This class build the route and calls to specific application controller
 */
class Router
{
    /**
     * List of routes
     *
     * @var array
     */
    private $routes = [];

    /**
     * The Identifiers builds the route
     *
     * @var array
     */
    private $identifiers;

    /**
     * Controller instance
     *
     * @var AbstractController
     */
    private $controller;

    /**
     * Indicates how the class name could be matched
     *
     * @var callable
     */
    private $classNameBuilder;

    /**
     * Zend\Router implementation
     *
     * @var \Zend\Router\SimpleRouteStack
     */
    private $zendRouter;

    /**
     * Returns all routes built
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Returns all identifiers
     *
     * @return array
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * Returns the controller instance
     *
     * @throws \RuntimeException
     *
     * @return AbstractController
     */
    public function getController()
    {
        if (is_null($this->controller))
            throw new \RuntimeException("No controller matched, try to match first.");

        return $this->controller;
    }

    /**
     * Returns the class name builder function
     *
     * @return callable
     */
    public function getClassNameBuilder()
    {
        return $this->classNameBuilder;
    }

    /**
     * Returns the Zend\Router\SimpleRouteStack object
     *
     * @return \Zend\Router\SimpleRouteStack
     */
    public function getZendRouter()
    {
        return $this->zendRouter;
    }

    /**
     * Sets identifiers
     *
     * @param string $module
     * @param string $controller
     * @param string $view
     *
     * @return null
     */
    public function setIdentifiers($module, $controller, $view)
    {
        $this->identifiers = [
            "module"     => $module,
            "controller" => $controller,
            "view"       => $view
        ];
    }

    /**
     * Sets the class name builder function
     *
     * @param callable $builder
     *
     * @return null
     */
    public function setClassNameBuilder(callable $builder)
    {
        $this->classNameBuilder = $builder;
    }

    /**
     * Constructor
     *
     * @param array $routes
     */
    public function __construct(Array $routes = [])
    {
        if (count($routes))
        {
            foreach ($routes as $route)
            {
                $this->addRoute($route);
            }
        }

        # default class name builder
        $this->setClassNameBuilder(function($module, $class) {
            return "\\$module\\$class";
        });

        $this->zendRouter = new \Zend\Router\SimpleRouteStack();
    }

    /**
     * Builds the current route and calls the controller
     *
     * @throws Exception\PageNotFoundException
     * @throws \LogicException
     *
     * @return  null
     */
    public function match()
    {
        if (!is_callable($this->classNameBuilder))
            throw \LogicException("No class name builder found");

        /*
         *  Key value pairs builder:
         *  Searches for the pattern /var1/value1/var2/value2 and converts it to  var1 => value1, var2 => value2
         */
        if (array_key_exists('params', $_GET))
        {
            $keypairs = $this->parseRequestParameters($_GET["params"]);
            unset($_GET["params"]);
            $_GET = array_merge($_GET, $keypairs);
        }

        /*
         *  Route builder:
         *  The route is built by default from the URL as follow
         *  www.example.com/module/controller/view
         */

        $module = (is_null($this->identifiers["module"]) || empty($this->identifiers["module"]))
                    ? $this->routes["defaults"]["module"] : $this->identifiers["module"];

        if (!array_key_exists($module, $this->routes))
            throw new Exception\ModuleNotFoundException("The key '$module' does not exists in routes!");

        $controller = (is_null($this->identifiers["controller"]) || empty($this->identifiers["controller"]))
                    ? $this->routes[$module]["controller"] : $this->identifiers["controller"];

        $view = (is_null($this->identifiers["view"]) || empty($this->identifiers["view"]))
                    ? $this->routes[$module]["view"] : $this->identifiers["view"];

        $fqn_controller = call_user_func($this->classNameBuilder, $module, $controller);

        if (class_exists($fqn_controller))
        {
            try {
                $this->controller = new $fqn_controller;
            }
            # change context, in terms of Router MethodNotFoundException or
            # PrivateMethodExecutionException is a PageNotfoundException
            catch (Exception\MethodNotFoundException $e)
            {
                throw new Exception\PageNotFoundException($e->getMessage(), $e->getCode(), $e);
            }
            catch (Exception\PrivateMethodExecutionException $e)
            {
                throw new Exception\PageNotFoundException($e->getMessage(), $e->getCode(), $e);
            }

            # in controller terms, a view is a method
            $this->controller->setMethod($view);
        }
        else
            throw new Exception\ControllerNotFoundException("The control class '$fqn_controller' does not exists!");
    }

    /**
     * Execute the method matched in the controller
     *
     * @return  null
     */
    public function run()
    {
        $this->controller->execute();
    }

    /**
     * Adds a new route to router
     *
     * @param Array $route
     *
     * @throws LogicException
     *
     * @return null
     */
    public function addRoute(array $route)
    {
        $key = array_keys($route);
        $key = array_shift($key);

        if (array_key_exists($key, $this->routes))
            throw new \LogicException("The key '$key' was already defined as route");

        $this->routes = array_merge($this->routes, $route);
    }

    /**
     * Adds a new route to router
     *
     * @param string $name
     * @param Zend\Router\Http\RouteInterface $route
     *
     * @throws LogicException
     *
     * @return null
     */
    public function addZendRoute($name, \Zend\Router\Http\RouteInterface $route)
    {
        $this->zendRouter->addRoute($name, $route);
    }

    /**
     * Parse key value pairs from a string
     *
     * Searches for the pattern /var1/value1/var2/value2 and converts it to
     *
     * var1 => value1
     * var2 => value2
     *
     * @param string $unparsed
     *
     * @return array
     */
    private function parseKeyValuePairsFrom($unparsed)
    {
        $params = explode("/", $unparsed);

        $vars = $values = [];

        $i = 1;
        foreach ($params as $item)
        {
            if ($i % 2 != 0)
                $vars[] = $item;
            else
                $values[] = $item;
            $i++;
        }

        $vars_count = count($vars);

        $result = [];

        for ($i = 0; $i < $vars_count; $i++)
        {
            if (array_key_exists($i, $values))
                $result[$vars[$i]] = $values[$i];
            else
                $result[$vars[$i]] = '';
        }

        return $result;
    }
}