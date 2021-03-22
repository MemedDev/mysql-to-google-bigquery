<?php
/**
 * Copyright 2016 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\BigQuery;

use Google\Cloud\BigQuery\Connection\ConnectionInterface;
use Google\Cloud\Exception\NotFoundException;
use Google\Cloud\Storage\StorageObject;

/**
 * [BigQuery Tables](https://cloud.google.com/bigquery/docs/tables) are a
 * standard two-dimensional table with individual records organized in rows, and
 * a data type assigned to each column (also called a field).
 */
class Table
{
    use JobConfigurationTrait;

    /**
     * @var ConnectionInterface $connection Represents a connection to BigQuery.
     */
    private $connection;

    /**
     * @var array The table's identity.
     */
    private $identity;

    /**
     * @var array The table's metadata
     */
    private $info;

    /**
     * @param ConnectionInterface $connection Represents a connection to
     *        BigQuery.
     * @param string $id The table's id.
     * @param string $datasetId The dataset's id.
     * @param string $projectId The project's id.
     * @param array $info [optional] The table's metadata.
     */
    public function __construct(ConnectionInterface $connection, $id, $datasetId, $projectId, array $info = [])
    {
        $this->connection = $connection;
        $this->info = $info;
        $this->identity = [
            'tableId' => $id,
            'datasetId' => $datasetId,
            'projectId' => $projectId
        ];
    }

    /**
     * Check whether or not the table exists.
     *
     * Example:
     * ```
     * $table->exists();
     * ```
     *
     * @return bool
     */
    public function exists()
    {
        try {
            $this->connection->getTable($this->identity + ['fields' => 'tableReference']);
        } catch (NotFoundException $ex) {
            return false;
        }

        return true;
    }

    /**
     * Delete the table.
     *
     * Example:
     * ```
     * $table->delete();
     * ```
     *
     * @see https://cloud.google.com/bigquery/docs/reference/v2/tables/delete Tables delete API documentation.
     *
     * @param array $options [optional] Configuration options.
     */
    public function delete(array $options = [])
    {
        $this->connection->deleteTable($options + $this->identity);
    }

    /**
     * Retrieves the rows associated with the table and merges them together
     * with the schema.
     *
     * Example:
     * ```
     * foreach ($table->rows() as $row) {
     *     echo $row['name'];
     * }
     * ```
     *
     * @see https://cloud.google.com/bigquery/docs/reference/v2/tabledata/list Tabledata list API Documentation.
     *
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type int $maxResults Maximum number of results to return.
     *     @type int $startIndex Zero-based index of the starting row.
     * }
     * @return \Generator<array>
     */
    public function rows(array $options = [])
    {
        $options['pageToken'] = null;
        $schema = $this->info()['schema']['fields'];

        do {
            $response = $this->connection->listTableData($options + $this->identity);

            if (!isset($response['rows'])) {
                return;
            }

            foreach ($response['rows'] as $rows) {
                $row = [];

                foreach ($rows['f'] as $key => $field) {
                    $row[$schema[$key]['name']] = $field['v'];
                }

                yield $row;
            }

            $options['pageToken'] = isset($response['nextPageToken']) ? $response['nextPageToken'] : null;
        } while ($options['pageToken']);
    }

    /**
     * Runs a copy job which copies this table to a specified destination table.
     *
     * Example:
     * ```
     * $sourceTable = $bigQuery->dataset('myDataset')->table('mySourceTable');
     * $destinationTable = $bigQuery->dataset('myDataset')->table('myDestinationTable');
     *
     * $sourceTable->copy($destinationTable);
     * ```
     *
     * @see https://cloud.google.com/bigquery/docs/reference/v2/jobs Jobs insert API Documentation.
     *
     * @param Table $destination The destination table.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type array $jobConfig Configuration settings for a copy job are
     *           outlined in the [API Docs for `configuration.copy`](https://goo.gl/m8dro9).
     *           If not provided default settings will be used.
     * }
     * @return Job
     */
    public function copy(Table $destination, array $options = [])
    {
        $config = $this->buildJobConfig(
            'copy',
            $this->identity['projectId'],
            [
                'destinationTable' => $destination->identity(),
                'sourceTable' => $this->identity
            ],
            $options
        );

        $response = $this->connection->insertJob($config);

        return new Job($this->connection, $response['jobReference']['jobId'], $this->identity['projectId'], $response);
    }

    /**
     * Runs an extract job which exports the contents of a table to Cloud
     * Storage.
     *
     * Example:
     * ```
     * $destinationObject = $storage->bucket('myBucket')->object('tableOutput');
     * $table->export($destinationObject);
     * ```
     *
     * @see https://cloud.google.com/bigquery/docs/reference/v2/jobs Jobs insert API Documentation.
     *
     * @param StorageObject $destination The destination object.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type array $jobConfig Configuration settings for an extract job are
     *           outlined in the [API Docs for `configuration.extract`](https://goo.gl/SQ9XAE).
     *           If not provided default settings will be used.
     * }
     * @return Job
     */
    public function export(StorageObject $destination, array $options = [])
    {
        $objIdentity = $destination->identity();
        $config = $this->buildJobConfig(
            'extract',
            $this->identity['projectId'],
            [
                'sourceTable' => $this->identity,
                'destinationUris' => ['gs://' . $objIdentity['bucket'] . '/' . $objIdentity['object']]
            ],
            $options
        );

        $response = $this->connection->insertJob($config);

        return new Job($this->connection, $response['jobReference']['jobId'], $this->identity['projectId'], $response);
    }

    /**
     * Runs a load job which loads the provided data into the table.
     *
     * Example:
     * ```
     * $table->load(fopen('/path/to/my/data.csv', 'r'));
     * ```
     *
     * @see https://cloud.google.com/bigquery/docs/reference/v2/jobs Jobs insert API Documentation.
     *
     * @param string|resource|StreamInterface $data The data to load.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type array $jobConfig Configuration settings for a load job are
     *           outlined in the [API Docs for `configuration.load`](https://goo.gl/j6jyHv).
     *           If not provided default settings will be used.
     * }
     * @return Job
     */
    public function load($data, array $options = [])
    {
        $response = null;
        $config = $this->buildJobConfig(
            'load',
            $this->identity['projectId'],
            ['destinationTable' => $this->identity],
            $options
        );

        if ($data) {
            $config['data'] = $data;
            $response = $this->connection->insertJobUpload($config)->upload();
        } else {
            $response = $this->connection->insertJob($config);
        }

        return new Job(
            $this->connection,
            $response['jobReference']['jobId'],
            $this->identity['projectId'],
            $response
        );
    }

    /**
     * Runs a load job which loads data from a file in a Storage bucket into the
     * table.
     *
     * Example:
     * ```
     * $object = $storage->bucket('myBucket')->object('important-data.csv');
     * $table->load($object);
     * ```
     *
     * @see https://cloud.google.com/bigquery/docs/reference/v2/jobs Jobs insert API Documentation.
     *
     * @param StorageObject $destination The object to load data from.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type array $jobConfig Configuration settings for a load job are
     *           outlined in the [API Docs for `configuration.load`](https://goo.gl/j6jyHv).
     *           If not provided default settings will be used.
     * }
     * @return Job
     */
    public function loadFromStorage(StorageObject $object, array $options = [])
    {
        $objIdentity = $object->identity();
        $options['jobConfig']['sourceUris'] = ['gs://' . $objIdentity['bucket'] . '/' . $objIdentity['object']];

        return $this->load(null, $options);
    }

    /**
     * Insert a record into the table without running a load job.
     *
     * Example:
     * ```
     * $row = [
     *     'city' => 'Detroit',
     *     'state' => 'MI'
     * ];
     *
     * $insertResponse = $table->insertRow($row, [
     *     'insertId' => '1'
     * ]);
     *
     * if (!$insertResponse->isSuccessful()) {
     *     $row = $insertResponse->failedRows()[0];
     *
     *     print_r($row['rowData']);
     *
     *     foreach ($row['errors'] as $error) {
     *         echo $error['reason'] . ': ' . $error['message'] . PHP_EOL;
     *     }
     * }
     * ```
     *
     * @codingStandardsIgnoreStart
     * @see https://cloud.google.com/bigquery/docs/reference/v2/tabledata/insertAll Tabledata insertAll API Documentation.
     * @see https://cloud.google.com/bigquery/streaming-data-into-bigquery Streaming data into BigQuery.
     *
     * @param array $row Key/value set of data matching the table's schema.
     * @param array $options [optional] {
     *     Please see
     *     {@see Google\Cloud\BigQuery\Table::insertRows()} for the
     *     other available configuration options.
     *
     *     @type string $insertId Used to
     *           [ensure data consistency](https://cloud.google.com/bigquery/streaming-data-into-bigquery#dataconsistency).
     * }
     * @return InsertResponse
     * @throws \InvalidArgumentException
     * @codingStandardsIgnoreEnd
     */
    public function insertRow(array $row, array $options = [])
    {
        $row = ['data' => $row];

        if (isset($options['insertId'])) {
            $row['insertId'] = $options['insertId'];
            unset($options['insertId']);
        }

        return $this->insertRows([$row], $options);
    }

    /**
     * Insert records into the table without running a load job.
     *
     * Example:
     * ```
     * $rows = [
     *     [
     *         'insertId' => '1',
     *         'data' => [
     *             'city' => 'Detroit',
     *             'state' => 'MI'
     *         ]
     *     ],
     *     [
     *         'insertId' => '2',
     *         'data' => [
     *             'city' => 'New York',
     *             'state' => 'NY'
     *         ]
     *     ]
     * ];
     *
     * $insertResponse = $table->insertRows($rows);
     *
     * if (!$insertResponse->isSuccessful()) {
     *     foreach ($insertResponse->failedRows() as $row) {
     *         print_r($row['rowData']);
     *
     *         foreach ($row['errors'] as $error) {
     *             echo $error['reason'] . ': ' . $error['message'] . PHP_EOL;
     *         }
     *     }
     * }
     * ```
     *
     * @codingStandardsIgnoreStart
     * @see https://cloud.google.com/bigquery/docs/reference/v2/tabledata/insertAll Tabledata insertAll API Documentation.
     * @see https://cloud.google.com/bigquery/streaming-data-into-bigquery Streaming data into BigQuery.
     *
     * @param array $rows The rows to insert. Each item in the array must
     *        contain a `data` key which is to hold a key/value array with data
     *        matching the schema of the table. Optionally, one may also provide
     *        an `insertId` key which will be used to
     *        [ensure data consistency](https://cloud.google.com/bigquery/streaming-data-into-bigquery#dataconsistency).
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type bool $skipInvalidRows Insert all valid rows of a request, even
     *           if invalid rows exist. The default value is `false`, which
     *           causes the entire request to fail if any invalid rows exist.
     *           **Defaults to** `false`.
     *     @type bool $ignoreUnknownValues Accept rows that contain values that
     *           do not match the schema. The unknown values are ignored.
     *           The default value is `false`, which treats unknown values as errors.
     *           **Defaults to** `false`.
     *     @type string $templateSuffix If specified, treats the destination
     *           table as a base template, and inserts the rows into an instance
     *           table named "{destination}{templateSuffix}". BigQuery will
     *           manage creation of the instance table, using the schema of the
     *           base template table. See
     *           [Creating tables automatically using template tables](https://cloud.google.com/bigquery/streaming-data-into-bigquery#template-tables)
     *           for considerations when working with templates tables.
     * }
     * @return InsertResponse
     * @throws \InvalidArgumentException
     * @codingStandardsIgnoreEnd
     */
    public function insertRows(array $rows, array $options = [])
    {
        foreach ($rows as $row) {
            if (!isset($row['data'])) {
                throw new \InvalidArgumentException('A row must have a data key.');
            }

            $row['json'] = $row['data'];
            unset($row['data']);
            $options['rows'][] = $row;
        }

        return new InsertResponse(
            $this->connection->insertAllTableData($this->identity + $options),
            $options['rows']
        );
    }

    /**
     * Retrieves the table's details. If no table data is cached a network
     * request will be made to retrieve it.
     *
     * Example:
     * ```
     * $info = $table->info();
     * echo $info['friendlyName'];
     * ```
     *
     * @see https://cloud.google.com/bigquery/docs/reference/v2/tables#resource Tables resource documentation.
     *
     * @param array $options [optional] Configuration options.
     * @return array
     */
    public function info(array $options = [])
    {
        if (!$this->info) {
            $this->reload($options);
        }

        return $this->info;
    }

    /**
     * Triggers a network request to reload the table's details.
     *
     * Example:
     * ```
     * $table->reload();
     * $info = $table->info();
     * echo $info['friendlyName'];
     * ```
     *
     * @see https://cloud.google.com/bigquery/docs/reference/v2/tables/get Tables get API documentation.
     *
     * @param array $options [optional] Configuration options.
     * @return array
     */
    public function reload(array $options = [])
    {
        return $this->info = $this->connection->getTable($options + $this->identity);
    }

    /**
     * Retrieves the table's ID.
     *
     * Example:
     * ```
     * echo $table->id();
     * ```
     *
     * @return string
     */
    public function id()
    {
        return $this->identity['tableId'];
    }

    /**
     * Retrieves the table's identity.
     *
     * An identity provides a description of a nested resource.
     *
     * Example:
     * ```
     * echo $table->identity()['projectId'];
     * ```
     *
     * @return array
     */
    public function identity()
    {
        return $this->identity;
    }
}
