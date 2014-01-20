<?php

namespace Insectum\Insectum\Errors;

use Insectum\Insectum\Contracts\ErrorAbstract;

class Backend extends ErrorAbstract {

    protected $kind = 'backend';

    /**
     * Short summary description
     * @return string
     */
    public function summary()
    {
        $msg = '%s in %s on line %s';
        return !empty($this->msg) ? $this->msg : sprintf($msg, $this->type, $this->file, $this->line);
    }

    /**
     * List error properties
     * @return array
     */
    public function listErrorFields()
    {
        return array(
            'kind', // Backend, Js, Webserver, etc
            'type', // Exception type
            'code',
            'msg',
            'file',
            'line'
        );
    }

    /**
     * List error occurrence properties
     * @return array
     */
    public function listErrorOccurrenceFields()
    {
        return array(
            'method',
            'url',
            'stage',
            'server_name',
            'server_ip',
            'backtrace',
            'context', // context for error and get_defined_vars for exception
            'session',
            'client_ip'
        );
    }

    /**
     * Set the fields properties
     */
    protected function initFields()
    {
        parent::initFields();
        $this->dates = array('resolved_at');
        $this->serialized = array('session');
    }


} 