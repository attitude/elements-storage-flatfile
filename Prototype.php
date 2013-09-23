<?php

/**
 * Flat File Storage
 */

namespace attitude\Elements;

/**
 * File Storage class
 *
 * Persistent flat-file storage engine.
 *
 * @author Martin Adamko <@martin_adamko>
 * @version v0.1.0
 * @licence MIT
 *
 */
abstract class StorageFlatfile_Prototype implements Storage_Interface
{
/*  Future feature:
    public static $cache_storage = null; */

    /**
     * Path to a dir where to storo data
     *
     * @var string
     */
    public $storage_path    = null;

    /**
     * Serializer object
     *
     * @var \attitude\Interfaces\Data\Serializer
     */
    public $data_serializer = null;

    /**
     * Class Constructor
     *
     * Protected visibility allows building singleton class.
     *
     * @param   void
     * @returns object  Returns `$this`.
     *
     */
    protected function __construct()
    {
        $this->storage_path    = DependencyContainer::get(get_called_class().'.storage_path');
        $this->data_serializer = DependencyContainer::get(get_called_class().'.data_serializer');

        if (!realpath($this->storage_path)) {
            if (! mkdir($this->storage_path, 0777, true)) {
                exit ('Cannot create '.get_called_class().' namespace in file system storage.');
            }
        }

        return $this;
    }

    /**
     * Unserializes data using serializer object
     *
     * @param   string  $data
     *
     */
    protected function unserialize($data)
    {
        return $this->data_serializer->unserialize($data);
    }

    /**
     * Serializes data using serializer object
     *
     * @param   mixed   $data
     */
    protected function serialize($data)
    {
        return $this->data_serializer->serialize($data);
    }

    /**
     * Returns unique path to a file by provided key
     *
     * @param   string  $key
     * @returns string
     *
     */
    abstract protected function lookupIdentifier($key);

    /**
     * Checks if document exists
     *
     * @param   string  $key    The key or array of keys to fetch.
     * @returns bool            Returns TRUE on success or FALSE on failure.
     *
     */
    public function exists($key)
    {
        if (!file_exists($this->lookupIdentifier($key))) {
            return false;
        }

        return true;
    }

    /**
     * Returns document
     *
     * @param   string  $key    The key or array of keys to fetch.
     * @returns mixed           Returns the object associated with the key or an
     *                          array of found key-value pairs when key is an
     *                          array. Returns FALSE on failure, key is not
     *                          found or key is an empty array.
     *
     */
    public function get($key)
    {
        if ($this->exists($key)) {
            if (!$data = file_get_contents($this->lookupIdentifier($key))) {
                trigger_error('`file_get_contents()` failed for '.$this->lookupIdentifier($key).'.', E_USER_WARNING);

                return false;
            }

            return $this->unserialize($data);
        }

        return null;
    }

    public function find(array $args=array())
    {
        $results = array();

        foreach (glob($this->lookupIdentifier('*')) as $file) {
            $data = file_get_contents($file);

            if ($data===false) {
                trigger_error('`file_get_contents()` failed for '.$file.'.', E_USER_WARNING);

                return false;
            }

            $results[basename($file)] = $data;
        }

        return $results;
    }

    /**
     * Add new document
     *
     * @param   string  $key    The key that will be associated with the item.
     * @param   mixed   $var    The variable to store. Strings and integers are
     *                          stored as is, other types are stored serialized.
     * @returns bool            Returns `$key` on success or FALSE on failure.
     *
     */
    public function add($key, $var)
    {
        if ($this->exists($key)) {
            return false;
        }

        return $this->set($key, $var);
    }

    /**
     * Sets new or replaces document
     *
     * @param   string  $key    The key that will be associated with the item.
     * @param   mixed   $var    The variable to store. Strings and integers are
     *                          stored as is, other types are stored serialized.
     * @returns bool            Returns `$key` on success or FALSE on failure.
     *
     */
    public function set($key, $var)
    {
        if (file_put_contents($this->lookupIdentifier($key), $this->serialize($var))===false) {
            trigger_error('`file_put_contents()` failed for '.$this->lookupIdentifier($key).'.', E_USER_WARNING);

            return false;
        }

        return $key;
    }

    /**
     * Replaces existing document
     *
     * @param   string  $key    The key that will be associated with the item.
     * @param   mixed   $var    The variable to store. Strings and integers are
     *                          stored as is, other types are stored serialized.
     * @returns bool            Returns `$key` on success or FALSE on failure.
     *
     */
    public function replace($key, $var)
    {
        if (!$this->exists($key)) {
            return false;
        }

        return $this->set($key, $var);
    }

    /**
     * Destroys document
     *
     * @param   string  $key    The key associated with the item to delete.
     * @returns null|bool       Returns TRUE on success or FALSE on failure.
     *                          NULL is being returned when there is nothing to
     *                          destroy.
     *
     */
    public function delete($key)
    {
        if (!$this->exists($key)) {
            return null;
        }

        return unlink($this->lookupIdentifier($key));
    }
}
