<?php
/**
 * Copyright 2015 Google Inc. All Rights Reserved.
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

namespace Google\Cloud\Storage\Connection;

use Google\Cloud\RequestBuilder;
use Google\Cloud\RequestWrapper;
use Google\Cloud\RestTrait;
use Google\Cloud\Storage\Connection\ConnectionInterface;
use Google\Cloud\Upload\AbstractUploader;
use Google\Cloud\Upload\MultipartUploader;
use Google\Cloud\Upload\ResumableUploader;
use Google\Cloud\UriTrait;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;

/**
 * Implementation of the
 * [Google Cloud Storage JSON API](https://cloud.google.com/storage/docs/json_api/).
 */
class Rest implements ConnectionInterface
{
    use RestTrait;
    use UriTrait;

    const BASE_URI = 'https://www.googleapis.com/storage/v1/';
    const UPLOAD_URI = 'https://www.googleapis.com/upload/storage/v1/b/{bucket}/o{?query*}';
    const DOWNLOAD_URI = 'https://storage.googleapis.com/{bucket}/{object}{?query*}';

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->setRequestWrapper(new RequestWrapper($config));
        $this->setRequestBuilder(new RequestBuilder(
            __DIR__ . '/ServiceDefinition/storage-v1.json',
            self::BASE_URI
        ));
    }

    /**
     * @param array $args
     */
    public function deleteAcl(array $args = [])
    {
        return $this->send($args['type'], 'delete', $args);
    }

    /**
     * @param array $args
     */
    public function getAcl(array $args = [])
    {
        return $this->send($args['type'], 'get', $args);
    }

    /**
     * @param array $args
     */
    public function listAcl(array $args = [])
    {
        return $this->send($args['type'], 'list', $args);
    }

    /**
     * @param array $args
     */
    public function insertAcl(array $args = [])
    {
        return $this->send($args['type'], 'insert', $args);
    }

    /**
     * @param array $args
     */
    public function patchAcl(array $args = [])
    {
        return $this->send($args['type'], 'patch', $args);
    }

    /**
     * @param array $args
     */
    public function deleteBucket(array $args = [])
    {
        return $this->send('buckets', 'delete', $args);
    }

    /**
     * @param array $args
     */
    public function getBucket(array $args = [])
    {
        return $this->send('buckets', 'get', $args);
    }

    /**
     * @param array $args
     */
    public function listBuckets(array $args = [])
    {
        return $this->send('buckets', 'list', $args);
    }

    /**
     * @param array $args
     */
    public function insertBucket(array $args = [])
    {
        return $this->send('buckets', 'insert', $args);
    }

    /**
     * @param array $args
     */
    public function patchBucket(array $args = [])
    {
        return $this->send('buckets', 'patch', $args);
    }

    /**
     * @param array $args
     */
    public function deleteObject(array $args = [])
    {
        return $this->send('objects', 'delete', $args);
    }

    /**
     * @param array $args
     */
    public function copyObject(array $args = [])
    {
        return $this->send('objects', 'copy', $args);
    }

    /**
     * @param array $args
     */
    public function rewriteObject(array $args = [])
    {
        return $this->send('objects', 'rewrite', $args);
    }

    /**
     * @param array $args
     */
    public function composeObject(array $args = [])
    {
        return $this->send('objects', 'compose', $args);
    }

    /**
     * @param array $args
     */
    public function getObject(array $args = [])
    {
        return $this->send('objects', 'get', $args);
    }

    /**
     * @param array $args
     */
    public function listObjects(array $args = [])
    {
        return $this->send('objects', 'list', $args);
    }

    /**
     * @param array $args
     */
    public function patchObject(array $args = [])
    {
        return $this->send('objects', 'patch', $args);
    }

    /**
     * @param array $args
     */
    public function downloadObject(array $args = [])
    {
        $args += [
            'bucket' => null,
            'object' => null,
            'generation' => null
        ];

        $requestOptions = array_intersect_key($args, [
            'httpOptions' => null,
            'retries' => null
        ]);

        $uri = $this->expandUri(self::DOWNLOAD_URI, [
            'bucket' => $args['bucket'],
            'object' => $args['object'],
            'query' => [
                'generation' => $args['generation'],
                'alt' => 'media'
            ]
        ]);

        return $this->requestWrapper->send(
            new Request('GET', Psr7\uri_for($uri)),
            $requestOptions
        )->getBody();
    }

    /**
     * @param array $args
     */
    public function insertObject(array $args = [])
    {
        $args = $this->resolveUploadOptions($args);
        $isResumable = $args['resumable'];
        $uploadType = $isResumable
            ? AbstractUploader::UPLOAD_TYPE_RESUMABLE
            : AbstractUploader::UPLOAD_TYPE_MULTIPART;

        $uriParams = [
            'bucket' => $args['bucket'],
            'query' => [
                'predefinedAcl' => $args['predefinedAcl'],
                'uploadType' => $uploadType
            ]
        ];

        if ($isResumable) {
            return new ResumableUploader(
                $this->requestWrapper,
                $args['data'],
                $this->expandUri(self::UPLOAD_URI, $uriParams),
                $args['uploaderOptions']
            );
        }

        return new MultipartUploader(
            $this->requestWrapper,
            $args['data'],
            $this->expandUri(self::UPLOAD_URI, $uriParams),
            $args['uploaderOptions']
        );
    }

    /**
     * @param array $args
     */
    private function resolveUploadOptions(array $args)
    {
        $args += [
            'bucket' => null,
            'name' => null,
            'validate' => true,
            'resumable' => null,
            'predefinedAcl' => 'private',
            'metadata' => []
        ];

        $args['data'] = Psr7\stream_for($args['data']);

        if ($args['resumable'] === null) {
            $args['resumable'] = $args['data']->getSize() > AbstractUploader::RESUMABLE_LIMIT;
        }

        if (!$args['name']) {
            $args['name'] = basename($args['data']->getMetadata('uri'));
        }

        // @todo add support for rolling hash
        if ($args['validate'] && !isset($args['metadata']['md5Hash'])) {
            $args['metadata']['md5Hash'] = base64_encode(Psr7\hash($args['data'], 'md5', true));
        }

        $args['metadata']['name'] = $args['name'];
        unset($args['name']);
        $args['contentType'] = isset($args['metadata']['contentType'])
            ? $args['metadata']['contentType']
            : Psr7\mimetype_from_filename($args['metadata']['name']);

        $uploaderOptionKeys = [
            'httpOptions',
            'retries',
            'chunkSize',
            'contentType',
            'metadata'
        ];

        $args['uploaderOptions'] = array_intersect_key($args, array_flip($uploaderOptionKeys));
        $args = array_diff_key($args, array_flip($uploaderOptionKeys));

        return $args;
    }
}
