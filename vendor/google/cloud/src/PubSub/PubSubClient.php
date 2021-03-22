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

namespace Google\Cloud\PubSub;

use Google\Cloud\ClientTrait;
use Google\Cloud\PubSub\Connection\Rest;
use InvalidArgumentException;

/**
 * Google Cloud Pub/Sub client. Allows you to send and receive
 * messages between independent applications. Find more information at
 * [Google Cloud Pub/Sub docs](https://cloud.google.com/pubsub/docs/).
 *
 * To enable the [Google Cloud Pub/Sub Emulator](https://cloud.google.com/pubsub/emulator),
 * set the [`PUBSUB_EMULATOR_HOST`](https://cloud.google.com/pubsub/emulator#env)
 * environment variable.
 *
 * Example:
 * ```
 * use Google\Cloud\ServiceBuilder;
 *
 * $cloud = new ServiceBuilder();
 *
 * $pubsub = $cloud->pubsub();
 * ```
 *
 * ```
 * // PubSubClient can be instantiated directly.
 * use Google\Cloud\PubSub\PubSubClient;
 *
 * $pubsub = new PubSubClient();
 * ```
 *
 * ```
 * // Using the Pub/Sub Emulator
 * use Google\Cloud\ServiceBuilder;
 *
 * // Be sure to use the port specified when starting the emulator.
 * // `8900` is used as an example only.
 * putenv('PUBSUB_EMULATOR_HOST=http://localhost:8900');
 *
 * $cloud = new ServiceBuilder();
 * $pubsub = $cloud->pubsub();
 * ```
 */
class PubSubClient
{
    use ClientTrait;
    use IncomingMessageTrait;
    use ResourceNameTrait;

    const FULL_CONTROL_SCOPE = 'https://www.googleapis.com/auth/pubsub';

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var bool
     */
    private $encode;

    /**
     * Create a PubSub client.
     *
     * @param array $config [optional] {
     *     Configuration Options.
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
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['scopes'])) {
            $config['scopes'] = [self::FULL_CONTROL_SCOPE];
        }

        $this->connection = new Rest($this->configureAuthentication($config));

        // When gRPC support is added, this value should be set to `false`
        // when gRPC transport is chosen.
        $this->encode = true;
    }

    /**
     * Create a topic.
     *
     * Unlike {@see Google\Cloud\PubSub\PubSubClient::topic()}, this method will send an API call to
     * create the topic. If the topic already exists, an exception will be
     * thrown. When in doubt, use {@see Google\Cloud\PubSub\PubSubClient::topic()}.
     *
     * Example:
     * ```
     * $topic = $pubsub->createTopic('my-new-topic');
     * echo $topic->info()['name']; // `projects/my-awesome-project/topics/my-new-topic`
     * ```
     *
     * @see https://cloud.google.com/pubsub/docs/reference/rest/v1/projects.topics/create Create Topic
     *
     * @param string $name The topic name
     * @param array $options [optional] Configuration Options
     * @return Topic
     */
    public function createTopic($name, array $options = [])
    {
        $topic = $this->topicFactory($name);
        $topic->create($options);

        return $topic;
    }

    /**
     * Lazily instantiate a topic with a topic name.
     *
     * No API requests are made by this method. If you want to create a new
     * topic, use {@see Google\Cloud\PubSub\Topic::createTopic()}.
     *
     * Example:
     * ```
     * // No API request yet!
     * $topic = $pubsub->topic('my-new-topic');
     *
     * // This will execute an API call.
     * echo $topic->info()['name']; // `projects/my-awesome-project/topics/my-new-topic`
     * ```
     *
     * @param string $name The topic name
     * @return Topic
     */
    public function topic($name)
    {
        return $this->topicFactory($name);
    }

    /**
     * Get a list of the topics registered to your project.
     *
     * Example:
     * ```
     * $topics = $pubsub->topics();
     * foreach ($topics as $topic) {
     *     $info = $topic->info();
     *     echo $info['name']; // `projects/my-awesome-project/topics/<topic-name>`
     * }
     * ```
     *
     * @see https://cloud.google.com/pubsub/docs/reference/rest/v1/projects.topics/list List Topics
     *
     * @param array $options [optional] {
     *     Configuration Options
     *
     *     @type int $pageSize Maximum number of results to return per
     *           request.
     * }
     * @return Generator<Google\Cloud\PubSub\Topic>
     */
    public function topics(array $options = [])
    {
        $options['pageToken'] = null;

        do {
            $response = $this->connection->listTopics($options + [
                'project' => $this->formatName('project', $this->projectId)
            ]);

            foreach ($response['topics'] as $topic) {
                yield $this->topicFactory($topic['name'], $topic);
            }

            // If there's a page token, we'll request the next page.
            $options['pageToken'] = isset($response['nextPageToken'])
                ? $response['nextPageToken']
                : null;
        } while ($options['pageToken']);
    }

    /**
     * Create a Subscription. If the subscription does not exist, it will be
     * created.
     *
     * Use {@see Google\Cloud\PubSub\PubSubClient::subscription()} to create a subscription object
     * without any API requests. If the topic already exists, an exception will
     * be thrown. When in doubt, use {@see Google\Cloud\PubSub\PubSubClient::subscription()}.
     *
     * Example:
     * ```
     * // Create a subscription. If it doesn't exist in the API, it will be created.
     * $subscription = $pubsub->subscribe('my-new-subscription', 'my-topic-name');
     * ```
     *
     * @see https://cloud.google.com/pubsub/docs/reference/rest/v1/projects.subscriptions/create Create Subscription
     *
     * @param string $name A subscription name
     * @param string $topicName The topic to which the new subscription will be subscribed.
     * @param array  $options [optional] Please see {@see Google\Cloud\PubSub\Subscription::create()}
     *        for configuration details.
     * @return Subscription
     */
    public function subscribe($name, $topicName, array $options = [])
    {
        $subscription = $this->subscriptionFactory($name, $topicName);
        $subscription->create($options);

        return $subscription;
    }

    /**
     * Lazily instantiate a subscription with a subscription name.
     *
     * This method will NOT perform any API calls. If you wish to create a new
     * subscription, use {@see Google\Cloud\PubSub\PubSubClient::subscribe()}.
     *
     * Unless you are sure the subscription exists, you should check its
     * existence before using it.
     *
     * Example:
     * ```
     * $subscription = $pubsub->subscription('my-new-subscription');
     * ```
     *
     * @param string $name The subscription name
     * @param string $topicName [optional] The topic name
     * @return Subscription
     */
    public function subscription($name, $topicName = null)
    {
        return $this->subscriptionFactory($name, $topicName);
    }

    /**
     * Get a list of the subscriptions registered to all of your project's
     * topics.
     *
     * Example:
     * ```
     * $subscriptions = $pubsub->subscriptions();
     * foreach ($subscriptions as $subscription) {
     *      $info = $subscription->info();
     *      echo $info['name']; // `projects/my-awesome-project/subscriptions/<subscription-name>`
     * }
     * ```
     *
     * @see https://cloud.google.com/pubsub/docs/reference/rest/v1/projects.subscriptions/list List Subscriptions
     *
     * @param array $options [optional] {
     *     Configuration Options
     *
     *     @type int $pageSize Maximum number of results to return per
     *           request.
     * }
     * @return \Generator<Google\Cloud\PubSub\Subscription>
     */
    public function subscriptions(array $options = [])
    {
        $options['pageToken'] = null;

        do {
            $response = $this->connection->listSubscriptions($options + [
                'project' => $this->formatName('project', $this->projectId)
            ]);

            foreach ($response['subscriptions'] as $subscription) {
                yield $this->subscriptionFactory(
                    $subscription['name'],
                    $subscription['topic'],
                    $subscription
                );
            }

            // If there's a page token, we'll request the next page.
            $options['pageToken'] = isset($response['nextPageToken'])
                ? $response['nextPageToken']
                : null;
        } while ($options['pageToken']);
    }

    /**
     * Consume an incoming message and return a PubSub Message.
     *
     * This method is for use with push delivery only.
     *
     * Example:
     * ```
     * $httpPostRequestBody = file_get_contents('php://input');
     * $requestData = json_decode($httpPostRequestBody, true);
     *
     * $message = $pubsub->consume($requestData);
     * ```
     *
     * @param array $requestBody The HTTP Request body
     * @return Message
     */
    public function consume(array $requestData)
    {
        return $this->messageFactory($requestData, $this->connection, $this->projectId, $this->encode);
    }

    /**
     * Create an instance of a topic
     *
     * @codingStandardsIgnoreStart
     * @param string $name The topic name
     * @param array  $info [optional] Information about the topic. Used internally to
     *        populate topic objects with an API result. Should be
     *        a representation of a [Topic](https://cloud.google.com/pubsub/docs/reference/rest/v1/projects.topics#Topic).
     * @return Topic
     * @codingStandardsIgnoreEnd
     */
    private function topicFactory($name, array $info = null)
    {
        return new Topic(
            $this->connection,
            $this->projectId,
            $name,
            $this->encode,
            $info
        );
    }

    /**
     * Create a subscription instance.
     *
     * @codingStandardsIgnoreStart
     * @param string $name The subscription name
     * @param string $topicName [optional] The topic name
     * @param array  $info [optional] Information about the subscription. Used
     *        to populate subscriptons with an api result. Should be a
     *        representation of a [Subscription](https://cloud.google.com/pubsub/docs/reference/rest/v1/projects.subscriptions#Subscription).
     * @return Subscription
     * @codingStandardsIgnoreEnd
     */
    private function subscriptionFactory($name, $topicName = null, array $info = null)
    {
        return new Subscription(
            $this->connection,
            $this->projectId,
            $name,
            $topicName,
            $this->encode,
            $info
        );
    }
}
