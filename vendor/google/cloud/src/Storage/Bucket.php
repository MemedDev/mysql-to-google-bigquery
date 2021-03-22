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

namespace Google\Cloud\Storage;

use Google\Cloud\Exception\NotFoundException;
use Google\Cloud\Storage\Connection\ConnectionInterface;
use Google\Cloud\Upload\ResumableUploader;
use GuzzleHttp\Psr7;
use Psr\Http\Message\StreamInterface;

/**
 * Buckets are the basic containers that hold your data. Everything that you
 * store in Google Cloud Storage must be contained in a bucket.
 */
class Bucket
{
    use EncryptionTrait;

    /**
     * @var Acl ACL for the bucket.
     */
    private $acl;

    /**
     * @var ConnectionInterface Represents a connection to Cloud Storage.
     */
    private $connection;

    /**
     * @var Acl Default ACL for objects created within the bucket.
     */
    private $defaultAcl;

    /**
     * @var array The bucket's identity.
     */
    private $identity;

    /**
     * @var array The bucket's metadata.
     */
    private $info;

    /**
     * @param ConnectionInterface $connection Represents a connection to Cloud
     *        Storage.
     * @param string $name The bucket's name.
     * @param array $info [optional] The bucket's metadata.
     */
    public function __construct(ConnectionInterface $connection, $name, array $info = null)
    {
        $this->connection = $connection;
        $this->identity = ['bucket' => $name];
        $this->info = $info;
        $this->acl = new Acl($this->connection, 'bucketAccessControls', $this->identity);
        $this->defaultAcl = new Acl($this->connection, 'defaultObjectAccessControls', $this->identity);
    }

    /**
     * Configure ACL for this bucket.
     *
     * Example:
     * ```
     * use Google\Cloud\Storage\Acl;
     *
     * $acl = $bucket->acl();
     * $acl->add('allAuthenticatedUsers', Acl::ROLE_READER);
     * ```
     *
     * @see https://cloud.google.com/storage/docs/access-control More about Access Control Lists
     *
     * @return Acl An ACL instance configured to handle the bucket's access
     *         control policies.
     */
    public function acl()
    {
        return $this->acl;
    }

    /**
     * Configure default object ACL for this bucket.
     *
     * Example:
     * ```
     * use Google\Cloud\Storage\Acl;
     *
     * $acl = $bucket->defaultAcl();
     * $acl->add('allAuthenticatedUsers', Acl::ROLE_READER);
     * ```
     *
     * @see https://cloud.google.com/storage/docs/access-control More about Access Control Lists
     * @return Acl An ACL instance configured to handle the bucket's default
     *         object access control policies.
     */
    public function defaultAcl()
    {
        return $this->defaultAcl;
    }

    /**
     * Check whether or not the bucket exists.
     *
     * Example:
     * ```
     * $bucket->exists();
     * ```
     *
     * @return bool
     */
    public function exists()
    {
        try {
            $this->connection->getBucket($this->identity + ['fields' => 'name']);
        } catch (NotFoundException $ex) {
            return false;
        }

        return true;
    }

    /**
     * Upload your data in a simple fashion. Uploads will default to being
     * resumable if the file size is greater than 5mb.
     *
     * Example:
     * ```
     * $bucket->upload(
     *     fopen(__DIR__ . '/image.jpg', 'r')
     * );
     * ```
     *
     * ```
     * // Upload an object in a resumable fashion while setting a new name for
     * // the object and including the content language.
     * $options = [
     *     'resumable' => true,
     *     'name' => '/images/new-name.jpg',
     *     'metadata' => [
     *         'contentLanguage' => 'en'
     *     ]
     * ];
     *
     * $bucket->upload(
     *     fopen(__DIR__ . '/image.jpg', 'r'),
     *     $options
     * );
     * ```
     *
     * ```
     * // Upload an object with a customer-supplied encryption key.
     * $key = openssl_random_pseudo_bytes(32); // Make sure to remember your key.
     * $options = [
     *     'encryptionKey' => $key
     *     'encryptionKeySHA256' => hash('SHA256', $key, true)
     * ];
     *
     * $bucket->upload(
     *     fopen(__DIR__ . '/image.jpg', 'r'),
     *     $options
     * );
     * ```
     *
     * @see https://cloud.google.com/storage/docs/json_api/v1/how-tos/upload#resumable Learn more about resumable
     * uploads.
     * @see https://cloud.google.com/storage/docs/json_api/v1/objects/insert Objects insert API documentation.
     * @see https://cloud.google.com/storage/docs/encryption#customer-supplied Customer-supplied encryption keys.
     *
     * @param string|resource|StreamInterface $data The data to be uploaded.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $name The name of the destination.
     *     @type bool $resumable Indicates whether or not the upload will be
     *           performed in a resumable fashion.
     *     @type bool $validate Indicates whether or not validation will be
     *           applied using md5 hashing functionality. If true and the
     *           calculated hash does not match that of the upstream server the
     *           upload will be rejected.
     *     @type int $chunkSize If provided the upload will be done in chunks.
     *           The size must be in multiples of 262144 bytes. With chunking
     *           you have increased reliability at the risk of higher overhead.
     *           It is recommended to not use chunking.
     *     @type string $predefinedAcl Predefined ACL to apply to the object.
     *           Acceptable values include,
     *           `"authenticatedRead"`, `"bucketOwnerFullControl"`,
     *           `"bucketOwnerRead"`, `"private"`, `"projectPrivate"`, and
     *           `"publicRead"`. **Defaults to** `"private"`.
     *     @type array $metadata The available options for metadata are outlined
     *           at the [JSON API docs](https://cloud.google.com/storage/docs/json_api/v1/objects/insert#request-body).
     *     @type string $encryptionKey An AES-256 customer-supplied encryption
     *           key. If provided one must also include an `encryptionKeySHA256`.
     *     @type string $encryptionKeySHA256 The SHA256 hash of the
     *           customer-supplied encryption key. If provided one must also
     *           include an `encryptionKey`.
     * }
     * @return StorageObject
     * @throws \InvalidArgumentException
     */
    public function upload($data, array $options = [])
    {
        if (is_string($data) && !isset($options['name'])) {
            throw new \InvalidArgumentException('A name is required when data is of type string.');
        }

        $encryptionKey = isset($options['encryptionKey']) ? $options['encryptionKey'] : null;
        $encryptionKeySHA256 = isset($options['encryptionKeySHA256']) ? $options['encryptionKeySHA256'] : null;

        $response = $this->connection->insertObject(
            $this->formatEncryptionHeaders($options) + [
                'bucket' => $this->identity['bucket'],
                'data' => $data
            ]
        )->upload();

        return new StorageObject(
            $this->connection,
            $response['name'],
            $this->identity['bucket'],
            $response['generation'],
            $response,
            $encryptionKey,
            $encryptionKeySHA256
        );
    }

    /**
     * Get a resumable uploader which can provide greater control over the
     * upload process. This is recommended when dealing with large files where
     * reliability is key.
     *
     * Example:
     * ```
     * $uploader = $bucket->getResumableUploader(
     *     fopen('image.jpg', 'r')
     * );
     *
     * try {
     *     $uploader->upload();
     * } catch (GoogleException $ex) {
     *     $resumeUri = $uploader->getResumeUri();
     *     $uploader->resume($resumeUri);
     * }
     * ```
     *
     * @see https://cloud.google.com/storage/docs/json_api/v1/how-tos/upload#resumable Learn more about resumable
     * uploads.
     * @see https://cloud.google.com/storage/docs/json_api/v1/objects/insert Objects insert API documentation.
     *
     * @param string|resource|StreamInterface $data The data to be uploaded.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $name The name of the destination.
     *     @type bool $validate Indicates whether or not validation will be
     *           applied using md5 hashing functionality. If true and the
     *           calculated hash does not match that of the upstream server the
     *           upload will be rejected.
     *     @type int $chunkSize If provided the upload will be done in chunks.
     *           The size must be in multiples of 262144 bytes. With chunking
     *           you have increased reliability at the risk of higher overhead.
     *           It is recommended to not use chunking.
     *     @type string $predefinedAcl Predefined ACL to apply to the object.
     *           Acceptable values include `"authenticatedRead`",
     *           `"bucketOwnerFullControl`", `"bucketOwnerRead`", `"private`",
     *           `"projectPrivate`", and `"publicRead"`. **Defaults to**
     *           `"private"`.
     *     @type array $metadata The available options for metadata are outlined
     *           at the [JSON API docs](https://cloud.google.com/storage/docs/json_api/v1/objects/insert#request-body).
     *     @type string $encryptionKey An AES-256 customer-supplied encryption
     *           key. If provided one must also include an `encryptionKeySHA256`.
     *     @type string $encryptionKeySHA256 The SHA256 hash of the
     *           customer-supplied encryption key. If provided one must also
     *           include an `encryptionKey`.
     * }
     * @return ResumableUploader
     * @throws \InvalidArgumentException
     */
    public function getResumableUploader($data, array $options = [])
    {
        if (is_string($data) && !isset($options['name'])) {
            throw new \InvalidArgumentException('A name is required when data is of type string.');
        }

        return $this->connection->insertObject(
            $this->formatEncryptionHeaders($options) + [
                'bucket' => $this->identity['bucket'],
                'data' => $data,
                'resumable' => true
            ]
        );
    }

    /**
     * Lazily instantiates an object. There are no network requests made at this
     * point. To see the operations that can be performed on an object please
     * see {@see Google\Cloud\Storage\StorageObject}.
     *
     * Example:
     * ```
     * $object = $bucket->object('file.txt');
     * ```
     *
     * @param string $name The name of the object to request.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $generation Request a specific revision of the object.
     *     @type string $encryptionKey An AES-256 customer-supplied encryption
     *           key. It will be neccesary to provide this when a key was used
     *           during the object's creation. If provided one must also include
     *           an `encryptionKeySHA256`.
     *     @type string $encryptionKeySHA256 The SHA256 hash of the
     *           customer-supplied encryption key. It will be neccesary to
     *           provide this when a key was used during the object's creation.
     *           If provided one must also include an `encryptionKey`.
     * }
     * @return StorageObject
     */
    public function object($name, array $options = [])
    {
        $generation = isset($options['generation']) ? $options['generation'] : null;
        $encryptionKey = isset($options['encryptionKey']) ? $options['encryptionKey'] : null;
        $encryptionKeySHA256 = isset($options['encryptionKeySHA256']) ? $options['encryptionKeySHA256'] : null;

        return new StorageObject(
            $this->connection,
            $name,
            $this->identity['bucket'],
            $generation,
            null,
            $encryptionKey,
            $encryptionKeySHA256
        );
    }

    /**
     * Fetches all objects in the bucket.
     *
     * Example:
     * ```
     * // Get all objects beginning with the prefix 'photo'
     * $objects = $bucket->objects([
     *     'prefix' => 'photo',
     *     'fields' => 'items/name,nextPageToken'
     * ]);
     *
     * foreach ($objects as $object) {
     *     var_dump($object->name());
     * }
     * ```
     *
     * @see https://cloud.google.com/storage/docs/json_api/v1/objects/list Objects list API documentation.
     *
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $delimiter Returns results in a directory-like mode.
     *           Results will contain only objects whose names, aside from the
     *           prefix, do not contain delimiter. Objects whose names, aside
     *           from the prefix, contain delimiter will have their name,
     *           truncated after the delimiter, returned in prefixes. Duplicate
     *           prefixes are omitted.
     *     @type integer $maxResults Maximum number of results to return per
     *           request. Defaults to `1000`.
     *     @type string $prefix Filter results with this prefix.
     *     @type string $projection Determines which properties to return. May
     *           be either 'full' or 'noAcl'.
     *     @type bool $versions If true, lists all versions of an object as
     *           distinct results. The default is false.
     *     @type string $fields Selector which will cause the response to only
     *           return the specified fields.
     * }
     * @return \Generator<Google\Cloud\Storage\StorageObject>
     */
    public function objects(array $options = [])
    {
        $options['pageToken'] = null;
        $includeVersions = isset($options['versions']) ? $options['versions'] : false;

        do {
            $response = $this->connection->listObjects($options + $this->identity);

            if (!array_key_exists('items', $response)) {
                break;
            }

            foreach ($response['items'] as $object) {
                $generation = $includeVersions ? $object['generation'] : null;
                yield new StorageObject(
                    $this->connection,
                    $object['name'],
                    $this->identity['bucket'],
                    $generation,
                    $object
                );
            }

            $options['pageToken'] = isset($response['nextPageToken']) ? $response['nextPageToken'] : null;
        } while ($options['pageToken']);
    }

    /**
     * Delete the bucket.
     *
     * Example:
     * ```
     * $bucket->delete();
     * ```
     *
     * @see https://cloud.google.com/storage/docs/json_api/v1/buckets/delete Buckets delete API documentation.
     *
     * @param array $options [optional] {
     *     Configuration options.
     *     @type string $ifMetagenerationMatch If set, only deletes the bucket
     *           if its metageneration matches this value.
     *     @type string $ifMetagenerationNotMatch If set, only deletes the
     *           bucket if its metageneration does not match this value.
     * }
     * @return void
     */
    public function delete(array $options = [])
    {
        $this->connection->deleteBucket($options + $this->identity);
    }

    /**
     * Update the bucket. Upon receiving a result the local bucket's data will
     * be updated.
     *
     * Example:
     * ```
     * // Enable logging on an existing bucket.
     * $bucket->update([
     *     'logging' => [
     *         'logBucket' => 'myBucket',
     *         'logObjectPrefix' => 'prefix'
     *     ]
     * ]);
     * ```
     *
     * @see https://cloud.google.com/storage/docs/json_api/v1/buckets/patch Buckets patch API documentation.
     *
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $ifMetagenerationMatch Makes the return of the bucket
     *           metadata conditional on whether the bucket's current
     *           metageneration matches the given value.
     *     @type string $ifMetagenerationNotMatch Makes the return of the bucket
     *           metadata conditional on whether the bucket's current
     *           metageneration does not match the given value.
     *     @type string $predefinedAcl Apply a predefined set of access controls
     *           to this bucket.
     *     @type string $predefinedDefaultObjectAcl Apply a predefined set of
     *           default object access controls to this bucket.
     *     @type string $projection Determines which properties to return. May
     *           be either 'full' or 'noAcl'.
     *     @type string $fields Selector which will cause the response to only
     *           return the specified fields.
     *     @type array $acl Access controls on the bucket.
     *     @type array $cors The bucket's Cross-Origin Resource Sharing (CORS)
     *           configuration.
     *     @type array $defaultObjectAcl Default access controls to apply to new
     *           objects when no ACL is provided.
     *     @type array $lifecycle The bucket's lifecycle configuration.
     *     @type array $logging The bucket's logging configuration, which
     *           defines the destination bucket and optional name prefix for the
     *           current bucket's logs.
     *     @type array $versioning The bucket's versioning configuration.
     *     @type array $website The bucket's website configuration.
     * }
     * @return array
     */
    public function update(array $options = [])
    {
        return $this->info = $this->connection->patchBucket($options + $this->identity);
    }

    /**
     * Composes a set of objects into a single object.
     *
     * Please note that all objects to be composed must come from the same
     * bucket.
     *
     * Example:
     * ```
     * $sourceObjects = ['log1.txt', 'log2.txt'];
     * $bucket->compose($sourceObjects, 'combined-logs.txt');
     * ```
     *
     * ```
     * // Use an instance of StorageObject.
     * $sourceObjects = [
     *     $bucket->object('log1.txt'),
     *     $bucket->object('log2.txt')
     * ];
     *
     * $bucket->compose($sourceObjects, 'combined-logs.txt');
     * ```
     *
     * @see https://cloud.google.com/storage/docs/json_api/v1/objects/compose Objects compose API documentation
     *
     * @param string[]|StorageObject[] $sourceObjects The objects to compose.
     * @param string $name The name of the composed object.
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $predefinedAcl Predefined ACL to apply to the composed
     *           object. Acceptable values include, `"authenticatedRead"`,
     *           `"bucketOwnerFullControl"`, `"bucketOwnerRead"`, `"private"`,
     *           `"projectPrivate"`, and `"publicRead"`. **Defaults to**
     *           `"private"`.
     *     @type array $metadata Metadata to apply to the composed object. The
     *           available options for metadata are outlined at the
     *           [JSON API docs](https://cloud.google.com/storage/docs/json_api/v1/objects/insert#request-body).
     *     @type string $ifGenerationMatch Makes the operation conditional on whether the object's current generation
     *           matches the given value.
     *     @type string $ifMetagenerationMatch Makes the operation conditional on whether the object's current
     *           metageneration matches the given value.
     * }
     * @return StorageObject
     * @throws \InvalidArgumentException
     */
    public function compose(array $sourceObjects, $name, array $options = [])
    {
        if (count($sourceObjects) < 2) {
            throw new \InvalidArgumentException('Must provide at least two objects to compose.');
        }

        $options += [
            'destinationBucket' => $this->name(),
            'destinationObject' => $name,
            'destinationPredefinedAcl' => isset($options['predefinedAcl']) ? $options['predefinedAcl'] : null,
            'destination' => isset($options['metadata']) ? $options['metadata'] : null,
            'sourceObjects' => array_map(function ($sourceObject) {
                $name = null;
                $generation = null;

                if ($sourceObject instanceof StorageObject) {
                    $name = $sourceObject->name();
                    $generation = isset($sourceObject->identity()['generation'])
                        ? $sourceObject->identity()['generation']
                        : null;
                }

                return array_filter([
                    'name' => $name ?: $sourceObject,
                    'generation' => $generation
                ]);
            }, $sourceObjects)
        ];

        if (!isset($options['destination']['contentType'])) {
            $options['destination']['contentType'] = Psr7\mimetype_from_filename($name);
        }

        if ($options['destination']['contentType'] === null) {
            throw new \InvalidArgumentException('A content type could not be detected and must be provided manually.');
        }

        unset($options['metadata']);
        unset($options['predefinedAcl']);

        $response = $this->connection->composeObject(array_filter($options));

        return new StorageObject(
            $this->connection,
            $response['name'],
            $this->identity['bucket'],
            $response['generation'],
            $response
        );
    }

    /**
     * Retrieves the bucket's details. If no bucket data is cached a network
     * request will be made to retrieve it.
     *
     * Example:
     * ```
     * $info = $bucket->info();
     * echo $info['location'];
     * ```
     *
     * @see https://cloud.google.com/storage/docs/json_api/v1/buckets/get Buckets get API documentation.
     *
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $ifMetagenerationMatch Makes the return of the bucket
     *           metadata conditional on whether the bucket's current
     *           metageneration matches the given value.
     *     @type string $ifMetagenerationNotMatch Makes the return of the bucket
     *           metadata conditional on whether the bucket's current
     *           metageneration does not match the given value.
     *     @type string $projection Determines which properties to return. May
     *           be either 'full' or 'noAcl'.
     * }
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
     * Triggers a network request to reload the bucket's details.
     *
     * Example:
     * ```
     * $bucket->reload();
     * $info = $bucket->info();
     * echo $info['location'];
     * ```
     *
     * @see https://cloud.google.com/storage/docs/json_api/v1/buckets/get Buckets get API documentation.
     *
     * @param array $options [optional] {
     *     Configuration options.
     *
     *     @type string $ifMetagenerationMatch Makes the return of the bucket
     *           metadata conditional on whether the bucket's current
     *           metageneration matches the given value.
     *     @type string $ifMetagenerationNotMatch Makes the return of the bucket
     *           metadata conditional on whether the bucket's current
     *           metageneration does not match the given value.
     *     @type string $projection Determines which properties to return. May
     *           be either 'full' or 'noAcl'.
     * }
     * @return array
     */
    public function reload(array $options = [])
    {
        return $this->info = $this->connection->getBucket($options + $this->identity);
    }

    /**
     * Retrieves the bucket's name.
     *
     * Example:
     * ```
     * echo $bucket->name();
     * ```
     *
     * @return string
     */
    public function name()
    {
        return $this->identity['bucket'];
    }
}
