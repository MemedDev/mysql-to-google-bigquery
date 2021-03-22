<?php

namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\LazyOpenStream;

class LazyOpenStreamTest extends BaseTest
{
    private $fname;

    /**
     * @before
     */
    public function setUpTest()
    {
        $this->fname = tempnam(sys_get_temp_dir(), 'tfile');

        if (file_exists($this->fname)) {
            unlink($this->fname);
        }
    }

    /**
     * @after
     */
    public function tearDownTest()
    {
        if (file_exists($this->fname)) {
            unlink($this->fname);
        }
    }

    public function testOpensLazily()
    {
        $l = new LazyOpenStream($this->fname, 'w+');
        $l->write('foo');
        $this->assertInternalTypeGuzzle('array', $l->getMetadata());
        $this->assertFileExists($this->fname);
        $this->assertSame('foo', file_get_contents($this->fname));
        $this->assertSame('foo', (string) $l);
    }

    public function testProxiesToFile()
    {
        file_put_contents($this->fname, 'foo');
        $l = new LazyOpenStream($this->fname, 'r');
        $this->assertSame('foo', $l->read(4));
        $this->assertTrue($l->eof());
        $this->assertSame(3, $l->tell());
        $this->assertTrue($l->isReadable());
        $this->assertTrue($l->isSeekable());
        $this->assertFalse($l->isWritable());
        $l->seek(1);
        $this->assertSame('oo', $l->getContents());
        $this->assertSame('foo', (string) $l);
        $this->assertSame(3, $l->getSize());
        $this->assertInternalTypeGuzzle('array', $l->getMetadata());
        $l->close();
    }

    public function testDetachesUnderlyingStream()
    {
        file_put_contents($this->fname, 'foo');
        $l = new LazyOpenStream($this->fname, 'r');
        $r = $l->detach();
        $this->assertInternalTypeGuzzle('resource', $r);
        fseek($r, 0);
        $this->assertSame('foo', stream_get_contents($r));
        fclose($r);
    }
}
