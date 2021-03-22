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

namespace Google\Cloud\Logging;

use Google\Cloud\ClientTrait;
use Google\Cloud\Logging\Connection\ConnectionInterface;
use Google\Cloud\Logging\Connection\Rest;

/**
 * Google Stackdriver Logging client. Allows you to store, search, analyze,
 * monitor, and alert on log data and events from Google Cloud Platform and
 * Amazon Web Services. Find more information at
 * [Google Stackdriver Logging docs](https://cloud.google.com/logging/docs/).
 *
 * Example:
 * ```
 * use Google\Cloud\ServiceBuilder;
 *
 * $cloud = new ServiceBuilder();
 * $logging = $cloud->logging();
 * ```
 *
 * ```
 * // LoggingClient can be instantiated directly.
 * use Google\Cloud\Logging\LoggingClient;
 *
 * $logging = new LoggingClient();
 * ```
 */
class LoggingClient
{
    use ClientTrait;

    const FULL_CONTROL_SCOPE = 'https://www.googleapis.com/auth/logging.admin';
    const READ_ONLY_SCOPE = 'https://www.googleapis.com/auth/logging.read';
    const WRITE_ONLY_SCOPE = 'https://www.googleapis.com/auth/logging.write';

    /**
     * @var ConnectionInterface Represents a connection to Stackdriver Logging.
     */
    protected $connection;

    /**
     * @var string The formatted name used in API requests.
     */
    private $formattedProjectName;

    /**
     * Create a Logging client.
     *
     * @param array $config [optional] {
     *     Configuration options.
     *
     *     @type string $projectId The project ID from the Google Developer's
     *           Console.
     *     @type callable $authHttpHandler A handler used to deliver Psr7
     *           requests specifically for authentication.
     *     @type callable $httpHandler A handler used to deliver Psr7 requests.
     *     @type string $keyFile The contents of the service account
     *           credentials .json file retrieved from the Google Developers
     *           Console.
     *     @type string $keyFilePath The full path to your service account
     *           credentials .json file retrieved from the Google Developers
     *           Console.
     *     @type int $retries Number of retries for a failed request.
     *           **Defaults to** `3`.
     *     @type array $scopes Scopes to be used for the request.
     * }
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['scopes'])) {
            $config['scopes'] = [self::FULL_CONTROL_SCOPE];
        }

        $this->connection = new Rest($this->configureAuthentication($config));
        $this->formattedProjectName = "projects/$this->projectId";
    }

    /**
     * Create a sink.
     *
     * Example:
     * ```
     * $logging->createSink('my-sink', 'storage.googleapis.com/my-bucket');
     * ```
     *
     * @codingStandardsIgnoreStart
     * @see https://cloud.google.com/logging/docs/api/ref_v2beta1/rest/v2beta1/projects.sinks/create projects.sinks create API documentation.
     * @codingStandardsIgnoreEnd
     *
     * @param string $name The name of the sink.
     * @param string $destination The export destination. Please see
     *        [Exporting Logs With Sinks](https://cloud.google.com/logging/docs/api/tasks/exporting-logs#about_sinks)
     *        for more information and examples.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $filter An [advanced logs filter](https://cloud.google.com/logging/docs/view/advanced_filters).
     *     @type string $outputVersionFormat The log entry version to use for
     *           this sink's exported log entries. This version does not have
     *           to correspond to the version of the log entry when it was
     *           written to Stackdriver Logging.
     * }
     * @return Sink
     */
    public function createSink($name, $destination, array $options = [])
    {
        $response =  $this->connection->createSink($options + [
            'projectName' => $this->formattedProjectName,
            'name' => $name,
            'destination' => $destination
        ]);

        return new Sink($this->connection, $name, $this->projectId, $response);
    }

    /**
     * Lazily instantiates a sink. There are no network requests made at this
     * point. To see the operations that can be performed on a sink please see
     * {@see Google\Cloud\Logging\Sink}.
     *
     * Example:
     * ```
     * $sink = $logging->sink('my-sink');
     * echo $sink->name();
     * ```
     *
     * @param string $name The name of the sink.
     * @return Sink
     */
    public function sink($name)
    {
        return new Sink($this->connection, $name, $this->projectId);
    }

    /**
     * Fetches sinks associated with your project.
     *
     * Example:
     * ```
     * $sinks = $logging->sinks();
     *
     * foreach ($sinks as $sink) {
     *     echo $sink->name();
     * }
     * ```
     *
     * @codingStandardsIgnoreStart
     * @see https://cloud.google.com/logging/docs/api/ref_v2beta1/rest/v2beta1/projects.sinks/list projects.sinks list API documentation.
     * @codingStandardsIgnoreEnd
     *
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type int $pageSize The maximum number of results to return per request.
     * }
     * @return \Generator<Google\Cloud\Logging\Sink>
     */
    public function sinks(array $options = [])
    {
        $options['pageToken'] = null;

        do {
            $response = $this->connection->listSinks($options + ['projectName' => $this->formattedProjectName]);

            if (!isset($response['sinks'])) {
                return;
            }

            foreach ($response['sinks'] as $sink) {
                yield new Sink($this->connection, $sink['name'], $this->projectId, $sink);
            }

            $options['pageToken'] = isset($response['nextPageToken']) ? $response['nextPageToken'] : null;
        } while ($options['pageToken']);
    }

    /**
     * Create a metric.
     *
     * Example:
     * ```
     * $logging->createMetric('my-metric', 'logName = projects/my-project/logs/my-log');
     * ```
     *
     * @codingStandardsIgnoreStart
     * @see https://cloud.google.com/logging/docs/api/ref_v2beta1/rest/v2beta1/projects.metrics/create projects.metrics create API documentation.
     * @codingStandardsIgnoreEnd
     *
     * @param string $name The name of the metric.
     * @param string $filter An [advanced logs filter](https://cloud.google.com/logging/docs/view/advanced_filters).
     * @param array $options [optional] Configuration Options.
     * @return Metric
     */
    public function createMetric($name, $filter, array $options = [])
    {
        $response =  $this->connection->createMetric($options + [
            'projectName' => $this->formattedProjectName,
            'name' => $name,
            'filter' => $filter
        ]);

        return new Metric($this->connection, $name, $this->projectId, $response);
    }

    /**
     * Lazily instantiates a metric. There are no network requests made at this
     * point. To see the operations that can be performed on a metric please see
     * {@see Google\Cloud\Logging\Metric}.
     *
     * Example:
     * ```
     * $metric = $logging->metric('my-metric');
     * echo $metric->name();
     * ```
     *
     * @param string $name The name of the metric.
     * @return Metric
     */
    public function metric($name)
    {
        return new Metric($this->connection, $name, $this->projectId);
    }

    /**
     * Fetches metrics associated with your project.
     *
     * Example:
     * ```
     * $metrics = $logging->metrics();
     *
     * foreach ($metrics as $metric) {
     *     echo $metric->name();
     * }
     * ```
     *
     * @codingStandardsIgnoreStart
     * @see https://cloud.google.com/logging/docs/api/ref_v2beta1/rest/v2beta1/projects.metrics/list projects.metrics list API documentation.
     * @codingStandardsIgnoreEnd
     *
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type int $pageSize The maximum number of results to return per request.
     * }
     * @return \Generator<Google\Cloud\Logging\Metric>
     */
    public function metrics(array $options = [])
    {
        $options['pageToken'] = null;

        do {
            $response = $this->connection->listMetrics($options + ['projectName' => $this->formattedProjectName]);

            if (!isset($response['metrics'])) {
                return;
            }

            foreach ($response['metrics'] as $metric) {
                yield new Metric($this->connection, $metric['name'], $this->projectId, $metric);
            }

            $options['pageToken'] = isset($response['nextPageToken']) ? $response['nextPageToken'] : null;
        } while ($options['pageToken']);
    }

    /**
     * Fetches log entries.
     *
     * Example:
     * ```
     * $entries = $logging->entries();
     *
     * foreach ($entries as $entry) {
     *     echo $entry->info()['textPayload'];
     * }
     * ```
     *
     * ```
     * // Use an advanced logs filter to fetch only entries from a specified log.
     * $entries = $logging->entries([
     *     'filter' => 'logName = projects/my-project/logs/my-log'
     * ]);
     *
     * foreach ($entries as $entry) {
     *     echo $entry->info()['textPayload'];
     * }
     * ```
     *
     * @codingStandardsIgnoreStart
     * @see https://cloud.google.com/logging/docs/api/ref_v2beta1/rest/v2beta1/entries/list Entries list API documentation.
     * @codingStandardsIgnoreEnd
     *
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string[] $projectIds A list of projectIds to fetch entries from
     *           in addition to entries found in the project bound to this
     *           client.
     *     @type string $filter An [advanced logs filter](https://cloud.google.com/logging/docs/view/advanced_filters).
     *     @type string $orderBy How the results should be sorted. Presently,
     *           the only permitted values are `timestamp asc` and
     *           `timestamp desc`. **Defaults to** `"timestamp asc"`.
     *     @type int $pageSize The maximum number of results to return per
     *           request.
     * }
     * @return \Generator<Google\Cloud\Logging\Entry>
     */
    public function entries(array $options = [])
    {
        $options['pageToken'] = null;

        if (isset($options['projectIds'])) {
            $options['projectIds'] = array_merge([$this->projectId], $options['projectIds']);
        } else {
            $options['projectIds'] = [$this->projectId];
        }

        do {
            $response = $this->connection->listEntries($options);

            if (!isset($response['entries'])) {
                return;
            }

            foreach ($response['entries'] as $entry) {
                yield new Entry($entry);
            }

            $options['pageToken'] = isset($response['nextPageToken']) ? $response['nextPageToken'] : null;
        } while ($options['pageToken']);
    }

    /**
     * Fetches a logger which will write log entries to Stackdriver Logging and
     * implements the PSR-3 specification.
     *
     * Example:
     * ```
     * $psrLogger = $logging->psrLogger('my-log', [
     *         'type' => 'gcs_bucket',
     *         'labels' => [
     *             'bucket_name' => 'my-bucket'
     *         ]
     * ]);
     * $psrLogger->alert('an alert!');
     * ```
     *
     * @param string $name The name of the log to write entries to.
     * @param array $resource The
     *        [monitored resource](https://cloud.google.com/logging/docs/api/ref_v2beta1/rest/v2beta1/MonitoredResource)
     *        to associate log entries with.
     * @return PsrLogger
     */
    public function psrLogger($name, array $resource)
    {
        return new PsrLogger($this->logger($name), $resource);
    }

    /**
     * Fetches a logger which will write log entries to Stackdriver Logging.
     *
     * Example:
     * ```
     * $logger = $logging->logger('my-log');
     * $entry = $logger->entry('my-data', [
     *         'type' => 'gcs_bucket',
     *         'labels' => [
     *             'bucket_name' => 'my-bucket'
     *         ]
     * ]);
     * $logger->write($entry);
     * ```
     *
     * @param string $name The name of the log to write entries to.
     * @return Logger
     */
    public function logger($name)
    {
        return new Logger($this->connection, $name, $this->projectId);
    }
}
