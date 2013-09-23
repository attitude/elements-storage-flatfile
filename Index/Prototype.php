<?php

/**
 * Document Storage
 */

namespace attitude\Elements\StorageFlatfile;

use \attitude\Elements\Storage\Index_AwareInterface;
use \attitude\Elements\StorageFlatfile_Prototype;
use \attitude\Elements\DependencyContainer;

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
abstract class Index_Prototype extends StorageFlatfile_Prototype implements Index_AwareInterface
{
/*  Future feature:
    public $cache_storage = null; */

    /**
     * Sets Index as unique
     *
     * @var bool
     *
     */
    protected $is_unique = false;

    protected function __construct()
    {
        $this->is_unique = DependencyContainer::get(get_called_class().'.is_unique');

        return parent::__construct();
    }

    /**
     * Returns uniqueness of the Index
     *
     * @param   void
     * @returns bool
     *
     */
    public function is_unique()
    {
        return !!$this->is_unique;
    }

    /**
     * Returns Index key-value pair
     *
     * Generates `subdir/file` string for further use.
     *
     * @param   string      $key    The key of the index.
     * @param   mixed       $var    The values to index.
     * @returns string
     *
     */
    protected function get_index_key($key, $var)
    {
        return ($var==='*' ? '*' : urlencode($var)).'/'.($key==='*' ? '*' : urlencode($key));
    }

    /**
     * Returns unique path to a file by provided key
     *
     * @param   string  $key    The key of the index.
     * @returns string
     *
     */
    protected function lookupIdentifier($key)
    {
        return $this->storage_path.'/'.$key;
    }

    /**
     * Creates a parent value directory
     *
     * Attempts to create a value subdirectory if it does not exist. When an
     * array of variables is passes, multiple subdirectories are being created.
     *
     * @param   string  $key    The key of the index.
     * @param   mixed   $var    The values to index.
     * @returns object          Returns `$this`
     *
     */
    protected function prepare_rows($key, $var)
    {
        if (is_array($var)) {
            foreach ($var as $v) {
                $this->prepare_rows($key, $v);
            }

            return;
        }

        // Create a parent value directory
        $row_name = $this->lookupIdentifier($this->get_index_key($key, $var));

        if (!file_exists(dirname($row_name))) {
            mkdir(dirname($row_name), 0777, true);
        }

        return $this;
    }

    /**
     * Checks if document exists
     *
     * @param   string  $key    The key or array of keys to fetch.
     * @returns bool            Returns TRUE on success or FALSE on failure.
     *
     */
    public function exists($key, $var='*')
    {
        return sizeof(glob($this->storage_path.'/'.$this->get_index_key($key, $var))) > 0 ? true : false;
    }

    /**
     * Returns values indexed for current object key
     *
     * @param   string  $key    The key of the index.
     * @returns mixed           Indexed values
     *
     */
    public function get($key, $var='*')
    {
        $rows = glob($this->storage_path.'/'.$this->get_index_key($key, $var));

        // Process names for values
        foreach ($rows as &$row) {
            $row = urldecode(basename($row));
        }

        return empty($rows) ? array() : $rows;
    }

    public function find(array $args=array())
    {
        trigger_error('Function '.__CLASS__.'::'.__FUNCTION__.'() is not ready');
    }

    /**
     * Add new indexes
     *
     * @param   string  $key    The key of the index.
     * @param   mixed   $var    The values to index.
     * @returns int|bool        Returns `TRUE` on success or `FALSE` on failure.
     *
     */
    public function add($key, $var)
    {
        // Cast as array of values to index
        $var = (array) $var;

        // Find all stored indexed values for current key
        $stored_indexes = $this->get($key);

        foreach ($stored_indexes as $stored_index) {
            // If any index already exists
            if (in_array($stored_index, $var)) {
                return false;
            }
        }

        // Prepare subdirecotories
        $this->prepare_rows($key, $var);

        // Add new indexes
        foreach ($var as $index_value) {
            // Case Unique: Not created but there might be already an entry
            if ($this->is_unique() && $this->exists('*', $index_value)) {
                return false;
            }

            // Create a new index
            if (!parent::set($this->get_index_key($key, $index_value), null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sets new or replaces and deletes indexes
     *
     * @param   string  $key    Object key
     * @param   mixed   $var    The values to index.
     *
     */
    public function set($key, $var)
    {
        // Cast as array of values to index
        $var = (array) $var;

        // Prepare subdirecotories
        $this->prepare_rows($key, $var);

        // Find all stored indexed values for current key
        $stored_indexes = $this->get($key);

        // Add new indexes
        foreach ($var as $index_value) {
            // They are already created
            if (in_array($index_value, $stored_indexes)) {
                continue;
            }

            // Case Unique: Not created but there might be already an entry
            if ($this->is_unique() && $this->exists('*', $index_value)) {
                return false;
            }

            // Create a new index
            if (!parent::set($this->get_index_key($key, $index_value), null)) {
                return false;
            }
        }

        // Remove old indexes for current document key
        foreach ($stored_indexes as $stored_index) {
            if (!in_array($stored_index, $var)) {
                if (!$this->delete($key, $stored_index)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Destroy indexes for document key-value pairs
     *
     * @param   string  $key    The key associated with the item to delete.
     * @returns null|bool       Returns TRUE on success or FALSE on failure.
     *                          NULL is being returned when there is nothing to
     *                          destroy.
     *
     */
    public function delete($key, $var='*')
    {
        $rows = glob($this->storage_path.'/'.$this->get_index_key($key, $var));

        // Process names for values
        foreach ($rows as &$row) {
            if (!unlink($row)) {
                return false;
            }
        }

        // Silent delete empty value directory
        @rmdir(dirname($row));

        return true;
    }
}
