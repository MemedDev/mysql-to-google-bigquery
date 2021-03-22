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

namespace Google\Cloud;

use Google\Cloud\RequestBuilder;
use Google\Cloud\RequestWrapper;

/**
 * Provides shared functionality for REST service implementations.
 */
trait RestTrait
{
    /**
     * @var RequestBuilder Builds PSR7 requests from a service definition.
     */
    private $requestBuilder;

    /**
     * @var RequestWrapper Wrapper used to handle sending requests to the
     * JSON API.
     */
    private $requestWrapper;

    /**
     * Sets the request builder.
     *
     * @param RequestBuilder $requestBuilder Builds PSR7 requests from a service
     *        definition.
     */
    public function setRequestBuilder(RequestBuilder $requestBuilder)
    {
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * Sets the request wrapper.
     *
     * @param RequestWrapper $requestWrapper Wrapper used to handle sending
     *        requests to the JSON API.
     */
    public function setRequestWrapper(RequestWrapper $requestWrapper)
    {
        $this->requestWrapper = $requestWrapper;
    }

    /**
     * Delivers a request built from the service definition.
     *
     * @param string $resource The resource type used for the request.
     * @param string $method The method used for the request.
     * @param array $options [optional] Options used to build out the request.
     * @return array
     */
    public function send($resource, $method, array $options = [])
    {
        $requestOptions = array_intersect_key($options, [
            'httpOptions' => null,
            'retries' => null
        ]);

        return json_decode(
            $this->requestWrapper->send(
                $this->requestBuilder->build($resource, $method, $options),
                $requestOptions
            )->getBody(),
            true
        );
    }

    /**
     * When emulators are enabled, use them as the service host
     *
     * @param string $baseUri
     * @param string $emulatorHost [optional]
     * @return string
     */
    public function getEmulatorBaseUri($baseUri, $emulatorHost = null)
    {
        if ($emulatorHost) {
            $emulatorUriComponents = parse_url($emulatorHost);
            $emulatorUriComponents = array_merge(['scheme' => 'http', 'port' => ''], $emulatorUriComponents);
            $baseUri = "{$emulatorUriComponents['scheme']}://{$emulatorUriComponents['host']}";
            $baseUri .= $emulatorUriComponents['port'] ? ":{$emulatorUriComponents['port']}/" : '/';
        }

        return $baseUri;
    }
}
