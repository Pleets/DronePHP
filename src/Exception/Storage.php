<?php
/**
 * DronePHP (http://www.dronephp.com)
 *
 * @link      http://github.com/Pleets/DronePHP
 * @copyright Copyright (c) 2016-2017 Pleets. (http://www.pleets.org)
 * @license   http://www.dronephp.com/license
 * @author    Darío Rivera <dario@pleets.org>
 */

namespace Drone\Exception;

use Drone\Error\Errno;

/**
 * Storage class
 *
 * This is a helper class to store exceptions
 */
class Storage
{
    use \Drone\Error\ErrorTrait;

    /**
     * Output file
     *
     * @var string
     */
    protected $outputFile;

    /**
     * Constructor
     *
     * @param string $outputFile
     *
     * @return null
     */
    public function __construct($outputFile)
    {
        $this->outputFile = $outputFile;
    }

    /**
     * Stores the exception serializing the object
     *
     * @param Exception $exception
     * @param string    $outputFile
     *
     * @return string|boolean
     */
    public function store(\Exception $exception)
    {
        # simple way to generate a unique id
        $id = time() . uniqid();

        $data = [];

        if (file_exists($this->outputFile))
        {
            $string = file_get_contents($this->outputFile);

            if (!empty($string))
            {
                $data   = json_decode($string, true);

                # json_encode can be return TRUE, FALSE or NULL (http://php.net/manual/en/function.json-decode.php)
                if (is_null($data) || $data === false)
                {
                    $this->error(Errno::JSON_DECODE_ERROR, $this->outputFile);
                    return false;
                }
            }
        }

        $data[$id] = [
            "message" => $exception->getMessage(),
            "object"  => serialize($exception)
        ];

        if (($encoded_data = json_encode($data)) === false)
        {
            $this->error(Errno::JSON_ENCODE_ERROR, $this->outputFile);
            return false;
        }

        $hd = @fopen($this->outputFile, "w+");

        if (!$hd || !@fwrite($hd, $encoded_data))
        {
            $this->error(Errno::FILE_PERMISSION_DENIED, $this->outputFile);
            return false;
        }

        @fclose($hd);

        return $id;
    }
}