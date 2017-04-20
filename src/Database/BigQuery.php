<?php
namespace MysqlToGoogleBigQuery\Database;

use Doctrine\DBAL\Types\Type;
use Google\Cloud\BigQuery\BigQueryClient;

class BigQuery
{
    protected $client;
    protected $tablesMetadata = [];

    /**
     * Create a BigQuery Table based on MySQL Table columns
     * @param  string $tableName           Table Name
     * @param  array  $mysqlTableColumns   Array of Doctrine\DBAL\Schema\Column
     * @return Google\Cloud\BigQuery\Table Table object
     */
    public function createTable($tableName, $mysqlTableColumns)
    {
        $bigQueryColumns = [];

        // Valid types for BigQuery are:
        // STRING, BYTES, INTEGER, FLOAT, BOOLEAN,
        // TIMESTAMP, DATE, TIME, DATETIME
        foreach ($mysqlTableColumns as $name => $column) {
            switch ($column->getType()->getName()) {
                case 'bigquerydate':
                    $type = 'DATE';
                    break;

                case 'bigquerydatetime':
                    $type = 'DATETIME';
                    break;

                case Type::BIGINT:
                    $type = 'INTEGER';
                    break;

                case Type::BOOLEAN:
                    $type = 'BOOLEAN';
                    break;

                case Type::DATE:
                    $type = 'DATETIME';
                    break;

                case Type::DATETIME:
                    $type = 'DATETIME';
                    break;

                case Type::DECIMAL:
                    $type = 'FLOAT';
                    break;

                case Type::FLOAT:
                    $type = 'FLOAT';
                    break;

                case Type::INTEGER:
                    $type = 'INTEGER';
                    break;

                case Type::SMALLINT:
                    $type = 'INTEGER';
                    break;

                case Type::TIME:
                    $type = 'TIME';
                    break;

                default:
                    $type = 'STRING';
                    break;
            }

            $bigQueryColumns[] = [
                'name' => $name,
                'type' => $type
            ];
        }

        $client = $this->getClient();
        $dataset = $client->dataset($_ENV['BQ_DATASET']);

        return $dataset->createTable($tableName, [
            'schema' => [
                'fields' => $bigQueryColumns
            ],
        ]);
    }

    /**
     * Delete a BigQuery Table
     * @param  string $tableName Table Name
     */
    public function deleteTable(string $tableName)
    {
        $client = $this->getClient();
        $dataset = $client->dataset($_ENV['BQ_DATASET']);
        $dataset->table($tableName)->delete();
    }

    /**
     * Get the number of rows on a table
     * @param  string $tableName Table name
     * @return int|bool          false if table doesn't exists, or the number of rows
     */
    public function getCountTableRows(string $tableName)
    {
        $this->getTablesMetadata();

        if (! array_key_exists($tableName, $this->tablesMetadata)) {
            return false;
        }

        return $this->tablesMetadata[$tableName]['row_count'];
    }

    /**
     * Get the maximum value of a column
     * @param  string $tableName Table name
     * @param  string $columnName   Column name
     * @return string               Max value
     */
    public function getMaxColumnValue(string $tableName, string $columnName)
    {
        $client = $this->getClient();

        $result = $client->runQuery(
            'SELECT MAX([' . $columnName . ']) AS columnMax FROM [' . $_ENV['BQ_DATASET'] . '.' .  $tableName . ']'
        );

        $isComplete = $result->isComplete();
        while (!$isComplete) {
            sleep(1);
            $result->reload();
            $isComplete = $result->isComplete();
        }

        foreach ($result->rows() as $row) {
            return $row['columnMax'];
        }

        return false;
    }

    /**
     * Delete all values of a column
     * @param  string $tableName Table name
     * @param  string $columnName   Column name
     * @param  string $columnValue  Value to be deleted
     * @return string               Result
     */
    public function deleteColumnValue(string $tableName, string $columnName, string $columnValue)
    {
        $client = $this->getClient();

        // Non numeric values needs ""
        if (! is_numeric($columnValue)) {
            $columnValue = '"' . $columnValue . '"';
        }

        $result = $client->runQuery(
            'DELETE FROM `' . $_ENV['BQ_DATASET'] . '.' .  $tableName . '`' .
            ' WHERE `' . $columnName .'` = ' . $columnValue,
            ['useLegacySql' => false]
        );

        $isComplete = $result->isComplete();
        while (!$isComplete) {
            sleep(1);
            $result->reload();
            $isComplete = $result->isComplete();
        }

        return $result;
    }

    /**
     * Get BigQuery API Client
     * @return BigQueryClient BigQuery API Client
     */
    public function getClient()
    {
        if ($this->client) {
            return $this->client;
        }

        $keyFilePath = $_ENV['BQ_KEY_FILE'];

        // Support relative and absolute path
        if ($keyFilePath[0] !== '/') {
            $keyFilePath = getcwd() . '/' . $keyFilePath;
        }

        if (! file_exists($keyFilePath)) {
            throw new \Exception('Google Service Account JSON Key File not found', 1);
        }

        return $this->client = new BigQueryClient([
            'projectId' => $_ENV['BQ_PROJECT_ID'],
            'keyFile' => json_decode(file_get_contents($keyFilePath), true),
            'scopes' => [BigQueryClient::SCOPE]
        ]);
    }

    /**
     * Get table metadata
     * See https://cloud.google.com/bigquery/querying-data#metadata_about_tables_in_a_dataset
     *
     * @return array Array with all dataset tables information
     */
    public function getTablesMetadata()
    {
        $client = $this->getClient();
        $queryResults = $client->runQuery('SELECT * FROM ' . $_ENV['BQ_DATASET'] . '.__TABLES__;', [
            'useQueryCache' => false
        ]);

        foreach ($queryResults->rows() as $row) {
            $this->tablesMetadata[$row['table_id']] = $row;
        }

        return $this->tablesMetadata;
    }

    /**
     * Load data to BigQuery reading it from JSON NEWLINE DELIMITED File
     * @param  resource|string $file                Resource or String (path) of JSON file
     * @param  string          $tableName           Table Name
     * @return Google\Cloud\BigQuery\Job            BigQuery Data Load Job
     */
    public function loadFromJson($file, $tableName)
    {
        $client = $this->getClient();
        $dataset = $client->dataset($_ENV['BQ_DATASET']);
        $table = $dataset->table($tableName);

        $job = $table->load(
            $file,
            [
                'jobConfig' => [
                    'sourceFormat' => 'NEWLINE_DELIMITED_JSON'
                ]
            ]
        );

        return $job;
    }

    /**
     * Check if a BigQuery table exists
     * @param  string $tableName Table name
     * @return bool              True if table exists
     */
    public function tableExists(string $tableName)
    {
        $client = $this->getClient();
        $dataset = $client->dataset($_ENV['BQ_DATASET']);

        return $dataset->table($tableName)->exists();
    }
}
