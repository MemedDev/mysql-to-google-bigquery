<?php
namespace MysqlToGoogleBigQuery;

use Google\Cloud\BigQuery\BigQueryClient;

class BigQuery
{
    protected $client;
    protected $errors;
    protected $tablesMetadata = [];

    public function getCountTableRows($tableName)
    {
        $this->getTablesMetadata();

        if (! array_key_exists($tableName, $this->tablesMetadata)) {
            throw new \Exception('BigQuery table ' . $tableName . ' not found');
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
            'keyFile' => json_decode(file_get_contents(__DIR__ . '/../' . $_ENV['BQ_KEY_FILE']), true),
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
