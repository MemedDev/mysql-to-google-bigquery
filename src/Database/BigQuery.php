<?php
namespace MysqlToGoogleBigQuery\Database;

use Doctrine\DBAL\Types\Type;
use Google\Cloud\BigQuery\BigQueryClient;

class BigQuery
{
    protected $client;
    protected $errors;
    protected $tablesMetadata = [];

    public function createTable($tableName, $mysqlTableColumns)
    {
        $bigQueryColumns = [];

        // Valid types for BigQuery are:
        // STRING, BYTES, INTEGER, FLOAT, BOOLEAN,
        // TIMESTAMP, DATE, TIME, DATETIME
        foreach ($mysqlTableColumns as $name => $column) {
            switch ($column->getType()->getName()) {
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

    public function getCountTableRows($tableName)
    {
        $this->getTablesMetadata();

        if (! array_key_exists($tableName, $this->tablesMetadata)) {
            return false;
        }

        return $this->tablesMetadata[$tableName]['row_count'];
    }

    public function getClient()
    {
        if ($this->client) {
            return $this->client;
        }

        return $this->client = new BigQueryClient([
            'projectId' => $_ENV['BQ_PROJECT_ID'],
            'keyFile' => json_decode(file_get_contents(__DIR__ . '/../../' . $_ENV['BQ_KEY_FILE']), true),
            'scopes' => [BigQueryClient::SCOPE]
        ]);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getTablesMetadata()
    {
        $client = $this->getClient();
        $queryResults = $client->runQuery('SELECT * FROM ' . $_ENV['BQ_DATASET'] . '.__TABLES__;');

        foreach ($queryResults->rows() as $row) {
            $this->tablesMetadata[$row['table_id']] = $row;
        }

        return $this->tablesMetadata;
    }

    public function loadFromJson($file, $tableName)
    {
        $this->errors = [];

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

        $jobInfo = $job->info();

        while ($jobInfo['status']['state'] === 'RUNNING') {
            echo '.';
            $jobInfo = $job->reload();
            sleep(1);
        }

        if (array_key_exists('errors', $jobInfo['status'])
            && is_array($jobInfo['status']['errors'])
            && count($jobInfo['status']['errors']) > 0
        ) {
            foreach ($jobInfo['status']['errors'] as $error) {
                $errors[] = $error['message'];
            }

            return false;
        }

        return true;
    }
}
