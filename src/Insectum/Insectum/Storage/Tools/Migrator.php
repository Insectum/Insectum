<?php

namespace Insectum\Insectum\Storage\Tools;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Connection;
use PDO, Exception;

/**
 * Class Migrator
 * @package Insectum\Insectum
 */
class Migrator
{

    /**
     * @var PDOConnector
     */
    private $connect;


    /**
     * @param PDOConnector $connect
     */
    function __construct(PDOConnector $connect)
    {
        $this->connect = $connect;
    }

    /**
     * Run the migration for given error kind
     * @param string $kind
     * @param string|null $version
     * @return array
     * @throws \Exception
     */
    public function run($kind, $version = null)
    {
        $kind = ucfirst(strtolower($kind));

        $ds = DIRECTORY_SEPARATOR;

        $config = new Configuration($this->connect->dbal());

        $config->setName('Insectum Migrate ' . $kind);
        $config->setMigrationsTableName('insectum_migration_versions');
        $config->setMigrationsNamespace('Insectum\\Insectum\\Storage\\Migrations\\' . $kind);
        $config->setMigrationsDirectory( dirname(__DIR__) . $ds . 'Migrations' . $ds . $kind );
        $config->registerMigrationsFromDirectory($config->getMigrationsDirectory());

        $migration = new Migration($config);

        try {
            return $migration->migrate($version);
        } catch (Exception $e) {
            // TODO: alert this somehow
            throw $e;
        }

    }


} 