<?php
/**
 * DronePHP (http://www.dronephp.com)
 *
 * @link      http://github.com/Pleets/DronePHP
 * @copyright Copyright (c) 2016-2018 Pleets. (http://www.pleets.org)
 * @license   http://www.dronephp.com/license
 * @author    Darío Rivera <fermius.us@gmail.com>
 */

namespace Drone\Db;

/**
 * SQLFunction class
 *
 * This class could be used to build specific querys that requires
 * specific database functions that data mapper does not support
 */
class SQLFunction
{
    /**
     * The SQL function name
     *
     * @var string
     */
    private $function;

    /**
     * The arguments for the SQL function
     *
     * @var array
     */
    private $arguments;

    /**
     * Returns the SQL function
     *
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Returns the arguments for the SQL function
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Constructor
     *
     * @param string $function
     * @param array $args
     *
     * @return null
     */
    public function __construct($function, array $args)
    {
        $this->function  = $function;
        $this->arguments = $args;
    }

    /**
     * Returns the SQL statment
     *
     * @return string
     */
    public function getStatement()
    {
        $arguments = $this->arguments;

        foreach ($arguments as $key => $arg) {
            if (is_string($arg)) {
                $arguments[$key] = "'$arg'";
            }
        }

        $arguments = implode(", ", array_values($arguments));

        return $this->function . '(' . $arguments . ')';
    }
}
