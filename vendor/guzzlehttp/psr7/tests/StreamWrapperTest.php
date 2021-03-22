<?php

namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7;

/**
 * @covers GuzzleHttp\Psr7\StreamWrapper
 */
class StreamWrapperTest extends BaseTest
{
    public function testResource()
    {
        $stream = Psr7\Utils::streamFor('foo');
        $handle = StreamWrapper::getResource($stream);
        $this->assertSame('foo', fread($handle, 3));
        $this->assertSame(3, ftell($handle));
        $this->assertSame(3, fwrite($handle, 'bar'));
        $this->assertSame(0, fseek($handle, 0));
        $this->assertSame('foobar', fread($handle, 6));
        $this->assertSame('', fread($handle, 1));
        $this->assertTrue(feof($handle));

        $stBlksize  = defined('PHP_WINDOWS_VERSION_BUILD') ? -1 : 0;

        // This fails on HHVM for some reason
        if (!defined('HHVM_VERSION')) {
            $this->assertSame([
                0         => 0,
                1         => 0,
                2         => 33206,
                3         => 0,
                4         => 0,
                5         => 0,
                6         => 0,
                7         => 6,
                8         => 0,
                9         => 0,
                10        => 0,
                11        => $stBlksize,
                12        => $stBlksize,
                'dev'     => 0,
                'ino'     => 0,
                'mode'    => 33206,
                'nlink'   => 0,
                'uid'     => 0,
                'gid'     => 0,
                'rdev'    => 0,
                'size'    => 6,
                'atime'   => 0,
                'mtime'   => 0,
                'ctime'   => 0,
                'blksize' => $stBlksize,
                'blocks'  => $stBlksize,
            ], fstat($handle));
        }

        $this->assertTrue(fclose($handle));
        $this->assertSame('foobar', (string) $stream);
    }

    public function testStreamContext()
    {
        $stream = Psr7\Utils::streamFor('foo');

        $this->assertSame('foo', file_get_contents('guzzle://stream', false, StreamWrapper::createStreamContext($stream)));
    }

    public function testStreamCast()
    {
        $streams = [
            StreamWrapper::getResource(Psr7\Utils::streamFor('foo')),
            StreamWrapper::getResource(Psr7\Utils::streamFor('bar'))
        ];
        $write = null;
        $except = null;
        $this->assertInternalTypeGuzzle('integer', stream_select($streams, $write, $except, 0));
    }

    public function testValidatesStream()
    {
        $stream = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass();
        $stream->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(false));
        $stream->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(false));

        $this->expectExceptionGuzzle('InvalidArgumentException');

        StreamWrapper::getResource($stream);
    }

    public function testReturnsFalseWhenStreamDoesNotExist()
    {
        $this->expectWarningGuzzle();

        fopen('guzzle://foo', 'r');
    }

    public function testCanOpenReadonlyStream()
    {
        $stream = $this->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->setMethods(['isReadable', 'isWritable'])
            ->getMockForAbstractClass();
        $stream->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(false));
        $stream->expects($this->once())
            ->method('isWritable')
            ->will($this->returnValue(true));
        $r = StreamWrapper::getResource($stream);
        $this->assertInternalTypeGuzzle('resource', $r);
        fclose($r);
    }

    public function testUrlStat()
    {
        StreamWrapper::register();

        $stBlksize = defined('PHP_WINDOWS_VERSION_BUILD') ? -1 : 0;

        $this->assertSame([
            0         => 0,
            1         => 0,
            2         => 0,
            3         => 0,
            4         => 0,
            5         => 0,
            6         => 0,
            7         => 0,
            8         => 0,
            9         => 0,
            10        => 0,
            11        => $stBlksize,
            12        => $stBlksize,
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 0,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => 0,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => $stBlksize,
            'blocks'  => $stBlksize,
        ], stat('guzzle://stream'));
    }

    public function testXmlReaderWithStream()
    {
        if (!class_exists('XMLReader')) {
            $this->markTestSkipped('XML Reader is not available.');
        }
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('This does not work on HHVM.');
        }

        $stream = Psr7\Utils::streamFor('<?xml version="1.0" encoding="utf-8"?><foo />');

        StreamWrapper::register();
        libxml_set_streams_context(StreamWrapper::createStreamContext($stream));
        $reader = new \XMLReader();

        $this->assertTrue($reader->open('guzzle://stream'));
        $this->assertTrue($reader->read());
        $this->assertSame('foo', $reader->name);
    }

    public function testXmlWriterWithStream()
    {
        if (!class_exists('XMLWriter')) {
            $this->markTestSkipped('XML Writer is not available.');
        }
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('This does not work on HHVM.');
        }

        $stream = Psr7\Utils::streamFor(fopen('php://memory', 'wb'));

        StreamWrapper::register();
        libxml_set_streams_context(StreamWrapper::createStreamContext($stream));
        $writer = new \XMLWriter();

        $this->assertTrue($writer->openURI('guzzle://stream'));
        $this->assertTrue($writer->startDocument());
        $this->assertTrue($writer->writeElement('foo'));
        $this->assertTrue($writer->endDocument());

        $stream->rewind();
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0"?><foo />', (string) $stream);
    }
}
