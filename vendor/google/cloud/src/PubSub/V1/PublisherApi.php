<?php
/*
 * Copyright 2016 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the License
 * is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 * or implied. See the License for the specific language governing permissions and limitations under
 * the License.
 */

/*
 * GENERATED CODE WARNING
 * This file was generated from the file
 * https://github.com/google/googleapis/blob/master/google/pubsub/v1/pubsub.proto
 * and updates to that file get reflected here through a refresh process.
 */

namespace Google\Cloud\PubSub\V1;

use Google\GAX\AgentHeaderDescriptor;
use Google\GAX\ApiCallable;
use Google\GAX\CallSettings;
use Google\GAX\GrpcConstants;
use Google\GAX\GrpcCredentialsHelper;
use Google\GAX\PageStreamingDescriptor;
use Google\GAX\PathTemplate;
use google\iam\v1\GetIamPolicyRequest;
use google\iam\v1\IAMPolicyClient;
use google\iam\v1\Policy;
use google\iam\v1\SetIamPolicyRequest;
use google\iam\v1\TestIamPermissionsRequest;
use google\pubsub\v1\DeleteTopicRequest;
use google\pubsub\v1\GetTopicRequest;
use google\pubsub\v1\ListTopicSubscriptionsRequest;
use google\pubsub\v1\ListTopicsRequest;
use google\pubsub\v1\PublishRequest;
use google\pubsub\v1\PublisherClient;
use google\pubsub\v1\PubsubMessage;
use google\pubsub\v1\Topic;

/**
 * Service Description: The service that an application uses to manipulate topics, and to send
 * messages to a topic.
 *
 * This class provides the ability to make remote calls to the backing service through method
 * calls that map to API methods. Sample code to get started:
 *
 * ```
 * try {
 *     $publisherApi = new PublisherApi();
 *     $formattedName = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
 *     $response = $publisherApi->createTopic($formattedName);
 * } finally {
 *     if (isset($publisherApi)) {
 *         $publisherApi->close();
 *     }
 * }
 * ```
 *
 * Many parameters require resource names to be formatted in a particular way. To assist
 * with these names, this class includes a format method for each type of name, and additionally
 * a parse method to extract the individual identifiers contained within names that are
 * returned.
 */
class PublisherApi
{
    /**
     * The default address of the service.
     */
    const SERVICE_ADDRESS = 'pubsub.googleapis.com';

    /**
     * The default port of the service.
     */
    const DEFAULT_SERVICE_PORT = 443;

    /**
     * The default timeout for non-retrying methods.
     */
    const DEFAULT_TIMEOUT_MILLIS = 30000;

    const _GAX_VERSION = '0.1.0';
    const _CODEGEN_NAME = 'GAPIC';
    const _CODEGEN_VERSION = '0.0.0';

    private static $projectNameTemplate;
    private static $topicNameTemplate;

    private $grpcCredentialsHelper;
    private $iAMPolicyStub;
    private $publisherStub;
    private $scopes;
    private $defaultCallSettings;
    private $descriptors;

    /**
     * Formats a string containing the fully-qualified path to represent
     * a project resource.
     */
    public static function formatProjectName($project)
    {
        return self::getProjectNameTemplate()->render([
            'project' => $project,
        ]);
    }

    /**
     * Formats a string containing the fully-qualified path to represent
     * a topic resource.
     */
    public static function formatTopicName($project, $topic)
    {
        return self::getTopicNameTemplate()->render([
            'project' => $project,
            'topic' => $topic,
        ]);
    }

    /**
     * Parses the project from the given fully-qualified path which
     * represents a project resource.
     */
    public static function parseProjectFromProjectName($projectName)
    {
        return self::getProjectNameTemplate()->match($projectName)['project'];
    }

    /**
     * Parses the project from the given fully-qualified path which
     * represents a topic resource.
     */
    public static function parseProjectFromTopicName($topicName)
    {
        return self::getTopicNameTemplate()->match($topicName)['project'];
    }

    /**
     * Parses the topic from the given fully-qualified path which
     * represents a topic resource.
     */
    public static function parseTopicFromTopicName($topicName)
    {
        return self::getTopicNameTemplate()->match($topicName)['topic'];
    }

    private static function getProjectNameTemplate()
    {
        if (self::$projectNameTemplate == null) {
            self::$projectNameTemplate = new PathTemplate('projects/{project}');
        }

        return self::$projectNameTemplate;
    }

    private static function getTopicNameTemplate()
    {
        if (self::$topicNameTemplate == null) {
            self::$topicNameTemplate = new PathTemplate('projects/{project}/topics/{topic}');
        }

        return self::$topicNameTemplate;
    }

    private static function getPageStreamingDescriptors()
    {
        $listTopicsPageStreamingDescriptor =
                new PageStreamingDescriptor([
                    'requestPageTokenField' => 'page_token',
                    'requestPageSizeField' => 'page_size',
                    'responsePageTokenField' => 'next_page_token',
                    'resourceField' => 'topics',
                ]);
        $listTopicSubscriptionsPageStreamingDescriptor =
                new PageStreamingDescriptor([
                    'requestPageTokenField' => 'page_token',
                    'requestPageSizeField' => 'page_size',
                    'responsePageTokenField' => 'next_page_token',
                    'resourceField' => 'subscriptions',
                ]);

        $pageStreamingDescriptors = [
            'listTopics' => $listTopicsPageStreamingDescriptor,
            'listTopicSubscriptions' => $listTopicSubscriptionsPageStreamingDescriptor,
        ];

        return $pageStreamingDescriptors;
    }

    // TODO(garrettjones): add channel (when supported in gRPC)
    /**
     * Constructor.
     *
     * @param array $options [optional] {
     *                       Optional. Options for configuring the service API wrapper.
     *
     *     @type string $serviceAddress The domain name of the API remote host.
     *                                  Default 'pubsub.googleapis.com'.
     *     @type mixed $port The port on which to connect to the remote host. Default 443.
     *     @type Grpc\ChannelCredentials $sslCreds
     *           A `ChannelCredentials` for use with an SSL-enabled channel.
     *           Default: a credentials object returned from
     *           Grpc\ChannelCredentials::createSsl()
     *     @type array $scopes A string array of scopes to use when acquiring credentials.
     *                         Default the scopes for the Google Cloud Pub/Sub API.
     *     @type array $retryingOverride
     *           An associative array of string => RetryOptions, where the keys
     *           are method names (e.g. 'createFoo'), that overrides default retrying
     *           settings. A value of null indicates that the method in question should
     *           not retry.
     *     @type int $timeoutMillis The timeout in milliseconds to use for calls
     *                              that don't use retries. For calls that use retries,
     *                              set the timeout in RetryOptions.
     *                              Default: 30000 (30 seconds)
     *     @type string $appName The codename of the calling service. Default 'gax'.
     *     @type string $appVersion The version of the calling service.
     *                              Default: the current version of GAX.
     *     @type Google\Auth\CredentialsLoader $credentialsLoader
     *                              A CredentialsLoader object created using the
     *                              Google\Auth library.
     * }
     */
    public function __construct($options = [])
    {
        $defaultScopes = [
            'https://www.googleapis.com/auth/cloud-platform',
            'https://www.googleapis.com/auth/pubsub',
        ];
        $defaultOptions = [
            'serviceAddress' => self::SERVICE_ADDRESS,
            'port' => self::DEFAULT_SERVICE_PORT,
            'scopes' => $defaultScopes,
            'retryingOverride' => null,
            'timeoutMillis' => self::DEFAULT_TIMEOUT_MILLIS,
            'appName' => 'gax',
            'appVersion' => self::_GAX_VERSION,
            'credentialsLoader' => null,
        ];
        $options = array_merge($defaultOptions, $options);

        $headerDescriptor = new AgentHeaderDescriptor([
            'clientName' => $options['appName'],
            'clientVersion' => $options['appVersion'],
            'codeGenName' => self::_CODEGEN_NAME,
            'codeGenVersion' => self::_CODEGEN_VERSION,
            'gaxVersion' => self::_GAX_VERSION,
            'phpVersion' => phpversion(),
        ]);

        $defaultDescriptors = ['headerDescriptor' => $headerDescriptor];
        $this->descriptors = [
            'createTopic' => $defaultDescriptors,
            'publish' => $defaultDescriptors,
            'getTopic' => $defaultDescriptors,
            'listTopics' => $defaultDescriptors,
            'listTopicSubscriptions' => $defaultDescriptors,
            'deleteTopic' => $defaultDescriptors,
            'setIamPolicy' => $defaultDescriptors,
            'getIamPolicy' => $defaultDescriptors,
            'testIamPermissions' => $defaultDescriptors,
        ];
        $pageStreamingDescriptors = self::getPageStreamingDescriptors();
        foreach ($pageStreamingDescriptors as $method => $pageStreamingDescriptor) {
            $this->descriptors[$method]['pageStreamingDescriptor'] = $pageStreamingDescriptor;
        }

        // TODO load the client config in a more package-friendly way
        // https://github.com/googleapis/toolkit/issues/332
        $clientConfigJsonString = file_get_contents(__DIR__.'/resources/publisher_client_config.json');
        $clientConfig = json_decode($clientConfigJsonString, true);
        $this->defaultCallSettings =
                CallSettings::load(
                    'google.pubsub.v1.Publisher',
                    $clientConfig,
                    $options['retryingOverride'],
                    GrpcConstants::getStatusCodeNames(),
                    $options['timeoutMillis']
                );

        $this->scopes = $options['scopes'];

        $createStubOptions = [];
        if (!empty($options['sslCreds'])) {
            $createStubOptions['sslCreds'] = $options['sslCreds'];
        }
        $grpcCredentialsHelperOptions = array_diff_key($options, $defaultOptions);
        $this->grpcCredentialsHelper = new GrpcCredentialsHelper($this->scopes, $grpcCredentialsHelperOptions);

        $createIAMPolicyStubFunction = function ($hostname, $opts) {
            return new IAMPolicyClient($hostname, $opts);
        };
        $this->iAMPolicyStub = $this->grpcCredentialsHelper->createStub(
            $createIAMPolicyStubFunction,
            $options['serviceAddress'],
            $options['port'],
            $createStubOptions
        );
        $createPublisherStubFunction = function ($hostname, $opts) {
            return new PublisherClient($hostname, $opts);
        };
        $this->publisherStub = $this->grpcCredentialsHelper->createStub(
            $createPublisherStubFunction,
            $options['serviceAddress'],
            $options['port'],
            $createStubOptions
        );
    }

    /**
     * Creates the given topic with the given name.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedName = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
     *     $response = $publisherApi->createTopic($formattedName);
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string $name         The name of the topic. It must have the format
     *                             `"projects/{project}/topics/{topic}"`. `{topic}` must start with a letter,
     *                             and contain only letters (`[A-Za-z]`), numbers (`[0-9]`), dashes (`-`),
     *                             underscores (`_`), periods (`.`), tildes (`~`), plus (`+`) or percent
     *                             signs (`%`). It must be between 3 and 255 characters in length, and it
     *                             must not start with `"goog"`.
     * @param array  $optionalArgs {
     *                             Optional.
     *
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @return google\pubsub\v1\Topic
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function createTopic($name, $optionalArgs = [])
    {
        $request = new Topic();
        $request->setName($name);

        $mergedSettings = $this->defaultCallSettings['createTopic']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->publisherStub,
            'CreateTopic',
            $mergedSettings,
            $this->descriptors['createTopic']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Adds one or more messages to the topic. Returns `NOT_FOUND` if the topic
     * does not exist. The message payload must not be empty; it must contain
     *  either a non-empty data field, or at least one attribute.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedTopic = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
     *     $data = "";
     *     $messagesElement = new PubsubMessage();
     *     $messagesElement->setData($data);
     *     $messages = [$messagesElement];
     *     $response = $publisherApi->publish($formattedTopic, $messages);
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string          $topic        The messages in the request will be published on this topic.
     * @param PubsubMessage[] $messages     The messages to publish.
     * @param array           $optionalArgs {
     *                                      Optional.
     *
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @return google\pubsub\v1\PublishResponse
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function publish($topic, $messages, $optionalArgs = [])
    {
        $request = new PublishRequest();
        $request->setTopic($topic);
        foreach ($messages as $elem) {
            $request->addMessages($elem);
        }

        $mergedSettings = $this->defaultCallSettings['publish']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->publisherStub,
            'Publish',
            $mergedSettings,
            $this->descriptors['publish']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Gets the configuration of a topic.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedTopic = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
     *     $response = $publisherApi->getTopic($formattedTopic);
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string $topic        The name of the topic to get.
     * @param array  $optionalArgs {
     *                             Optional.
     *
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @return google\pubsub\v1\Topic
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function getTopic($topic, $optionalArgs = [])
    {
        $request = new GetTopicRequest();
        $request->setTopic($topic);

        $mergedSettings = $this->defaultCallSettings['getTopic']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->publisherStub,
            'GetTopic',
            $mergedSettings,
            $this->descriptors['getTopic']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Lists matching topics.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedProject = PublisherApi::formatProjectName("[PROJECT]");
     *     foreach ($publisherApi->listTopics($formattedProject) as $element) {
     *         // doThingsWith(element);
     *     }
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string $project      The name of the cloud project that topics belong to.
     * @param array  $optionalArgs {
     *                             Optional.
     *
     *     @type int $pageSize
     *          The maximum number of resources contained in the underlying API
     *          response. The API may return fewer values in a page, even if
     *          there are additional values to be retrieved.
     *     @type string $pageToken
     *          A page token is used to specify a page of values to be returned.
     *          If no page token is specified (the default), the first page
     *          of values will be returned. Any page token used here must have
     *          been generated by a previous call to the API.
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @return Google\GAX\PagedListResponse
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function listTopics($project, $optionalArgs = [])
    {
        $request = new ListTopicsRequest();
        $request->setProject($project);
        if (isset($optionalArgs['pageSize'])) {
            $request->setPageSize($optionalArgs['pageSize']);
        }
        if (isset($optionalArgs['pageToken'])) {
            $request->setPageToken($optionalArgs['pageToken']);
        }

        $mergedSettings = $this->defaultCallSettings['listTopics']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->publisherStub,
            'ListTopics',
            $mergedSettings,
            $this->descriptors['listTopics']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Lists the name of the subscriptions for this topic.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedTopic = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
     *     foreach ($publisherApi->listTopicSubscriptions($formattedTopic) as $element) {
     *         // doThingsWith(element);
     *     }
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string $topic        The name of the topic that subscriptions are attached to.
     * @param array  $optionalArgs {
     *                             Optional.
     *
     *     @type int $pageSize
     *          The maximum number of resources contained in the underlying API
     *          response. The API may return fewer values in a page, even if
     *          there are additional values to be retrieved.
     *     @type string $pageToken
     *          A page token is used to specify a page of values to be returned.
     *          If no page token is specified (the default), the first page
     *          of values will be returned. Any page token used here must have
     *          been generated by a previous call to the API.
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @return Google\GAX\PagedListResponse
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function listTopicSubscriptions($topic, $optionalArgs = [])
    {
        $request = new ListTopicSubscriptionsRequest();
        $request->setTopic($topic);
        if (isset($optionalArgs['pageSize'])) {
            $request->setPageSize($optionalArgs['pageSize']);
        }
        if (isset($optionalArgs['pageToken'])) {
            $request->setPageToken($optionalArgs['pageToken']);
        }

        $mergedSettings = $this->defaultCallSettings['listTopicSubscriptions']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->publisherStub,
            'ListTopicSubscriptions',
            $mergedSettings,
            $this->descriptors['listTopicSubscriptions']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Deletes the topic with the given name. Returns `NOT_FOUND` if the topic
     * does not exist. After a topic is deleted, a new topic may be created with
     * the same name; this is an entirely new topic with none of the old
     * configuration or subscriptions. Existing subscriptions to this topic are
     * not deleted, but their `topic` field is set to `_deleted-topic_`.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedTopic = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
     *     $publisherApi->deleteTopic($formattedTopic);
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string $topic        Name of the topic to delete.
     * @param array  $optionalArgs {
     *                             Optional.
     *
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function deleteTopic($topic, $optionalArgs = [])
    {
        $request = new DeleteTopicRequest();
        $request->setTopic($topic);

        $mergedSettings = $this->defaultCallSettings['deleteTopic']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->publisherStub,
            'DeleteTopic',
            $mergedSettings,
            $this->descriptors['deleteTopic']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Sets the access control policy on the specified resource. Replaces any
     * existing policy.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedResource = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
     *     $policy = new Policy();
     *     $response = $publisherApi->setIamPolicy($formattedResource, $policy);
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string $resource     REQUIRED: The resource for which policy is being specified.
     *                             Resource is usually specified as a path, such as,
     *                             projects/{project}/zones/{zone}/disks/{disk}.
     * @param Policy $policy       REQUIRED: The complete policy to be applied to the 'resource'. The size of
     *                             the policy is limited to a few 10s of KB. An empty policy is in general a
     *                             valid policy but certain services (like Projects) might reject them.
     * @param array  $optionalArgs {
     *                             Optional.
     *
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @return google\iam\v1\Policy
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function setIamPolicy($resource, $policy, $optionalArgs = [])
    {
        $request = new SetIamPolicyRequest();
        $request->setResource($resource);
        $request->setPolicy($policy);

        $mergedSettings = $this->defaultCallSettings['setIamPolicy']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->iAMPolicyStub,
            'SetIamPolicy',
            $mergedSettings,
            $this->descriptors['setIamPolicy']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Gets the access control policy for a resource. Is empty if the
     * policy or the resource does not exist.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedResource = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
     *     $response = $publisherApi->getIamPolicy($formattedResource);
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string $resource     REQUIRED: The resource for which policy is being requested. Resource
     *                             is usually specified as a path, such as, projects/{project}.
     * @param array  $optionalArgs {
     *                             Optional.
     *
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @return google\iam\v1\Policy
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function getIamPolicy($resource, $optionalArgs = [])
    {
        $request = new GetIamPolicyRequest();
        $request->setResource($resource);

        $mergedSettings = $this->defaultCallSettings['getIamPolicy']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->iAMPolicyStub,
            'GetIamPolicy',
            $mergedSettings,
            $this->descriptors['getIamPolicy']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Returns permissions that a caller has on the specified resource.
     *
     * Sample code:
     * ```
     * try {
     *     $publisherApi = new PublisherApi();
     *     $formattedResource = PublisherApi::formatTopicName("[PROJECT]", "[TOPIC]");
     *     $permissions = [];
     *     $response = $publisherApi->testIamPermissions($formattedResource, $permissions);
     * } finally {
     *     if (isset($publisherApi)) {
     *         $publisherApi->close();
     *     }
     * }
     * ```
     *
     * @param string   $resource     REQUIRED: The resource for which policy detail is being requested.
     *                               Resource is usually specified as a path, such as, projects/{project}.
     * @param string[] $permissions  The set of permissions to check for the 'resource'. Permissions with
     *                               wildcards (such as '*' or 'storage.*') are not allowed.
     * @param array    $optionalArgs {
     *                               Optional.
     *
     *     @type Google\GAX\RetrySettings $retrySettings
     *          Retry settings to use for this call. If present, then
     *          $timeoutMillis is ignored.
     *     @type int $timeoutMillis
     *          Timeout to use for this call. Only used if $retrySettings
     *          is not set.
     * }
     *
     * @return google\iam\v1\TestIamPermissionsResponse
     *
     * @throws Google\GAX\ApiException if the remote call fails
     */
    public function testIamPermissions($resource, $permissions, $optionalArgs = [])
    {
        $request = new TestIamPermissionsRequest();
        $request->setResource($resource);
        foreach ($permissions as $elem) {
            $request->addPermissions($elem);
        }

        $mergedSettings = $this->defaultCallSettings['testIamPermissions']->merge(
            new CallSettings($optionalArgs)
        );
        $callable = ApiCallable::createApiCall(
            $this->iAMPolicyStub,
            'TestIamPermissions',
            $mergedSettings,
            $this->descriptors['testIamPermissions']
        );

        return $callable(
            $request,
            [],
            ['call_credentials_callback' => $this->createCredentialsCallback()]);
    }

    /**
     * Initiates an orderly shutdown in which preexisting calls continue but new
     * calls are immediately cancelled.
     */
    public function close()
    {
        $this->iAMPolicyStub->close();
        $this->publisherStub->close();
    }

    private function createCredentialsCallback()
    {
        return $this->grpcCredentialsHelper->createCallCredentialsCallback();
    }
}
