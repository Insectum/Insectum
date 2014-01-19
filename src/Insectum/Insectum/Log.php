<?php

namespace Insectum\Insectum;

use Carbon\Carbon;
use Insectum\Insectum\Contracts\ErrorAbstract;
use Insectum\Insectum\Contracts\StorageAbstract;
use Insectum\Helpers\Arr;


/**
 * Class Log
 * @package Insectum\Insectum
 */
class Log
{
    /**
     * @var array | \ArrayAccess
     */
    protected $config;
    /**
     * @var Contracts\StorageAbstract
     */
    protected $storage;
    /**
     * @var array
     */
    protected $hooks;

    /**
     * @param $config
     * @param StorageAbstract $storage
     * @param string $stage
     */
    function __construct(array $config, StorageAbstract $storage)
    {
        $this->config = $config;
        $this->storage = $storage;

        $this->hooks = $this->registerHooks(Arr::get($this->config, 'hooks', null));
    }

    /**
     * Register hooks based on config
     * @return array
     */
    protected function registerHooks()
    {
        // TODO: зарегистрировать хуки
        return array();
    }

    /**
     * Write error to log
     * @param $type
     * @param $payload
     * @return mixed
     */
    public function write($kind, $payload, $stage = 'production')
    {
        $errorClass = 'Insectum\Insectum\Errors\\' . ucfirst(strtolower($kind));
        $error = new $errorClass($payload);

        $this->storage->write($error, $stage);
        $this->runHooks($error);

        return $error;
    }

    /**
     * Run registered hooks
     * @param ErrorAbstract $error
     */
    protected function runHooks(ErrorAbstract $error)
    {
        if ($this->hooks) {
            // TODO: run hooks
        }
    }

    /**
     * Read log items of specified kind and with specified offset
     * @param string $kind
     * @param int $offset
     * @param int $limit
     * @return LogItem[]
     */
    public function read($kind, $offset = 0, $limit = 10)
    {
        return $this->storage->read($kind, null, $offset, $limit);
    }

    /**
     * Read unresolved log items of specified kind and with specified offset
     * @param string $kind
     * @param int $offset
     * @param int $limit
     * @return LogItem[]
     */
    public function readUnresolved($kind, $offset = 0, $limit = 10)
    {
        return $this->storage->read($kind, StorageAbstract::UNRESOLVED_ONLY, $offset, $limit);
    }

    /**
     * Read resolved log items of specified kind and with specified offset
     * @param string $kind
     * @param int $offset
     * @param int $limit
     * @return LogItem[]
     */
    public function readResolved($kind, $offset = 0, $limit = 10)
    {
        return $this->storage->read($kind, StorageAbstract::RESOLVED_ONLY, $offset, $limit);
    }

    /**
     * Count total errors in log
     * @param $kind
     * @return int
     */
    public function total($kind)
    {
        return $this->storage->total($kind);
    }

    /**
     * Count total resolved errors in log
     * @param $kind
     * @return int
     */
    public function totalResolved($kind)
    {
        return $this->storage->totalResolved($kind);
    }

    /**
     * Count total unresolved errors in log
     * @param $kind
     * @return int
     */
    public function totalUnresolved($kind)
    {
        return $this->storage->totalUnresolved($kind);
    }

} 