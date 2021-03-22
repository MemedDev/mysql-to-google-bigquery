<?php

namespace GuzzleHttp\Tests\Psr7;

use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * @param string      $exception
     * @param string|null $message
     */
    public function expectExceptionGuzzle($exception, $message = null)
    {
        if (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException($exception, $message);
        } else {
            $this->expectException($exception);
            if (null !== $message) {
                $this->expectExceptionMessage($message);
            }
        }
    }

    public function expectWarningGuzzle()
    {
        if (method_exists($this, 'expectWarning')) {
            $this->expectWarning();
        } elseif (class_exists('PHPUnit\Framework\Error\Warning')) {
            $this->expectExceptionGuzzle('PHPUnit\Framework\Error\Warning');
        } else {
            $this->expectExceptionGuzzle('PHPUnit_Framework_Error_Warning');
        }
    }

    /**
     * @param string $type
     * @param mixed  $input
     */
    public function assertInternalTypeGuzzle($type, $input)
    {
        switch ($type) {
            case 'array':
                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($input);
                } else {
                    $this->assertInternalType('array', $input);
                }
                break;
            case 'bool':
            case 'boolean':
                if (method_exists($this, 'assertIsBool')) {
                    $this->assertIsBool($input);
                } else {
                    $this->assertInternalType('bool', $input);
                }
                break;
            case 'double':
            case 'float':
            case 'real':
                if (method_exists($this, 'assertIsFloat')) {
                    $this->assertIsFloat($input);
                } else {
                    $this->assertInternalType('float', $input);
                }
                break;
            case 'int':
            case 'integer':
                if (method_exists($this, 'assertIsInt')) {
                    $this->assertIsInt($input);
                } else {
                    $this->assertInternalType('int', $input);
                }
                break;
            case 'numeric':
                if (method_exists($this, 'assertIsNumeric')) {
                    $this->assertIsNumeric($input);
                } else {
                    $this->assertInternalType('numeric', $input);
                }
                break;
            case 'object':
                if (method_exists($this, 'assertIsObject')) {
                    $this->assertIsObject($input);
                } else {
                    $this->assertInternalType('object', $input);
                }
                break;
            case 'resource':
                if (method_exists($this, 'assertIsResource')) {
                    $this->assertIsResource($input);
                } else {
                    $this->assertInternalType('resource', $input);
                }
                break;
            case 'string':
                if (method_exists($this, 'assertIsString')) {
                    $this->assertIsString($input);
                } else {
                    $this->assertInternalType('string', $input);
                }
                break;
            case 'scalar':
                if (method_exists($this, 'assertIsScalar')) {
                    $this->assertIsScalar($input);
                } else {
                    $this->assertInternalType('scalar', $input);
                }
                break;
            case 'callable':
                if (method_exists($this, 'assertIsCallable')) {
                    $this->assertIsCallable($input);
                } else {
                    $this->assertInternalType('callable', $input);
                }
                break;
            case 'iterable':
                if (method_exists($this, 'assertIsIterable')) {
                    $this->assertIsIterable($input);
                } else {
                    $this->assertInternalType('iterable', $input);
                }
                break;
        }
    }

    /**
     * @param string $needle
     * @param string $haystack
     */
    public function assertStringContainsStringGuzzle($needle, $haystack)
    {
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString($needle, $haystack);
        } else {
            $this->assertContains($needle, $haystack);
        }
    }
}
