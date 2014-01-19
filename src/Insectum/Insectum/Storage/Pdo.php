<?php

namespace Insectum\Insectum\Storage;

use Carbon\Carbon;
use Exception;
use Insectum\Insectum\Contracts\ErrorAbstract;
use Insectum\Insectum\Contracts\StorageAbstract;
use Insectum\Helpers\Arr;
use Insectum\Insectum\LogItem;
use Insectum\Insectum\Storage\Tools\PDOConnector;

/**
 * Class Pdo
 * @package Insectum\Insectum\Storage
 */
class Pdo extends StorageAbstract
{
    /**
     * @var PDOConnector
     */
    protected $connector;
    /**
     * @var string
     */
    protected $dbName;

    /**
     * Accepts three variants of arguments set
     *
     * First:
     * \PDO $connect, string $dbName
     *
     * Second:
     * string $dsn, string $username, string $password
     *
     * Third:
     * array $dbConfig (see parseConfigArray() method for details)
     *
     */
    function __construct(/* polymorphic */)
    {
        $params = func_get_args();
        $reflect = new \ReflectionClass('Insectum\\Insectum\\Storage\\Tools\\PDOConnector');
        $this->connector = $reflect->newInstanceArgs($params);
    }

    /**
     * Get unique error item and store it if it's new
     * @param ErrorAbstract $error
     * @return ErrorAbstract
     */
    public function getErrorItem(ErrorAbstract $error)
    {
        $errorsTable = $this->getErrorsTableOfKind($error->kind);

        $where = array();
        foreach ($error->listErrorFields() as $f) {
            $where[] = $f . ' = :' . $f;
        }
        $where = implode(' AND ', $where);
        $req = "SELECT * FROM {$errorsTable} WHERE {$where} LIMIT 1";

        $values = array_intersect_key($error->storable(), array_flip($error->listErrorFields()));

        $dbRecord = $this->runStatement($req, $values, $error->kind);
        if (!empty($dbRecord)) {
            $dbRecord = $dbRecord->fetch();
        }

        if (!empty($dbRecord)) {
            $class = $this->getClassOfKind($error->kind);
            return new $class((array)$dbRecord, $dbRecord->id);
        } else {

            $colNames = array_keys($error->storable());

            $cols = implode(',', $colNames);

            $vals = array();
            foreach ($colNames as $f) {
                $vals[] = ':' . $f;
            }
            $vals = implode(', ', $vals);

            $insert = "INSERT INTO {$errorsTable} ({$cols}) VALUES ({$vals})";

            $data = $error->storable();
            if (empty($data['created_at'])) {
                $data['created_at'] = new Carbon();
            }

            $this->runStatement($insert, $data, $error->kind);
            return $this->getErrorItem($error);
        }
    }

    /**
     * @param $kind
     * @return string
     */
    protected function getErrorsTableOfKind($kind)
    {
        return "insectum_" . strtolower($kind) . "_errors";
    }

    /**
     * Run \PDO statement
     *
     * @param string $statement
     * @param array $bindings
     * @param int $attempt
     * @return \PDOStatement
     * @throws \Exception
     */
    protected function runStatement($statement, $bindings = null, $kind, $attempt = 0)
    {
        try {
            $pdo = $this->connector->pdo();
            // We need to use exceptions
            // If PDO instance was provided in constructor — it can use different err mode
            // So check it and change temporarily if needed
            if (($oldErrmode = $pdo->getAttribute(\PDO::ATTR_ERRMODE)) != \PDO::ERRMODE_EXCEPTION) {
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }
            // Prepare statement
            $sth = $pdo->prepare($statement);
            // Return previous err mode
            if ($oldErrmode != \PDO::ERRMODE_EXCEPTION) {
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, $oldErrmode);
            }
            // Execute statement
            $sth->setFetchMode(\PDO::FETCH_OBJ);
            $sth->execute($bindings);

            return $sth;
        } catch (Exception $e) {
            if ($attempt == 0) {
                $this->migrate($kind);
                $this->runStatement($statement, $bindings, $kind, $attempt + 1);
            } else {
                // TODO: alert this somehow
                throw $e;
            }
        }
    }

    /**
     * Migrate the database
     */
    protected function migrate($kind)
    {
        $migrator = new Tools\Migrator($this->connector);
        $migrator->run($kind);
    }

    /**
     * Read log items of specified kind and with specified offset
     * @param string $kind
     * @param int $resolvedStatus
     * @param int $offset
     * @param int $limit
     * @return LogItem[]
     * @throws \InvalidArgumentException
     */
    public function read($kind, $resolvedStatus = null, $offset = 0, $limit = 10)
    {
        $class = $this->getClassOfKind($kind);

        $errorsTable = $this->getErrorsTableOfKind($kind);
        $occurrencesTable = $this->getOccurrencesTableOfKind($kind);

        // Get errors from db
        $getErrors = "SELECT * FROM {$errorsTable} AS ert LEFT JOIN {$occurrencesTable} AS oct ON oct.error_id = ert.id";
        if (!is_null($resolvedStatus)) {
            if ($resolvedStatus == self::RESOLVED_ONLY) {
                $getErrors .= " WHERE ert.resolved_at IS NOT NULL AND oct.occurred_at <= ert.resolved_at";
            } elseif ($resolvedStatus == self::UNRESOLVED_ONLY) {
                $getErrors .= " WHERE ert.resolved_at IS NULL OR oct.occurred_at > ert.resolved_at";
            }
        }
        $getErrors .= " ORDER BY ert.created_at DESC LIMIT :limit OFFSET :offset";
        $errors = $this->runStatement($getErrors, array(
            'limit' => $limit,
            'offset' => $offset
        ), $kind)->fetchAll();

        if (empty($errors)) {
            return array();
        }

        // Get ID's
        $ids = Arr::pluck($errors, 'id');

        // Get all occurrences for given errors
        $getOccurrences = "
        SELECT ert.*, oct.*, oct.id as occurrence_id, ert.id as error_id
        FROM {$errorsTable} AS ert
        LEFT JOIN {$occurrencesTable} AS oct
        ON oct.error_id = ert.id
        WHERE ert.id IN (?)";

        $occurrences = $this->runStatement($getOccurrences, array(implode(',', $ids)), $kind)->fetchAll();

        // Group errors by id
        $pool = array();
        foreach ($occurrences as $oc) {
            $pool[$oc->error_id][] = new $class($oc, $oc->error_id);;
        }

        // Convert pool to log items array
        array_walk($pool, function (&$item, $id) {
            $item = new LogItem($item[0], $item);
        });

        return $pool;
    }

    /**
     * @param $kind
     * @return string
     */
    protected function getOccurrencesTableOfKind($kind)
    {
        return "insectum_" . strtolower($kind) . "_occurrences";
    }

    /**
     * Count total errors in log
     * @param $kind
     * @return int
     */
    public function total($kind)
    {
        return $this->countErrors($kind);
    }

    /**
     * @param $kind
     * @param int $resolvedStatus
     * @return int
     */
    protected function countErrors($kind, $resolvedStatus = null)
    {
        $errorsTable = $this->getErrorsTableOfKind($kind);
        $occurrencesTable = $this->getOccurrencesTableOfKind($kind);

        $countErrors = "SELECT count(*) as total FROM {$errorsTable}";

        if (!is_null($resolvedStatus)) {
            if ($resolvedStatus == self::RESOLVED_ONLY) {
                $countErrors = "
                SELECT COUNT(*) as total
                FROM {$errorsTable} AS ert
                LEFT JOIN {$occurrencesTable} AS oct
                ON oct.error_id = ert.id
                WHERE ert.resolved_at IS NOT NULL AND oct.occurred_at <= ert.resolved_at";
            } elseif ($resolvedStatus == self::UNRESOLVED_ONLY) {
                $countErrors = "
                SELECT COUNT(*) as total
                FROM {$errorsTable} AS ert
                LEFT JOIN {$occurrencesTable} AS oct
                ON oct.error_id = ert.id
                WHERE ert.resolved_at IS NULL OR oct.occurred_at > ert.resolved_at";
            }
        }

        $res = $this->runStatement($countErrors, null, $kind);

        return intval($res->fetch()->total);

    }

    /**
     * Count total resolved errors in log
     * @param $kind
     * @return int
     */
    public function totalResolved($kind)
    {
        return $this->countErrors($kind, self::RESOLVED_ONLY);
    }

    /**
     * Count total unresolved errors in log
     * @param $kind
     * @return int
     */
    public function totalUnresolved($kind)
    {
        return $this->countErrors($kind, self::UNRESOLVED_ONLY);
    }

    /**
     * Store error occurrence in storage
     * @param ErrorAbstract $loggedError
     * @param ErrorAbstract $error
     * @param string $stage
     * @throws \InvalidArgumentException
     */
    protected function storeOccurrence(ErrorAbstract $loggedError, ErrorAbstract $error, $stage)
    {
        $data = $error->occurrence()->storable();
        $data['error_id'] = $loggedError->id;
        $data['stage'] = $stage;
        if (empty($data['occurred_at'])) {
            $data['occurred_at'] = new Carbon();
        }

        if (empty($data['error_id'])) {
            throw new \InvalidArgumentException('You should pass a stored error item');
        }

        $occurrencesTable = $this->getOccurrencesTableOfKind($loggedError->kind);

        $colNames = array_keys($data);

        $cols = implode(',', $colNames);

        $vals = array();
        foreach ($colNames as $f) {
            $vals[] = ':' . $f;
        }
        $vals = implode(', ', $vals);

        $insert = "INSERT INTO {$occurrencesTable} ({$cols}) VALUES ({$vals})";

        $this->runStatement($insert, $data, $loggedError->kind);
    }


} 