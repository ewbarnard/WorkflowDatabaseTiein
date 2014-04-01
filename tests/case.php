<?php
/**
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package WorkflowDatabaseTiein
 * @subpackage Tests
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

$dir = realpath(__DIR__."/../../Workflow/tests");
if(!is_dir($dir)) {
$dir = realpath(__DIR__."/../../workflow/tests");
}
$case = "$dir/case.php";
require_once $case;

/**
 * @package WorkflowDatabaseTiein
 * @subpackage Tests
 */
abstract class ezcWorkflowDatabaseTieinTestCase extends ezcWorkflowTestCase
{
    protected $db;
    protected $dbStorage;
    protected $prefix;

    protected function setUp()
    {
        parent::setUp();

        try
        {
            /**
             * Ed Barnard, 2014-03-14 
             * Provide database connection 
             * Adjust canned database schema to include prefix 
             */
            $options = new ezcWorkflowDatabaseOptions;
            $this->prefix = $options->prefix;
            $dsn = defined('ZETA_TESTSUITE_DSN') ? ZETA_TESTSUITE_DSN : 'sqlite://:memory:';
            $this->db = ezcDbFactory::create( $dsn );

            $this->cleanupTables( $this->db );

            $schema = ezcDbSchema::createFromFile(
              'array',
              dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'workflow.dba'
              ,true /* Modify schema to include table prefix */
            );

            $schema->writeToDb( $this->db );

            $this->dbStorage = new ezcWorkflowDatabaseDefinitionStorage( $this->db );
        }
        catch ( Exception $e )
        {
            $this->markTestSkipped( 'No test database has been configured: ' . $e->getMessage() );
        }
    }

    protected function tearDown()
    {
        if ( $this->db !== null )
        {
            $this->cleanupTables();
        }

        $this->db = null;
        $this->dbStorage = null;
    }

    protected function cleanupTables()
    {
        switch ( $this->db->getName() )
        {
            case 'pgsql':
            {
                $tables = $this->db->query( "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'" )->fetchAll();
                array_walk( $tables, create_function( '&$item,$key', '$item = $item[0];' ) );

                foreach ( $tables as $tableName )
                {
                    $this->db->query( "DROP TABLE \"$tableName\"" );
                }
            }
            break;

            case 'oracle':
            {
                $tables = $this->db->query( "SELECT table_name FROM user_tables" )->fetchAll();
                array_walk( $tables, create_function( '&$item,$key', '$item = $item[0];' ) );

                foreach ( $tables as $tableName )
                {
                    $this->db->query( "DROP TABLE \"$tableName\"" );
                }

                $sequences = $this->db->query( "SELECT sequence_name FROM user_sequences" )->fetchAll();
                array_walk( $sequences, create_function( '&$item,$key', '$item = $item[0];' ) );

                foreach ( $sequences as $sequenceName )
                {
                    $this->db->query( "DROP SEQUENCE \"{$sequenceName}\"" );
                }
            }
            break;

            default:
            {
                $this->db->exec( 'DROP TABLE IF EXISTS workflow;' );
                $this->db->exec( 'DROP TABLE IF EXISTS node;' );
                $this->db->exec( 'DROP TABLE IF EXISTS node_connection;' );
                $this->db->exec( 'DROP TABLE IF EXISTS variable_handler;' );
                $this->db->exec( 'DROP TABLE IF EXISTS execution;' );
                $this->db->exec( 'DROP TABLE IF EXISTS execution_state;' );
            }
        }
    }
}
?>
