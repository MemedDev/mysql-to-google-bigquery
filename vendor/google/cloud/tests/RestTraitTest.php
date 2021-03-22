<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Tests;

use Google\Cloud\RestTrait;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * @group root
 */
class RestTraitTest extends \PHPUnit_Framework_TestCase
{
    private $implementation;
    private $requestBuilder;
    private $requestWrapper;

    public function setUp()
    {
        $this->implementation = $this->getObjectForTrait(RestTrait::class);
        $this->requestWrapper = $this->prophesize('Google\Cloud\RequestWrapper');
        $this->requestBuilder = $this->prophesize('Google\Cloud\RequestBuilder');
        $this->requestBuilder->build(Argument::cetera())
            ->willReturn(new Request('GET', '/someplace'));
    }

    public function testSendsRequest()
    {
        $responseBody = '{"whatAWonderful": "response"}';
        $this->requestWrapper->send(Argument::cetera())
            ->willReturn(new Response(200, [], $responseBody));

        $this->implementation->setRequestBuilder($this->requestBuilder->reveal());
        $this->implementation->setRequestWrapper($this->requestWrapper->reveal());
        $actualResponse = $this->implementation->send('resource', 'method');

        $this->assertEquals(json_decode($responseBody, true), $actualResponse);
    }

    public function testSendsRequestWithOptions()
    {
        $httpOptions = [
            'httpOptions' => ['debug' => true],
            'retries' => 5
        ];
        $responseBody = '{"whatAWonderful": "response"}';
        $this->requestWrapper->send(Argument::any(), $httpOptions)
            ->willReturn(new Response(200, [], $responseBody));

        $this->implementation->setRequestBuilder($this->requestBuilder->reveal());
        $this->implementation->setRequestWrapper($this->requestWrapper->reveal());
        $actualResponse = $this->implementation->send('resource', 'method', $httpOptions);

        $this->assertEquals(json_decode($responseBody, true), $actualResponse);
    }

    public function testGetEmulatorBaseUriNoEmulator()
    {
        $res = $this->implementation->getEmulatorBaseUri('http://google.com', null);

        $this->assertEquals('http://google.com', $res);
    }

    public function testGetEmulatorBaseUriEmulator()
    {
        $res = $this->implementation->getEmulatorBaseUri('http://google.com', 'http://localhost:8080');

        $this->assertEquals('http://localhost:8080/', $res);
    }
}
