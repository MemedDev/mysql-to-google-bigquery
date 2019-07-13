<?php
namespace MysqlToGoogleBigQuery\Services;

use Doctrine\DBAL\Types\Type;
use MysqlToGoogleBigQuery\Database\BigQuery;
use MysqlToGoogleBigQuery\Database\Mysql;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncService
{
    protected $bigQuery;
    protected $currentJob;
    protected $mysql;

    public function __construct(BigQuery $bigQuery, Mysql $mysql)
    {
        $this->bigQuery = $bigQuery;
        $this->mysql = $mysql;
    }

    /**
     * Create a BigQuery table using MySQL table schema
     * @param  string $databaseName         Database name
     * @param  string $tableName            Table name
     * @param  string $bigQueryTableName    BigQuery Table name
     */
    protected function createTable(string $databaseName, string $tableName, string $bigQueryTableName)
    {
        $mysqlTableColumns = $this->mysql->getTableColumns($databaseName, $tableName);
        $this->bigQuery->createTable($bigQueryTableName, $mysqlTableColumns);
    }

    /**
     * Execute the service, syncing MySQL and BigQuery table
     * @param  string          $databaseName          Database Name
     * @param  string          $tableName             Table Name
     * @param  string          $bigQueryTableName     Table Name
     * @param  bool            $createTable           If BigQuery table doesn't exists, create
     * @param  bool            $deleteTable           If BigQuery table exists, delete and recreate
     * @param  string          $orderColumn           Column to sort and compare result sets
     * @param  array           $ignoreColumns         Ignore columns from syncing
     * @param  OutputInterface $output                Output
     */
    public function execute(
        string $databaseName,
        string $tableName,
        string $bigQueryTableName,
        bool $createTable,
        bool $deleteTable,
        $orderColumn,
        array $ignoreColumns,
        OutputInterface $output, 
        bool $noData, 
        bool $unbuffered
    ) {
        if ($deleteTable) {
            // Delete the BigQuery Table before any operation
            if ($this->bigQuery->tableExists($bigQueryTableName)) {
                $this->bigQuery->deleteTable($bigQueryTableName);
            }

            // Create the table after deleting
            $createTable = true;
        }

        $output->writeln("\nchecking if we need a table"); 
        if (!$this->bigQuery->tableExists($bigQueryTableName)) {
            if (!$createTable) {
                throw new \Exception('BigQuery table ' . $bigQueryTableName . ' not found');
            }
            $output->writeln("\nCreating table: " . $tableName.""); 
            $this->createTable($databaseName, $tableName, $bigQueryTableName);
        }
        else 
        {
            $output->writeln("\nWe will not create a table"); 
        }

        if ( $noData )
        {
          $output->writeln("\nNo data specified");
          exit;
        }

        if (!$unbuffered && $orderColumn) {
            $output->writeln('<fg=green>Using order column "' . $orderColumn . '"</>');

            $mysqlMaxColumnValue = $this->mysql->getMaxColumnValue($databaseName, $tableName, $orderColumn);
            $bigQueryMaxColumnValue = $this->bigQuery->getMaxColumnValue($bigQueryTableName, $orderColumn);

            if (strcmp($mysqlMaxColumnValue, $bigQueryMaxColumnValue) === 0) {
                $output->writeln('<fg=green>Already synced!</>');
                return;
            }

            /**
             * Nothing to delete on a empty table
             */
            if ($bigQueryMaxColumnValue) {
                /**
                 * Delete latest values, there are no primary keys in bigQuery so we miss some values
                 */
                $output->writeln(
                    '<fg=yellow>Cleaning "' . $bigQueryTableName . '" for "' .
                    $orderColumn . '" = "' . $bigQueryMaxColumnValue . '"</>'
                );
                $this->bigQuery->deleteColumnValue($bigQueryTableName, $orderColumn, $bigQueryMaxColumnValue);

                /**
                 * Now get the latest "real" value
                 */
                $bigQueryMaxColumnValue = $this->bigQuery->getMaxColumnValue($bigQueryTableName, $orderColumn);
                $output->writeln('<fg=green>Syncing from "' . $bigQueryMaxColumnValue . '"</>');
            } else {
                $bigQueryMaxColumnValue = false;
            }
        } else {
            $bigQueryMaxColumnValue = false;
        }

        if ( !$unbuffered ) 
        {
            	$mysqlCountTableRows = $this->mysql->getCountTableRows($databaseName, $tableName, $orderColumn, $bigQueryMaxColumnValue);
        }
        $bigQueryCountTableRows = $orderColumn ? 0 : $this->bigQuery->getCountTableRows($bigQueryTableName, $orderColumn);
        
        if ( !$unbuffered ) 
        {
            $output->writeln('<fg=green>Comparing ['.$tableName.'] mysql: '. $mysqlCountTableRows . ' bigquery: ' . $bigQueryCountTableRows. ' </>');
            
            $rowsDiff = $mysqlCountTableRows - $bigQueryCountTableRows;
        
            // We don't need to sync
            if ($rowsDiff <= 0) {
                $output->writeln('<fg=green>Already synced!</>');
                return;
            } else {
                $output->writeln('<fg=green>Syncing ' . $rowsDiff . ' rows</>');
            }
        }

        $maxRowsPerBatch = (isset($_ENV['MAX_ROWS_PER_BATCH'])) ? $_ENV['MAX_ROWS_PER_BATCH'] : 600000;
        
        if ( $unbuffered ) 
        {
            $output->writeln('<fg=green>Starting unbuffered copy..</>');
            $this->sendBatchUnbuffered(
                    $databaseName,
                    $tableName,
                    $bigQueryTableName,
                    $orderColumn,
                    $ignoreColumns,
                    0,
                    $maxRowsPerBatch,
                    $bigQueryMaxColumnValue
                    );
                    $output->writeln('<fg=green>Synced!</>');
        }
        else 
        {
            $batches = ceil($rowsDiff / $maxRowsPerBatch);
            $output->writeln('<info>Sending ' . $batches . ' batches of ' . $maxRowsPerBatch . ' rows/batch</info>');
            $progress = new ProgressBar($output, $batches);
    
            for ($i = 0; $i < $batches; $i++) {
                $offset = $bigQueryCountTableRows + ($i * $maxRowsPerBatch);
                
                $this->sendBatch(
                    $databaseName,
                    $tableName,
                    $bigQueryTableName,
                    $orderColumn,
                    $ignoreColumns,
                    $offset,
                    $maxRowsPerBatch,
                    $bigQueryMaxColumnValue
                );
                $progress->advance();
            }
            $output->writeln('<fg=green>Synced!</>');
            $progress->finish();
        }
    }

    /**
     * Send a batch of data
     * @param  string $databaseName          Database name
     * @param  string $tableName             Table name
     * @param  string $bigQueryTableName     BigQuery Table name
     * @param  array  $ignoreColumns         Ignore columns from syncing
     * @param  int    $offset                Initial MySQL rows offset
     * @param  int    $limit                 MySQL rows limit, per batch
     */
    protected function sendBatch(
        string $databaseName,
        string $tableName,
        string $bigQueryTableName,
        $orderColumn = null,
        array $ignoreColumns,
        int $offset,
        int $limit,
        $orderColumnOffset
    ) {
        $mysqlConnection = $this->mysql->getConnection($databaseName);
        $mysqlPlatform = $mysqlConnection->getDatabasePlatform();
        $mysqlTableColumns = $this->mysql->getTableColumns($databaseName, $tableName);

        $jsonFilePath = ((isset($_ENV['CACHE_DIR'])) ? $_ENV['CACHE_DIR'] : __DIR__ . '/../../cache/') . $tableName;

        if (file_exists($jsonFilePath)) {
            unlink($jsonFilePath);
        }

        $json = fopen($jsonFilePath, 'a+');

        if ($orderColumn) {
            if ($orderColumnOffset) {
                $mysqlQueryResult = $mysqlConnection->query(
                    'SELECT * FROM `' . $tableName . '`' .
                    ' WHERE ' . $orderColumn . ' > "' . $orderColumnOffset . '"' .
                    ' ORDER BY ' . $orderColumn .
                    ' LIMIT '. $offset . ', ' . $limit
                );
            } else {
                $mysqlQueryResult = $mysqlConnection->query(
                    'SELECT * FROM `' . $tableName . '` ORDER BY ' . $orderColumn . ' LIMIT '. $offset . ', ' . $limit
                );
            }
        } else {
            $mysqlQueryResult = $mysqlConnection->query('SELECT * FROM `' . $tableName . '` LIMIT ' . $offset . ', ' . $limit);
        }

        while ($row = $mysqlQueryResult->fetch()) {
            $row = $this->processRow($mysqlTableColumns, $mysqlPlatform, $ignoreColumns, $row);
            $string = json_encode($row);

            // Google BigQuery needs JSON new line delimited file
            // Each line of the file will be each MySQL row converted to JSON
            fwrite($json, json_encode($row) . PHP_EOL);
        }

        // Rewind to the beginning of the JSON file
        rewind($json);

        // We have a job running, waits to send the next
        if ($this->currentJob) {
            $this->waitJob($this->currentJob);
        }

        // Send JSON to BigQuery
        $job = $this->bigQuery->loadFromJson($json, $bigQueryTableName, false);

        // This is the first job, waits for a first success to continue
        if (! $this->currentJob) {
            $this->waitJob($job);
        }

        $this->currentJob = $job;

        unlink($jsonFilePath);
    }
    
protected function processRow($mysqlTableColumns, $mysqlPlatform, $ignoreColumns, $row)
{
    foreach ($row as $key => $value) {
        // Ignore the column
        if (in_array($key, $ignoreColumns)) {
            unset($row[$key]);
            continue;
        }
    
        // Convert to PHP values, BigQuery requires the correct types on JSON, uppercase is not supported by
        // BigQuery - make keys lowercase
        $type = $mysqlTableColumns[$key]->getType();
    
        if ($type->getName() !== Type::STRING
            && $type->getName() !== Type::TEXT
        ) {
            $row[$key] = $type->convertToPhpValue($value, $mysqlPlatform);
        }
    
        if (is_string($row[$key])) {
            $row[$key] = mb_convert_encoding($row[$key], 'UTF-8', mb_detect_encoding($value));
        }
    }
    return $row; 
}


protected function sendBatchUnbuffered(
        string $databaseName,
        string $tableName,
        string $bigQueryTableName,
        $orderColumn = null,
        array $ignoreColumns,
        int $offset,
        int $limit,
        $orderColumnOffset
    ) {
        $mysqlConnection = $this->mysql->getConnection($databaseName); 
        $mysqlConnection->getWrappedConnection()->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        
        $mysqlPlatform = $mysqlConnection->getDatabasePlatform();
        $mysqlTableColumns = $this->mysql->getTableColumns($databaseName, $tableName);

        $jsonFilePath = ((isset($_ENV['CACHE_DIR'])) ? $_ENV['CACHE_DIR'] : __DIR__ . '/../../cache/') . $tableName;

        if (file_exists($jsonFilePath)) {
            unlink($jsonFilePath);
        }

        $json = fopen($jsonFilePath, 'a+');

        $mysqlQueryResult = $mysqlConnection->query('SELECT * FROM `' . $tableName . '`', MYSQLI_USE_RESULT);

        while ($row = $mysqlQueryResult->fetch()) {
            $row = $this->processRow($mysqlTableColumns, $mysqlPlatform, $ignoreColumns, $row);
            $string = json_encode($row);

            // Google BigQuery needs JSON new line delimited file
            // Each line of the file will be each MySQL row converted to JSON
            fwrite($json, json_encode($row) . PHP_EOL);
        }

        // Rewind to the beginning of the JSON file
        rewind($json);

        // We have a job running, waits to send the next
        if ($this->currentJob) {
            $this->waitJob($this->currentJob);
        }

        // Send JSON to BigQuery
        $job = $this->bigQuery->loadFromJson($json, $bigQueryTableName, true);

        // This is the first job, waits for a first success to continue
        if (! $this->currentJob) {
            $this->waitJob($job);
        }

        $this->currentJob = $job;
        unlink($jsonFilePath);
    }


    /**
     * Wait for a BigQuery Job
     * @param  Google\Cloud\BigQuery\Job $job BigQuery Job
     */
    protected function waitJob($job)
    {
        $errors = [];
        $jobInfo = $job->info();

        while ($jobInfo['status']['state'] === 'RUNNING') {
            echo '.';
            $jobInfo = $job->reload();
            // Wait a second to retry
            sleep(1);
        }

        if (array_key_exists('errors', $jobInfo['status'])
            && is_array($jobInfo['status']['errors'])
            && count($jobInfo['status']['errors']) > 0
        ) {
            foreach ($jobInfo['status']['errors'] as $error) {
                $errors[] = $error['message'];
            }

            throw new \Exception('BigQuery replied with errors: ' . PHP_EOL . implode(PHP_EOL, $errors));
        }
    }
}
