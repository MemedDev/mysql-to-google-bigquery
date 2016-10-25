<?php
namespace MysqlToGoogleBigQuery\Database;

use Doctrine\DBAL\Types\Type;

class Mysql
{
    protected $conn;

    public function getConnection()
    {
        if ($this->conn) {
            return $this->conn;
        }

        $config = new \Doctrine\DBAL\Configuration();

        $connParams = array(
            'dbname' => $_ENV['DB_DATABASE_NAME'],
            'user' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'host' => $_ENV['DB_HOST'],
            'driver' => $_ENV['DB_DRIVER'] ? $_ENV['DB_DRIVER'] : 'pdo_mysql',
            'charset'  => 'utf8',
        );

        $this->conn = \Doctrine\DBAL\DriverManager::getConnection($connParams, $config);

        // Replace the DateTime conversion
        Type::addType('bigquerydatetime', 'MysqlToGoogleBigQuery\Doctrine\BigQueryDateTimeType');
        Type::addType('bigquerydate', 'MysqlToGoogleBigQuery\Doctrine\BigQueryDateType');

        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('date', 'bigquerydate');
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('datetime', 'bigquerydatetime');
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('timestamp', 'bigquerydatetime');

        // Add support for MySQL 5.7 JSON type
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('json', 'text');
        $this->conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'text');

        return $this->conn;
    }

    public function getCountTableRows($tableName)
    {
        $mysqlQueryResult = $this->getConnection()->query('SELECT COUNT(*) AS count FROM ' . $tableName);

        while ($row = $mysqlQueryResult->fetch()) {
            return (int) $row['count'];
        }

        throw new \Exception('Mysql table ' . $tableName . ' not found');
    }

    public function getTableColumns($tableName)
    {
        $mysqlConnection = $this->getConnection();
        $mysqlPlatform = $mysqlConnection->getDatabasePlatform();
        $mysqlSchemaManager = $mysqlConnection->getSchemaManager();

        $mysqlTableDetails = $mysqlSchemaManager->listTableDetails($tableName);
        return $mysqlTableDetails->getColumns();
    }
}
