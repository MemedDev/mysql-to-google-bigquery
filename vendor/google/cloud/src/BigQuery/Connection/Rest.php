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

namespace Google\Cloud\BigQuery\Connection;

use Google\Cloud\BigQuery\Connection\ConnectionInterface;
use Google\Cloud\RequestBuilder;
use Google\Cloud\RequestWrapper;
use Google\Cloud\RestTrait;
use Google\Cloud\Upload\AbstractUploader;
use Google\Cloud\Upload\MultipartUploader;
use Google\Cloud\Upload\ResumableUploader;
use Google\Cloud\UriTrait;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;

/**
 * Implementation of the
 * [Google BigQuery JSON API](https://cloud.google.com/bigquery/docs/reference/v2/).
 */
class Rest implements ConnectionInterface
{
    use RestTrait;
    use UriTrait;

    const BASE_URI = 'https://www.googleapis.com/bigquery/v2/';
    const UPLOAD_URI = 'https://www.googleapis.com/upload/bigquery/v2/projects/{projectId}/jobs';

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setRequestWrapper(new RequestWrapper($config));
        $this->setRequestBuilder(new RequestBuilder(
            __DIR__ . '/ServiceDefinition/bigquery-v2.json',
            self::BASE_URI
        ));
    }

    /**
     * @param array $args
     * @return array
     */
    public function deleteDataset(array $args = [])
    {
        return $this->send('datasets', 'delete', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function patchDataset(array $args = [])
    {
        return $this->send('datasets', 'patch', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function getDataset(array $args = [])
    {
        return $this->send('datasets', 'get', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function listDatasets(array $args = [])
    {
        return $this->send('datasets', 'list', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function insertDataset(array $args = [])
    {
        return $this->send('datasets', 'insert', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function deleteTable(array $args = [])
    {
        return $this->send('tables', 'delete', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function getTable(array $args = [])
    {
        return $this->send('tables', 'get', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function insertTable(array $args = [])
    {
        return $this->send('tables', 'insert', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function listTables(array $args = [])
    {
        return $this->send('tables', 'list', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function listTableData(array $args = [])
    {
        return $this->send('tabledata', 'list', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function insertAllTableData(array $args = [])
    {
        return $this->send('tabledata', 'insertAll', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function query(array $args = [])
    {
        return $this->send('jobs', 'query', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function getQueryResults(array $args = [])
    {
        return $this->send('jobs', 'getQueryResults', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function getJob(array $args = [])
    {
        return $this->send('jobs', 'get', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function listJobs(array $args = [])
    {
        return $this->send('jobs', 'list', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function cancelJob(array $args = [])
    {
        return $this->send('jobs', 'cancel', $args);
    }

    /**
     * @param array $args
     * @return array
     */
    public function insertJob(array $args = [])
    {
        return $this->send('jobs', 'insert', $args);
    }

    /**
     * @todo look into abstracting the upload logic here and in the storage REST implementation
     * @param array $args
     * @return AbstractUploader
     */
    public function insertJobUpload(array $args = [])
    {
        $args = $this->resolveUploadOptions($args);

        return new MultipartUploader(
            $this->requestWrapper,
            $args['data'],
            $this->expandUri(self::UPLOAD_URI, ['projectId' => $args['projectId']]),
            $args['uploaderOptions']
        );
    }

    /**
     * @param array $args
     * @return array
     */
    private function resolveUploadOptions(array $args)
    {
        $args += [
            'projectId' => null,
            'data' => null,
            'configuration' => []
        ];

        $args['data'] = Psr7\stream_for($args['data']);
        $args['metadata']['configuration'] = $args['configuration'];
        unset($args['configuration']);

        $uploaderOptionKeys = [
            'httpOptions',
            'retries',
            'metadata'
        ];

        $args['uploaderOptions'] = array_intersect_key($args, array_flip($uploaderOptionKeys));
        $args = array_diff_key($args, array_flip($uploaderOptionKeys));

        return $args;
    }
}
