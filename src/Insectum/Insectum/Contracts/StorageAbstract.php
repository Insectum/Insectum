<?php

namespace Insectum\Insectum\Contracts;

use Insectum\Insectum\LogItem;

/**
 * Class StorageAbstract
 * @package Insectum\Insectum\Contracts
 */
abstract class StorageAbstract
{

    const RESOLVED_ONLY = 1;

    const UNRESOLVED_ONLY = -1;

    /**
     * @param ErrorAbstract $error
     * @param string $stage
     */
    public function write(ErrorAbstract $error, $stage)
    {
        $loggedError = $this->getErrorItem($error);

        $this->storeOccurrence($loggedError, $error, $stage);
    }

    /**
     * Get unique error item and store it if it's new
     * @param ErrorAbstract $error
     * @return mixed
     */
    abstract function getErrorItem(ErrorAbstract $error);

    /**
     * Store error occurrence in storage
     * @param ErrorAbstract $loggedError
     * @param ErrorAbstract $error
     * @param string $stage
     * @return mixed
     */
    abstract protected function storeOccurrence(ErrorAbstract $loggedError, ErrorAbstract $error, $stage);

    /**
     * Read log items of specified kind and with specified offset
     * @param string $kind
     * @param int $resolvedStatus
     * @param int $offset
     * @param int $limit
     * @return LogItem[]
     */
    abstract public function read($kind, $resolvedStatus = null, $offset = 0, $limit = 10);

    /**
     * Count total errors in log
     * @param $kind
     * @return int
     */
    abstract public function total($kind);

    /**
     * Count total resolved errors in log
     * @param $kind
     * @return int
     */
    abstract public function totalResolved($kind);

    /**
     * Count total unresolved errors in log
     * @param $kind
     * @return int
     */
    abstract public function totalUnresolved($kind);

    /**
     * Get classname for given error kind
     * @param $kind
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getClassOfKind($kind) {

        $class = "\\Insectum\\Insectum\\Errors\\" . ucfirst(strtolower($kind));

        if (!class_exists($class)) {
            throw new \InvalidArgumentException('Unsupported error kind');
        }

        return $class;
    }

} 