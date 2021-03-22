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

namespace Google\Cloud;

use Google\Auth\HttpHandler\HttpHandlerFactory;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Datastore\DatastoreClient;
use Google\Cloud\Logging\LoggingClient;
use Google\Cloud\NaturalLanguage\NaturalLanguageClient;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\Speech\SpeechClient;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Translate\TranslateClient;
use Google\Cloud\Vision\VisionClient;

/**
 * Google Cloud Platform is a set of modular cloud-based services that allow you
 * to create anything from simple websites to complex applications.
 *
 * This API aims to expose access to these services in a way that is intuitive
 * and easy to use for PHP enthusiasts. The ServiceBuilder instance exposes
 * factory methods which grant access to the various services contained within
 * the API.
 *
 * Configuration is simple. Pass in an array of configuration options to the
 * constructor up front which can be shared between clients or specify the
 * options for the specific services you wish to access, e.g. Datastore, or
 * Storage.
 *
 * Please note that unless otherwise noted the examples below take advantage of
 * [Application Default Credentials](https://developers.google.com/identity/protocols/application-default-credentials).
 */
class ServiceBuilder
{
    const VERSION = '0.11.1';

    /**
     * @var array Configuration options to be used between clients.
     */
    private $config;

    /**
     * Pass in an array of configuration options which will be shared between
     * clients.
     *
     * Example:
     * ```
     * use Google\Cloud\ServiceBuilder;
     *
     * $cloud = new ServiceBuilder([
     *     'projectId' => 'myAwesomeProject'
     * ]);
     * ```
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
     *           credentials .json file retrieved from the Google Developer's
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
        $this->config = $this->resolveConfig($config);
    }

    /**
     * Google Cloud BigQuery client. Allows you to create, manage, share and query
     * data. Find more information at
     * [Google Cloud BigQuery Docs](https://cloud.google.com/bigquery/what-is-bigquery).
     *
     * Example:
     * ```
     * $bigQuery = $cloud->bigQuery();
     * ```
     *
     * @param array $config [optional] Configuration options. See
     *        {@see Google\Cloud\ServiceBuilder::__construct()} for the available options.
     * @return BigQueryClient
     */
    public function bigQuery(array $config = [])
    {
        return new BigQueryClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Google Cloud Datastore client. Cloud Datastore is a highly-scalable NoSQL
     * database for your applications.  Find more information at
     * [Google Cloud Datastore docs](https://cloud.google.com/datastore/docs/).
     *
     * Example:
     * ```
     * $datastore = $cloud->datastore();
     * ```
     *
     * @param array $config [optional] Configuration options. See
     *        {@see Google\Cloud\ServiceBuilder::__construct()} for the available options.
     * @return DatastoreClient
     */
    public function datastore(array $config = [])
    {
        return new DatastoreClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Google Stackdriver Logging client. Allows you to store, search, analyze,
     * monitor, and alert on log data and events from Google Cloud Platform and
     * Amazon Web Services. Find more information at
     * [Google Stackdriver Logging docs](https://cloud.google.com/logging/docs/).
     *
     * Example:
     * ```
     * $logging = $cloud->logging();
     * ```
     *
     * @param array $config [optional] Configuration options. See
     *        {@see Google\Cloud\ServiceBuilder::__construct()} for the available options.
     * @return LoggingClient
     */
    public function logging(array $config = [])
    {
        return new LoggingClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Google Cloud Natural Language client. Provides natural language
     * understanding technologies to developers, including sentiment analysis,
     * entity recognition, and syntax analysis. Currently only English, Spanish,
     * and Japanese textual context are supported. Find more information at
     * [Google Cloud Natural Language docs](https://cloud.google.com/natural-language/docs/).
     *
     * Example:
     * ```
     * $language = $cloud->naturalLanguage();
     * ```
     *
     * @param array $config [optional] Configuration options. See
     *        {@see Google\Cloud\ServiceBuilder::__construct()} for the available options.
     * @return NaturalLanguageClient
     */
    public function naturalLanguage(array $config = [])
    {
        return new NaturalLanguageClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Google Cloud Pub/Sub client. Allows you to send and receive
     * messages between independent applications. Find more information at
     * [Google Cloud Pub/Sub docs](https://cloud.google.com/pubsub/docs/).
     *
     * Example:
     * ```
     * $pubsub = $cloud->pubsub();
     * ```
     *
     * @param array $config [optional] Configuration options. See
     *        {@see Google\Cloud\ServiceBuilder::__construct()} for the available options.
     * @return PubSubClient
     */
    public function pubsub(array $config = [])
    {
        return new PubSubClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Google Cloud Speech client. Enables easy integration of Google speech
     * recognition technologies into developer applications. Send audio and
     * receive a text transcription from the Cloud Speech API service. Find more
     * information at
     * [Google Cloud Speech API docs](https://developers.google.com/speech).
     *
     * Example:
     * ```
     * $speech = $cloud->speech();
     * ```
     *
     * @param array $config [optional] Configuration options. See
     *        {@see Google\Cloud\ServiceBuilder::__construct()} for the available options.
     * @return SpeechClient
     */
    public function speech(array $config = [])
    {
        return new SpeechClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Google Cloud Storage client. Allows you to store and retrieve data on
     * Google's infrastructure. Find more information at
     * [Google Cloud Storage API docs](https://developers.google.com/storage).
     *
     * Example:
     * ```
     * $storage = $cloud->storage();
     * ```
     *
     * @param array $config [optional] Configuration options. See
     *        {@see Google\Cloud\ServiceBuilder::__construct()} for the available options.
     * @return StorageClient
     */
    public function storage(array $config = [])
    {
        return new StorageClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Google Cloud Vision client. Allows you to understand the content of an
     * image, classify images into categories, detect text, objects, faces and
     * more. Find more information at [Google Cloud Vision docs](https://cloud.google.com/vision/docs/).
     *
     * Example:
     * ```
     * $vision = $cloud->vision();
     * ```
     *
     * @param array $config [optional] Configuration options. See
     *        {@see Google\Cloud\ServiceBuilder::__construct()} for the available options.
     * @return VisionClient
     */
    public function vision(array $config = [])
    {
        return new VisionClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Google Translate client. Provides the ability to dynamically translate
     * text between thousands of language pairs. The Google Translate API lets
     * websites and programs integrate with Google Translate API
     * programmatically. Google Translate API is available as a paid service.
     * See the [Pricing](https://cloud.google.com/translate/v2/pricing) and
     * [FAQ](https://cloud.google.com/translate/v2/faq) pages for details. Find
     * more information at
     * [Google Translate docs](https://cloud.google.com/translate/docs/).
     *
     * Please note that unlike most other Cloud Platform services Google
     * Translate requires a public API access key and cannot currently be
     * accessed with a service account or application default credentials.
     * Follow the
     * [before you begin](https://cloud.google.com/translate/v2/translating-text-with-rest#before-you-begin)
     * instructions to learn how to generate a key.
     *
     * Example:
     * ```
     * use Google\Cloud\ServiceBuilder;
     *
     * $builder = new ServiceBuilder([
     *     'key' => 'YOUR_KEY'
     * ]);
     *
     * $translate = $builder->translate();
     * ```
     *
     * @param array $config [optional] {
     *     Configuration options.
     *
     *     @type string $key A public API access key.
     *     @type string $target The target language to assign to the client.
     *           Defaults to `en` (English).
     *     @type callable $httpHandler A handler used to deliver Psr7 requests.
     *     @type int $retries Number of retries for a failed request.
     *           **Defaults to** `3`.
     * }
     * @return TranslateClient
     */
    public function translate(array $config = [])
    {
        return new TranslateClient($config ? $this->resolveConfig($config) : $this->config);
    }

    /**
     * Resolves configuration options.
     *
     * @param array $config
     * @return array
     */
    private function resolveConfig(array $config)
    {
        if (!isset($config['httpHandler'])) {
            $config['httpHandler'] = HttpHandlerFactory::build();
        }

        return $config;
    }
}
