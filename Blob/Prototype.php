<?php

/**
 * Document Storage
 */

namespace attitude\Elements\StorageFlatfile;

use \attitude\Elements\DependencyContainer;
use \attitude\Elements\StorageFlatfile_Prototype;

use \attitude\Elements\Storage\Blob_AwareInterface;

/**
 * Document Storage Class
 *
 * Non-persistent PHP in memory storage engine.
 *
 * @author Martin Adamko <@martin_adamko>
 * @version v0.1.0
 * @licence MIT
 *
 */
abstract class Blob_Prototype extends StorageFlatfile_Prototype implements Blob_AwareInterface
{
/*  Future feature:
    public static $cache_storage = null; */

    /**
     * Path to a dir where to store data
     *
     * @var string
     */
    public $storage_path  = null;

    /**
     * Returns unique path to a file by provided key
     *
     * @param   string  $key
     * @returns string
     *
     */
    protected function lookupIdentifier($key)
    {
        static $subdir = null;

        if ($subdir===null) {
            $tmp = rtrim($this->storage_path, DIRECTORY_SEPARATOR).'/_blobs';

            if (! realpath($tmp)) {
                if (!mkdir($tmp, 0777, true)) {
                    trigger_error('Cannot create blobs subdirecotry.', E_USER_ERROR);
                }
            }

            $subdir =& $tmp;
        }

        return $subdir.DIRECTORY_SEPARATOR.$key;
    }

    public function find(array $args=array())
    {
        $results = array();

        foreach (glob($this->lookupIdentifier('*')) as $file) {
            $key = basename($file);

            $data = file_get_contents($file);

            if ($data===false) {
                trigger_error('`file_get_contents()` failed for '.$key.'.', E_USER_WARNING);

                return false;
            }

            $results[$key] = $this->data_serializer->unserialize($data);

            $results[$key]['_id']      = isset($results[$key]['_id'])      ? $results[$key]['_id']      : $key;
            $results[$key]['_created'] = isset($results[$key]['_created']) ? $results[$key]['_created'] : filectime($file);
            $results[$key]['_updated'] = isset($results[$key]['_updated']) ? $results[$key]['_updated'] : filemtime($file);

            ksort($results[$key]);
        }

        return array_values($results);
    }
}
