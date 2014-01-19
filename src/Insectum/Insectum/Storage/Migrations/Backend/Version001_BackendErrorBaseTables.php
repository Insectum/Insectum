<?php

namespace Insectum\Insectum\Storage\Migrations\Backend;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version001_BackendErrorBaseTables extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->createTable('insectum_backend_errors');
        $table->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
        $table->addColumn('kind', 'string');
        $table->addColumn('type', 'string', array('notnull' => false, 'default' => null));
        $table->addColumn('code', 'integer', array('notnull' => false, 'default' => null));
        $table->addColumn('msg', 'string', array('notnull' => false, 'default' => null));
        $table->addColumn('file', 'string', array('notnull' => false, 'default' => null));
        $table->addColumn('line', 'integer', array('notnull' => false, 'default' => null));
        $table->addColumn('created_at', 'datetime', array('notnull' => false, 'default' => null));
        $table->addColumn('resolved_at', 'datetime', array('notnull' => false, 'default' => null));

        $table->setPrimaryKey(array("id"));
        $table->addUniqueIndex(array(
            'kind',
            'type',
            'code',
            'msg',
            'file',
            'line'
        ));
        $table->addIndex(array('created_at'));
        $table->addIndex(array('resolved_at'));

        $table2 = $schema->createTable('insectum_backend_occurrences');
        $table2->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
        $table2->addColumn('error_id', 'integer', array('unsigned' => true));
        $table2->addColumn('method', 'string', array('notnull' => false, 'default' => null));
        $table2->addColumn('url', 'string', array('notnull' => false, 'default' => null));
        $table2->addColumn('stage', 'string', array('notnull' => false, 'default' => null));
        $table2->addColumn('server_name', 'string', array('notnull' => false, 'default' => null));
        $table2->addColumn('server_ip', 'string', array('notnull' => false, 'default' => null));
        $table2->addColumn('backtrace', 'text', array('notnull' => false, 'default' => null));
        $table2->addColumn('context', 'text', array('notnull' => false, 'default' => null));
        $table2->addColumn('session', 'text', array('notnull' => false, 'default' => null));
        $table2->addColumn('client_type', 'string', array('notnull' => false, 'default' => null));
        $table2->addColumn('client_id', 'string', array('notnull' => false, 'default' => null));
        $table2->addColumn('client_ip', 'string', array('notnull' => false, 'default' => null));
        $table2->addColumn('occurred_at', 'datetime', array('notnull' => false, 'default' => null));

        $table2->setPrimaryKey(array("id"));
        $table2->addIndex(array('error_id'));
        $table2->addIndex(array('occurred_at'));
        $table2->addIndex(array('stage'));
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('insectum_backend_errors');
        $schema->dropTable('insectum_backend_occurrences');
    }
}