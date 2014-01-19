<?php

namespace Insectum\Insectum\Storage\Tools;

use Exception;
use Insectum\Helpers\Arr;
use PDO;


/**
 * Class PDOConnector
 * @package Insectum\Insectum\Storage\Tools
 */
class PDOConnector
{

    /**
     * @var PDO
     */
    protected $connect;
    /**
     * @var string
     */
    protected $dbName;
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $dbalConnection;

    /**
     * @param mixed $connect
     * @param string|null $dbName
     */
    function __construct($connect, $dbName = null)
    {
        $this->initConnection($connect, $dbName);
    }

    /**
     * @param mixed $connect
     * @param string|null $dbName
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected function initConnection($connect, $dbName = null)
    {

        if ($connect instanceof \PDO) {

            if (is_null($dbName)) {
                throw new \InvalidArgumentException('You should provide DB name');
            }

            $this->connect = $connect;
            $this->dbName = $dbName;

        } elseif (is_string($connect)) {

            try {
                $this->connect = new \PDO($connect);
                $this->connect->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $this->dbName = $this->getDbNameFromDsn($connect);
            } catch (Exception $e) {
                // TODO: alert this somehow
                throw new Exception('Failed to connect the database: ' . $e->getMessage());
            }

        } elseif (is_array($connect)) {

            $this->parseConfigArray($connect);

        }

    }

    /**
     * @param string $dsn
     * @return string|null
     */
    protected function getDbNameFromDsn($dsn)
    {

        $dbName = null;

        $split = explode(':', $dsn);
        $driver = $split[0];
        $params = explode(';', $split[1]);
        array_walk($params, function (&$item) {
            $ar = explode('=', $item);
            $item = array(
                'key' => $ar[0],
                'value' => $ar[1]
            );
        });
        foreach ($params as $p) {
            if ($p['key'] == 'dbname' || $p['key'] == 'Database') {
                $dbName = $p['value'];
                break;
            }
        }

        return $dbName;
    }

    /**
     * @param array $connect
     */
    protected function parseConfigArray($connect)
    {

        // Get all options that may be needed, get NULL for absent
        $driver = Arr::get($connect, 'driver', 'mysql');

        $config['host'] = Arr::get($connect, 'host', '');
        $config['port'] = Arr::get($connect, 'port');
        $config['unix_socket'] = Arr::get($connect, 'unix_socket');
        $config['username'] = Arr::get($connect, 'username');
        $config['password'] = Arr::get($connect, 'password');
        $config['database'] = Arr::get($connect, 'database');

        // All other items in array will be passed as options
        $options = array_diff_key($connect, $config);

        $dsn = $this->getDsn($driver, $config);

        $this->initConnection($dsn);

    }

    /**
     * @param string $driver
     * @param array $config
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getDsn($driver, $config)
    {
        // Prepare DSN for different drivers
        $dsn = "";
        switch ($driver) {
            case 'sqlite':
                $dsn = "sqlite:{$config['database']}";
                break;
            case 'mysql':
                $dsn = "mysql:host={$config['host']};dbname={$config['database']}";
                if ($config['port']) {
                    $dsn .= ";port={$config['port']}";
                }
                if ($config['unix_socket']) {
                    $dsn .= ";unix_socket={$config['unix_socket']}";
                }
                break;
            case 'pgsql':
                $dsn = "pgsql:{$config['host']}dbname={$config['database']}";
                if ($config['port']) {
                    $dsn .= ";port={$config['port']}";
                }
                break;
            case 'sqlsrv':
            case 'dblib':
                $config['port'] = $config['port'] ? ',' . $config['port'] : '';
                if (in_array('dblib', \PDO::getAvailableDrivers())) {
                    $dsn = "dblib:host={$config['host']}{$config['port']};dbname={$config['database']}";
                } else {
                    $dsn = "sqlsrv:Server={$config['host']}{$config['port']}";
                    if ($config['database']) {
                        $dsn .= ";Database={$config['database']}";
                    }
                }
                break;
            default:
                throw new \InvalidArgumentException('Unsupported driver. Currently supported: sqlite, mysql, pgsql, sqlsrv, dblib');
        }
        return $dsn;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function dbal()
    {

        if (is_null($this->dbalConnection)) {

            $connectParams = array(
                'pdo' => $this->connect,
                'dbname' => $this->dbName
            );
            // create dbal driver from pdo driver
            $pdo_driver = $this->connect->getAttribute(PDO::ATTR_DRIVER_NAME);
            $dbal_driver = 'PDO' . ucfirst(strtolower($pdo_driver));
            $dbal_driver = str_replace('sql', 'Sql', $dbal_driver);
            $dbal_driver_class = '\Doctrine\DBAL\Driver\\' . $dbal_driver . '\Driver';

            $driver = new $dbal_driver_class;

            $this->dbalConnection = new \Doctrine\DBAL\Connection($connectParams, $driver);

            // Hack for doctrine..
            // See http://wildlyinaccurate.com/doctrine-2-resolving-unknown-database-type-enum-requested
            // http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/mysql-enums.html
            $this->dbalConnection->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        }

        return $this->dbalConnection;

    }

    /**
     * @return PDO
     */
    public function pdo()
    {
        return $this->connect;
    }

    /**
     * @return string
     */
    public function dbName()
    {
        return $this->dbName;
    }


} 