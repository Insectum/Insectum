<?php

namespace Insectum\Insectum;

use Insectum\Insectum\Contracts\ErrorAbstract;

class LogItem
{

    /**
     * @var Contracts\ErrorAbstract
     */
    protected $baseError;
    /**
     * @var Contracts\ErrorAbstract[]
     */
    protected $errors;
    protected $lastOccurrence;
    protected $occurrencesTotal;
    protected $occurrencesUnresolved;

    function __construct(ErrorAbstract $baseError, array $errors)
    {
        $this->baseError = $baseError;
        $this->errors = $errors;
    }

    function __call($name, $arguments)
    {
        return call_user_func_array(array($this->baseError, $name), $arguments);
    }

    function __get($name)
    {
        return $this->baseError->{$name};
    }

    public function occurrencesTotal()
    {
        if (is_null($this->occurrencesTotal)) {
            $this->occurrencesTotal = count($this->errors);
        }
        return $this->occurrencesTotal;
    }

    public function occurrencesUnresolved()
    {
        if (is_null($this->occurrencesUnresolved)) {
            $this->occurrencesUnresolved = 0;
            foreach ($this->errors as $err) {
                if ( $err->occurred_at > $this->baseError->resolved_at ) {
                    $this->occurrencesUnresolved++;
                }
            }
        }
        return $this->occurrencesUnresolved;
    }

    public function lastOccurrence(){
        if (is_null($this->lastOccurrence)) {
            $this->lastOccurrence = $this->errors[0]->occurrence()->occurred_at;
        }
        return $this->lastOccurrence;
    }


}