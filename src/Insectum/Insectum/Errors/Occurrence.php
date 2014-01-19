<?php

namespace Insectum\Insectum\Errors;

use Carbon\Carbon;
use Insectum\Insectum\Contracts\ContainerAbstract;

/**
 * Class Occurrence
 * @package Insectum\Insectum\Errors
 */
class Occurrence extends ContainerAbstract
{
    /**
     * @param array $data
     * @param array $fields
     * @param array $serialized
     * @param array $dates
     */
    function __construct(array $data, array $fields, array $serialized = null, array $dates = null)
    {
        parent::__construct($data);
        $this->fields = $fields;
        $this->serialized = $serialized;
        $this->ensureSystemFields();
    }

    /**
     * Ensure that system required fields are inited
     */
    protected function ensureSystemFields() {
        foreach (array('occurred_at', 'client_type', 'client_id') as $f) {
            if ( ! in_array($f, $this->fields) ) {
                $this->fields[] = $f;
            }
        }

        if ( ! in_array('occurred_at', $this->dates) ) {
            $this->dates[] = 'occurred_at';
        }
        if ( ! isset($this->data['occurred_at']) ) {
            $this->data['occurred_at'] = new Carbon();
        }
    }

    /**
     * Set the $fields property
     * Fields are already set in constructor!
     */
    protected function initFields()
    {
    }

} 