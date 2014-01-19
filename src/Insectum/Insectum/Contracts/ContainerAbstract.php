<?php

namespace Insectum\Insectum\Contracts;

use Insectum\Helpers\Arr;


/**
 * Class ContainerAbstract
 * @package Insectum\Insectum\Contracts
 */
abstract class ContainerAbstract implements \Serializable, \Iterator, \ArrayAccess
{

    /**
     * Raw input data
     * @var array
     */
    protected $data = array();
    /**
     * Properties of error
     * @var array
     */
    protected $fields = array();
    /**
     * List of fields containing dates to be converted to \Carbon\Carbon
     * @var array
     */
    protected $dates = array();
    /**
     * List of fields that should be serialized/unserialized
     * @var array
     */
    protected $serialized = array();
    /**
     * Cache for processed fields
     * @var array
     */
    private $cache = array();
    /**
     * Position of internal iterator
     * @var int
     */
    private $iteratorPosition = 0;

    /**
     * @param array $data
     */
    public function  __construct(array $data)
    {
        $this->data = $data;
        $this->initFields();
    }

    /**
     * Set the fields properties
     */
    abstract protected function initFields();

    /**
     * Get raw data
     * @return array
     */
    public function getData()
    {
        return $this->getData();
    }

    /**
     * Return as an array
     * @return array
     */
    public function toArray()
    {
        $vals = array_map(function ($field) {
            return $this->__get($field);
        }, $this->getFields());

        return array_combine($this->getFields(), $vals);
    }

    /**
     * @param $field
     * @return mixed
     */
    public function __get($field)
    {
        // If this is not defined field - forget about it
        if (!in_array($field, $this->fields)) {
            return null;
        }

        if (isset($this->cache[$field])) {
            return $this->cache[$field];
        }

        $v = Arr::get($this->data, $field, null);

        if (in_array($field, $this->dates) && !is_null($v)) {
            $v = new \Carbon\Carbon($v);
        } elseif (in_array($field, $this->serialized) && !is_null($v)) {
            if (is_string($v)) {
                $check = @unserialize($v);
                if ($check !== false || $v === 'b:0;') {
                    $v = $check;
                }
            }
        }

        $this->cache[$field] = $v;

        return $v;
    }

    /**
     * Get fields list
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Return normalized storable array
     * @return array
     */
    public function storable()
    {
        return $this->storableSet($this->fields);
    }

    /**
     * Return array containing all fields listed in $fieldsList and prepared for storing
     * @param $fieldsList
     * @return array
     */
    protected function storableSet($fieldsList)
    {
        $vals = array_map(function ($field) {

            // grab all fields from raw data array or nulls instead of absent
            $v = $this->__get($field);

            // If this field should be a date - prepare it
            if (in_array($field, $this->dates)) {
                // Normalize
                if (is_string($v)) {
                    $v = (new \Carbon\Carbon($v))->format('Y-m-d H:i:s');
                } elseif ($v instanceof \DateTime || $v instanceof \Carbon\Carbon) {
                    $v = $v->format('Y-m-d H:i:s');
                }
            } // If this field should be serialized - prepare it
            elseif (in_array($field, $this->serialized)) {
                if (!is_string($v)) {
                    try {
                        // Suppress serialization errors
                        $v = @serialize($v);
                    } catch (\Exception $e) {
                        $v = print_r($v, true);
                    }
                } else {
                    // Check if it is not serialized already
                    $check = @unserialize($v);
                    if ($check === false && $v !== 'b:0;') {
                        $v = serialize($v);
                    }
                }
            }
            elseif (!is_string($v)) {
                $v = print_r($v, true);
            }
            return $v;

        }, $fieldsList);

        return array_combine($fieldsList, $vals);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize($this->data);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->data = unserialize($serialized);
        $this->initFields();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        $field = $this->fields[$this->iteratorPosition];
        return $this->__get($field);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        ++$this->iteratorPosition;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->fields[$this->iteratorPosition];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return isset($this->fields[$this->iteratorPosition]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->iteratorPosition = 0;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return in_array($offset, $this->fields);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return; // not supported
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        return; // not supported
    }


} 