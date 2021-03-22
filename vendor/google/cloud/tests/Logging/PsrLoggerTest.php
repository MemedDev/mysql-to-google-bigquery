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

namespace Google\Cloud\Tests\Logging;

use Google\Cloud\Logging\Logger;
use Google\Cloud\Logging\PsrLogger;
use Google\Cloud\Logging\Connection\ConnectionInterface;
use Prophecy\Argument;

/**
 * @group logging
 */
class PsrLoggerTest extends \PHPUnit_Framework_TestCase
{
    public $connection;
    public $formattedName;
    public $logName = 'myLog';
    public $projectId = 'myProjectId';
    public $textPayload = 'aPayload';
    public $jsonPayload = ['a' => 'payload'];
    public $resource = ['type' => 'global'];
    public $severity = 'ALERT';

    public function setUp()
    {
        $this->formattedName = "projects/$this->projectId/logs/$this->logName";
        $this->connection = $this->prophesize(ConnectionInterface::class);
    }

    public function getPsrLogger($connection)
    {
        $logger = new Logger($connection->reveal(), $this->logName, $this->projectId);
        return new PsrLogger($logger, $this->resource);
    }

    /**
     * @dataProvider levelProvider
     */
    public function testWritesEntryWithDefinedLevels($level)
    {
        $this->connection->writeEntries([
            'entries' => [
                [
                    'severity' => $level,
                    'textPayload' => $this->textPayload,
                    'logName' => $this->formattedName,
                    'resource' => $this->resource
                ]
            ]
        ])
            ->willReturn([])
            ->shouldBeCalledTimes(1);
        $psrLogger = $this->getPsrLogger($this->connection);

        $this->assertNull($psrLogger->$level($this->textPayload));
    }

    public function levelProvider()
    {
        return [
            ['EMERGENCY'],
            ['ALERT'],
            ['CRITICAL'],
            ['ERROR'],
            ['WARNING'],
            ['NOTICE'],
            ['INFO'],
            ['DEBUG']
        ];
    }

    public function testWritesEntry()
    {
        $this->connection->writeEntries([
            'entries' => [
                [
                    'severity' => $this->severity,
                    'textPayload' => $this->textPayload,
                    'logName' => $this->formattedName,
                    'resource' => $this->resource
                ]
            ]
        ])
            ->willReturn([])
            ->shouldBeCalledTimes(1);
        $psrLogger = $this->getPsrLogger($this->connection);

        $this->assertNull($psrLogger->log($this->severity, $this->textPayload));
    }

    /**
     * @expectedException Psr\Log\InvalidArgumentException
     */
    public function testLogThrowsExceptionWithInvalidLevel()
    {
        $psrLogger = $this->getPsrLogger($this->connection);
        $psrLogger->log('INVALID-LEVEL', $this->textPayload);
    }

    public function testLogAppendsExceptionWhenPassedThroughAsContext()
    {
        $exception = new \Exception('test');
        $this->connection->writeEntries([
            'entries' => [
                [
                    'severity' => $this->severity,
                    'textPayload' => $this->textPayload . ' : ' . (string) $exception,
                    'logName' => $this->formattedName,
                    'resource' => $this->resource
                ]
            ]
        ])
            ->willReturn([])
            ->shouldBeCalledTimes(1);
        $psrLogger = $this->getPsrLogger($this->connection);
        $psrLogger->log($this->severity, $this->textPayload, [
            'exception' => $exception
        ]);
    }
}
