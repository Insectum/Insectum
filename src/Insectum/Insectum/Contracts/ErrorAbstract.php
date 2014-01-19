<?php

namespace Insectum\Insectum\Contracts;

use Insectum\Insectum\Errors\Occurrence;
use Insectum\Helpers\Arr;

/**
 * Class ErrorAbstract
 * @package Insectum\Insectum\Contracts
 */
abstract class ErrorAbstract extends ContainerAbstract
{
    /**
     * @var int|null
     */
    protected $id;
    /**
     * @var string
     */
    protected $kind;
    /**
    /**
     * Properties of error occurrence
     * @var array
     */
    protected $occurrenceFields = array();
    /**
     * Cache for Occurrence obj
     * @var array
     */
    private $occurrenceCached;

    /**
     * @param array $data
     * @param int $id
     */
    function __construct(array $data, $id = null)
    {
        parent::__construct($data);
        $this->ensureSystemFields();

        $this->id = !is_null($id) ? intval($id) : null;
    }

    public function __get($field) {
        if ( $field == 'id' || $field == 'kind' ) {
            return $this->{$field};
        }
        else return parent::__get($field);
    }

    /**
     * Ensure that system required fields are inited
     */
    protected function ensureSystemFields()
    {
        if (!in_array('created_at', $this->fields)) {
            $this->fields[] = 'created_at';
        }
        if (!in_array('resolved_at', $this->fields)) {
            $this->fields[] = 'resolved_at';
        }
        if (!in_array('created_at', $this->dates)) {
            $this->dates[] = 'created_at';
        }
        if (!in_array('resolved_at', $this->dates)) {
            $this->dates[] = 'resolved_at';
        }
    }

    /**
     * Short summary description
     * @return string
     */
    abstract public function summary();

    /**
     * Information about occurrence of this error
     * @return Occurrence|bool
     */
    public function occurrence()
    {
        if (is_null($this->occurrenceCached)) {
            if (count(Arr::only($this->data, $this->occurrenceFields)) > 0) {
                $this->occurrenceCached = new Occurrence(
                    $this->data,
                    $this->occurrenceFields,
                    $this->serialized,
                    $this->dates);
            } else {
                $this->occurrenceCached = false;
            }
        }
        return $this->occurrenceCached;
    }

    /**
     * @return bool
     */
    public function isResolved()
    {
        if (is_null($this->__get('resolved_at'))) {
            return false;
        }
        else {
            //TODO: check last occurrance date!
        }
    }

    /**
     * Set the fields properties
     */
    protected function initFields()
    {
        $this->fields = $this->listErrorFields();
        $this->occurrenceFields = $this->listErrorOccurrenceFields();
    }

    /**
     * List error properties
     * @return array
     */
    abstract public function listErrorFields();

    /**
     * List error occurrence properties
     * @return array
     */
    abstract public function listErrorOccurrenceFields();

}