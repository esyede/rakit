<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Curl;
use System\Storage;

class StorageDisksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The AWS documentation's example credentials.
     *
     * @var array
     */
    protected $aws = [
        'driver' => 's3',
        'key' => 'AKIAIOSFODNN7EXAMPLE',
        'secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
        'region' => 'us-east-1',
        'bucket' => 'examplebucket',
        'endpoint' => 'https://s3.amazonaws.com',
    ];

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Curl::fake(false);
        Storage::$disks = [];
        is_dir(path('storage').'disktest') && Storage::rmdir(path('storage').'disktest');
    }

    /**
     * Signature V4 matches the examples of the AWS documentation.
     *
     * @group system
     */
    public function testSignatureMatchesAwsExamples()
    {
        $s3 = new StorageDisksTestS3($this->aws);
        $empty = hash('sha256', '');

        // "GET Object" example of the Signature Version 4 documentation.
        $authorization = $s3->authorization('GET', '/test.txt', [], [
            'host' => 'examplebucket.s3.amazonaws.com',
            'range' => 'bytes=0-9',
            'x-amz-content-sha256' => $empty,
            'x-amz-date' => '20130524T000000Z',
        ], $empty, StorageDisksTestS3::TIME);

        $this->assertEquals(
            'AWS4-HMAC-SHA256 Credential=AKIAIOSFODNN7EXAMPLE/20130524/us-east-1/s3/aws4_request,'
            .'SignedHeaders=host;range;x-amz-content-sha256;x-amz-date,'
            .'Signature=f0e8bdb87c964420e857bd35b5d6ed310bd44f0170aba48dd91039c6036bdb41',
            $authorization
        );

        // "Presigned URL" example.
        $this->assertEquals(
            'https://examplebucket.s3.amazonaws.com/test.txt?X-Amz-Algorithm=AWS4-HMAC-SHA256'
            .'&X-Amz-Credential=AKIAIOSFODNN7EXAMPLE%2F20130524%2Fus-east-1%2Fs3%2Faws4_request'
            .'&X-Amz-Date=20130524T000000Z&X-Amz-Expires=86400&X-Amz-SignedHeaders=host'
            .'&X-Amz-Signature=aeeed9bbccd4d02ee5c0109b86d86835f995330da4c265957d157751f604d404',
            $s3->temporary_url('test.txt', 86400)
        );
    }

    /**
     * Requests go to the right URLs, signed, and responses are read back.
     *
     * @group system
     */
    public function testRequests()
    {
        Config::set('storage.disks.s3test', array_merge($this->aws, [
            'endpoint' => 'http://minio.test:9000',
            'path_style' => true,
            'root' => 'app',
            'url' => 'https://cdn.example.com',
        ]));

        $listing = '<ListBucketResult><IsTruncated>false</IsTruncated>'
            .'<Contents><Key>app/a/one.txt</Key></Contents><Contents><Key>app/a/b &amp; c.txt</Key></Contents></ListBucketResult>';

        Curl::fake([
            'minio.test:9000/examplebucket/app/a/one.txt' => function ($request) {
                return ('HEAD' === $request['method'])
                    ? Curl::response('', 200, ['Content-Length' => '5', 'Last-Modified' => 'Fri, 24 May 2013 00:00:00 GMT'])
                    : Curl::response('hello');
            },
            'minio.test:9000/examplebucket/app/missing.txt' => Curl::response('<Error><Code>NoSuchKey</Code></Error>', 404),
            'minio.test:9000/examplebucket/app/denied.txt' => Curl::response('<Error><Code>AccessDenied</Code><Message>Access Denied</Message></Error>', 403),
            'minio.test:9000/examplebucket/?*' => $listing,
            'minio.test:9000/examplebucket/*' => '',
        ]);

        $disk = Storage::disk('s3test');

        $this->assertTrue($disk->put('a/one.txt', 'hello', ['visibility' => 'public']));
        $this->assertEquals('hello', $disk->get('a/one.txt'));
        $this->assertTrue($disk->exists('a/one.txt'));
        $this->assertFalse($disk->exists('missing.txt'));
        $this->assertNull($disk->get('missing.txt'));
        $this->assertSame(5, $disk->size('a/one.txt'));
        $this->assertEquals(1369353600, $disk->modified('a/one.txt'));
        $this->assertEquals(['a/one.txt', 'a/b & c.txt'], $disk->files('a'));
        $this->assertTrue($disk->move('a/one.txt', 'a/two words.txt'));
        $this->assertEquals('https://cdn.example.com/app/a/two%20words.txt', $disk->url('a/two words.txt'));

        $put = Curl::recorded(function ($request) {
            return 'PUT' === $request['method'] && ! isset($request['headers']['x-amz-copy-source']);
        });
        $this->assertEquals('http://minio.test:9000/examplebucket/app/a/one.txt', $put[0]['url']);
        $this->assertEquals('public-read', $put[0]['headers']['x-amz-acl']);
        $this->assertEquals('hello', $put[0]['body']);
        $this->assertEquals(hash('sha256', 'hello'), $put[0]['headers']['x-amz-content-sha256']);
        $this->assertContains('SignedHeaders=content-type;host;x-amz-acl;x-amz-content-sha256;x-amz-date', $put[0]['headers']['authorization']);

        Curl::assert_sent(function ($request) {
            return 'PUT' === $request['method'] && isset($request['headers']['x-amz-copy-source'])
                && '/examplebucket/app/a/one.txt' === $request['headers']['x-amz-copy-source']
                && 'http://minio.test:9000/examplebucket/app/a/two%20words.txt' === $request['url'];
        });
        Curl::assert_sent(function ($request) {
            return 'DELETE' === $request['method'] && 'http://minio.test:9000/examplebucket/app/a/one.txt' === $request['url'];
        });
        Curl::assert_sent(function ($request) {
            return 'http://minio.test:9000/examplebucket/?list-type=2&prefix=app%2Fa%2F' === $request['url'];
        });

        try {
            $disk->get('denied.txt');
            $this->fail('error ignored');
        } catch (\Exception $e) {
            $this->assertEquals('S3 GET denied.txt failed: AccessDenied: Access Denied', $e->getMessage());
        }

        $this->setExpectedException('Exception', 'Path traversal');
        $disk->get('../secret.txt');
    }

    /**
     * The local disk keeps the same calls, under its root.
     *
     * @group system
     */
    public function testLocalDisk()
    {
        Config::set('storage.disks.localtest', ['driver' => 'local', 'root' => path('storage').'disktest', 'url' => 'https://example.com/files']);
        $disk = Storage::disk('localtest');

        $this->assertTrue($disk->put('a/b/one.txt', 'hello'));
        $this->assertEquals('hello', $disk->get('a/b/one.txt'));
        $this->assertSame(5, $disk->size('a/b/one.txt'));
        $this->assertTrue($disk->copy('a/b/one.txt', 'a/two.txt'));
        $this->assertEquals(['a/b/one.txt', 'a/two.txt'], $disk->files('a'));
        $this->assertTrue($disk->move('a/two.txt', 'three.txt'));
        $this->assertFalse($disk->exists('a/two.txt'));
        $this->assertTrue($disk->delete(['three.txt', 'nothing.txt']));
        $this->assertNull($disk->get('three.txt'));
        $this->assertEquals('https://example.com/files/a/b/one.txt', $disk->url('a/b/one.txt'));

        $this->setExpectedException('Exception', 'Undefined storage disk');
        Storage::disk('nowhere');
    }
}

class StorageDisksTestS3 extends \System\Storage\S3
{
    const TIME = 1369353600; // 2013-05-24T00:00:00Z

    protected function now()
    {
        return static::TIME;
    }
}
