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
    protected $mysql;

    public function __construct(BigQuery $bigQuery, Mysql $mysql)
    {
        $this->bigQuery = $bigQuery;
        $this->mysql = $mysql;
    }

    protected function createTable($tableName)
    {
        $mysqlTableColumns = $this->mysql->getTableColumns($tableName);
        $this->bigQuery->createTable($tableName, $mysqlTableColumns);
    }

    /**
     * Executes the service
     */
    public function execute(string $tableName, bool $createTable, OutputInterface $output)
    {
        $mysqlCountTableRows = $this->mysql->getCountTableRows($tableName);
        $bigQueryCountTableRows = $this->bigQuery->getCountTableRows($tableName);

        if ($bigQueryCountTableRows === false) {
            if (! $createTable) {
                throw new \Exception('BigQuery table ' . $tableName . ' not found');
            }

            $this->createTable($tableName);
        }

        $rowsDiff = $mysqlCountTableRows - $bigQueryCountTableRows;

        // We don't need to sync
        if ($rowsDiff <= 0) {
            $output->writeln('<fg=green>Already synced!</>');
            return;
        }

        $maxRowsPerBatch = $_ENV['MAX_ROWS_PER_BATCH'] ? $_ENV['MAX_ROWS_PER_BATCH'] : 20000;
        $batches = ceil($rowsDiff / $maxRowsPerBatch);

        $output->writeln('<info>Sending ' . $batches . ' batches of ' . $maxRowsPerBatch . ' rows/batch</info>');
        $progress = new ProgressBar($output, $batches);

        for ($i = 0; $i < $batches; $i++) {
            $offset = $bigQueryCountTableRows + ($i * $maxRowsPerBatch);
            $this->sendBatch($tableName, $offset, $maxRowsPerBatch);
            $progress->advance();
        }

        $progress->finish();
        $output->writeln('<fg=green>Synced!</>');
    }

    protected function sendBatch(string $tableName, int $offset, int $limit)
    {
        $mysqlConnection = $this->mysql->getConnection();
        $mysqlPlatform = $mysqlConnection->getDatabasePlatform();
        $mysqlTableColumns = $this->mysql->getTableColumns($tableName);

        $jsonFilePath = __DIR__ . '/../../cache/' . $tableName;

        if (file_exists($jsonFilePath)) {
            unlink($jsonFilePath);
        }

        $json = fopen($jsonFilePath, 'a+');

        $mysqlQueryResult = $mysqlConnection->query('SELECT * FROM ' . $tableName . ' LIMIT ' . $offset . ', ' . $limit);

        while ($row = $mysqlQueryResult->fetch()) {
            foreach ($row as $key => $value) {
                // Convert to PHP values, BigQuery requires the correct types on JSON
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

            $string = json_encode($row);

            // Google BigQuery needs JSON new line delimited file
            // Each line of the file will be each MySQL row converted to JSON
            fwrite($json, json_encode($row) . PHP_EOL);
        }

        // Rewind to the beginning of the JSON file
        rewind($json);

        // Send JSON to BigQuery
        $success = $this->bigQuery->loadFromJson($json, $tableName);

        if (! $success) {
            throw new \Exception('BigQuery replied with errors: ' . implode(PHP_EOL, $this->bigQuery->getErrors()));
        }
    }
}
