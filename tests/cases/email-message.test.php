<?php

defined('DS') or exit('No direct access.');

use System\Email;
use System\Config;

/**
 * Covers the message builder shared by every email driver: recipients,
 * headers, bodies, attachments and the header sanitising.
 */
class EmailMessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Email::reset();

        Config::set('email', [
            'driver' => 'log',
            'as_html' => null,
            'encoding' => '8bit',
            'encode_headers' => false,
            'priority' => Email::NORMAL,
            'from' => ['email' => 'noreply@example.com', 'name' => 'Administrator'],
            'validate' => true,
            'attachify' => true,
            'alternatify' => true,
            'force_mixed' => false,
            'wordwrap' => 76,
            'newline' => "\n",
            'return_path' => false,
            'strip_comments' => true,
            'protocol_replacement' => false,
        ]);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Email::reset();
    }

    /**
     * Build the message the driver would transmit.
     *
     * @param \System\Email\Drivers\Driver $driver
     *
     * @return array
     */
    protected function build($driver)
    {
        $send = new \ReflectionMethod('\System\Email\Drivers\Driver', 'send');
        PHP_VERSION_ID < 80100 && $send->setAccessible(true);
        $send->invoke($driver, false);

        $build = new \ReflectionMethod('\System\Email\Drivers\Driver', 'build');
        PHP_VERSION_ID < 80100 && $build->setAccessible(true);

        return $build->invoke($driver, false);
    }

    /**
     * Read back the headers the driver prepared.
     *
     * @param \System\Email\Drivers\Driver $driver
     *
     * @return array
     */
    protected function headers($driver)
    {
        $property = new \ReflectionProperty('\System\Email\Drivers\Driver', 'headers');
        PHP_VERSION_ID < 80100 && $property->setAccessible(true);

        return $property->getValue($driver);
    }

    /**
     * A driver with the common fields already filled in.
     *
     * @return \System\Email\Drivers\Driver
     */
    protected function driver()
    {
        return Email::driver('log')
            ->from('noreply@example.com', 'Administrator')
            ->to('budi@example.com', 'Budi')
            ->subject('Halo')
            ->body('Isi pesan');
    }

    // -------------------------------------------------------------------------
    // Headers
    // -------------------------------------------------------------------------

    /**
     * The standard headers are present.
     *
     * @group system
     */
    public function testStandardHeaders()
    {
        $driver = $this->driver();
        $this->build($driver);

        $headers = $this->headers($driver);

        $this->assertEquals('"Budi" <budi@example.com>', $headers['To']);
        $this->assertEquals('Halo', $headers['Subject']);
        $this->assertEquals('"Administrator" <noreply@example.com>', $headers['From']);
        $this->assertEquals('1.0', $headers['MIME-Version']);
        $this->assertEquals('Rakit', $headers['X-Mailer']);
        $this->assertArrayHasKey('Date', $headers);
        $this->assertArrayHasKey('Message-ID', $headers);
    }

    /**
     * Custom headers end up in the message head.
     *
     * @group system
     */
    public function testCustomHeaders()
    {
        $driver = $this->driver()->header('X-Campaign', 'promo');
        $message = $this->build($driver);

        $this->assertContains('X-Campaign: promo', $message['header']);
    }

    /**
     * Several custom headers can be given at once.
     *
     * @group system
     */
    public function testCustomHeadersAsArray()
    {
        $driver = $this->driver()->header(['X-One' => '1', 'X-Two' => '2']);
        $message = $this->build($driver);

        $this->assertContains('X-One: 1', $message['header']);
        $this->assertContains('X-Two: 2', $message['header']);
    }

    /**
     * The priority is reflected in the X-Priority header.
     *
     * @group system
     */
    public function testPriority()
    {
        $driver = $this->driver()->priority(Email::HIGH);
        $this->build($driver);

        $headers = $this->headers($driver);
        $this->assertEquals(Email::HIGH, $headers['X-Priority']);
    }

    // -------------------------------------------------------------------------
    // Header injection
    // -------------------------------------------------------------------------

    /**
     * A newline in the subject must not be able to append a header.
     *
     * @group system
     */
    public function testSubjectCannotInjectHeaders()
    {
        $driver = $this->driver()->subject("Halo\r\nBcc: korban@example.com");
        $message = $this->build($driver);

        $headers = $this->headers($driver);
        $this->assertNotContains("\n", $headers['Subject']);
        $this->assertNotContains("\r", $headers['Subject']);

        // The value is still there, but it can no longer start a header of its own.
        $this->assertNotContains("\nBcc:", $message['header']);
    }

    /**
     * Neither may a recipient address or its display name.
     *
     * @group system
     */
    public function testRecipientCannotInjectHeaders()
    {
        $driver = Email::driver('log')
            ->from('noreply@example.com')
            ->to("budi@example.com\r\nBcc: korban@example.com", "Budi\r\nX-Evil: 1")
            ->subject('Halo')
            ->body('Isi');

        $message = $this->build($driver);

        $this->assertNotContains("\nBcc: korban@example.com", $message['header']);
        $this->assertNotContains("\nX-Evil: 1", $message['header']);
    }

    /**
     * A custom header value cannot smuggle another header either.
     *
     * @group system
     */
    public function testCustomHeaderCannotInject()
    {
        $driver = $this->driver()->header('X-Campaign', "promo\r\nBcc: korban@example.com");
        $message = $this->build($driver);

        $this->assertNotContains("\nBcc: korban@example.com", $message['header']);
    }

    /**
     * The from address is sanitised as well.
     *
     * @group system
     */
    public function testFromCannotInjectHeaders()
    {
        $driver = Email::driver('log')
            ->from("noreply@example.com\r\nBcc: korban@example.com", 'App')
            ->to('budi@example.com')
            ->subject('Halo')
            ->body('Isi');

        $message = $this->build($driver);

        $this->assertNotContains("\nBcc: korban@example.com", $message['header']);
    }

    /**
     * A quote inside a display name is escaped instead of closing the string.
     *
     * @group system
     */
    public function testDisplayNameQuoteIsEscaped()
    {
        $driver = Email::driver('log')
            ->from('noreply@example.com')
            ->to('budi@example.com', 'Budi "The Boss"')
            ->subject('Halo')
            ->body('Isi');

        $this->build($driver);
        $headers = $this->headers($driver);

        $this->assertEquals('"Budi \\"The Boss\\"" <budi@example.com>', $headers['To']);
    }

    // -------------------------------------------------------------------------
    // Recipients
    // -------------------------------------------------------------------------

    /**
     * Several recipients are joined with a comma.
     *
     * @group system
     */
    public function testMultipleRecipients()
    {
        $driver = Email::driver('log')
            ->from('noreply@example.com')
            ->to(['a@example.com' => 'A', 'b@example.com' => 'B'])
            ->subject('Halo')
            ->body('Isi');

        $this->build($driver);
        $headers = $this->headers($driver);

        $this->assertEquals('"A" <a@example.com>, "B" <b@example.com>', $headers['To']);
    }

    /**
     * Cc and reply-to get their own headers, bcc is stripped from the message
     * that goes on the wire.
     *
     * @group system
     */
    public function testCcBccAndReplyTo()
    {
        $driver = $this->driver()
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyto('reply@example.com');

        $message = $this->build($driver);
        $headers = $this->headers($driver);

        $this->assertEquals('cc@example.com', $headers['Cc']);
        $this->assertEquals('bcc@example.com', $headers['Bcc']);
        $this->assertEquals('reply@example.com', $headers['Reply-To']);

        // build(true) is the variant used when the recipients are handed to the
        // transport separately.
        $build = new \ReflectionMethod('\System\Email\Drivers\Driver', 'build');
        PHP_VERSION_ID < 80100 && $build->setAccessible(true);
        $without = $build->invoke($driver, true);

        $this->assertContains('Cc: cc@example.com', $message['header']);
        $this->assertNotContains('Bcc: bcc@example.com', $without['header']);
    }

    /**
     * Sending without any recipient is refused.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testSendWithoutRecipientThrows()
    {
        Email::driver('log')->from('noreply@example.com')->subject('Halo')->body('Isi')->send();
    }

    /**
     * An invalid address is reported when validation is on.
     *
     * @group system
     */
    public function testInvalidAddressIsReported()
    {
        $driver = Email::driver('log')
            ->from('noreply@example.com')
            ->to('bukan-email')
            ->subject('Halo')
            ->body('Isi');

        try {
            $driver->send(true);
            $this->fail('Expected the invalid address to be refused.');
        } catch (\Exception $e) {
            $this->assertContains('did not pass validation', $e->getMessage());
        }

        $invalid = $driver->get_invalid_addresses();
        $this->assertArrayHasKey('to', $invalid);
    }

    // -------------------------------------------------------------------------
    // Bodies
    // -------------------------------------------------------------------------

    /**
     * A plain body is carried as text/plain.
     *
     * @group system
     */
    public function testPlainBody()
    {
        $driver = $this->driver();
        $message = $this->build($driver);

        $this->assertContains('text/plain', $message['header']);
        $this->assertContains('Isi pesan', $message['body']);
    }

    /**
     * An HTML body gets a plain text alternative.
     *
     * @group system
     */
    public function testHtmlBodyGetsAlternative()
    {
        $driver = Email::driver('log')
            ->from('noreply@example.com')
            ->to('budi@example.com')
            ->subject('Halo')
            ->html_body('<h1>Judul</h1><p>Isi <b>tebal</b></p>');

        $message = $this->build($driver);

        $this->assertContains('multipart/alternative', $message['header']);
        $this->assertContains('<h1>Judul</h1>', $message['body']);
        $this->assertContains('text/plain', $message['body']);
        $this->assertContains('Judul', $message['body']);
    }

    /**
     * An explicit alternative body wins over the generated one.
     *
     * @group system
     */
    public function testExplicitAlternativeBody()
    {
        $driver = Email::driver('log')
            ->from('noreply@example.com')
            ->to('budi@example.com')
            ->subject('Halo')
            ->html_body('<p>Versi HTML</p>')
            ->alt_body('Versi teks biasa');

        $message = $this->build($driver);

        $this->assertContains('Versi teks biasa', $message['body']);
    }

    // -------------------------------------------------------------------------
    // Attachments
    // -------------------------------------------------------------------------

    /**
     * A string attachment is embedded, base64 encoded.
     *
     * @group system
     */
    public function testStringAttachment()
    {
        $driver = $this->driver()->string_attach('isi berkas', 'catatan.txt');
        $message = $this->build($driver);

        // 'plain_attach' is carried as multipart/related unless force_mixed is on.
        $this->assertContains('multipart/related', $message['header']);
        $this->assertContains('catatan.txt', $message['body']);
        $this->assertContains('Content-Disposition: attachment', $message['body']);
        $this->assertContains('text/plain', $message['body']);
        $this->assertContains(
            base64_encode('isi berkas'),
            preg_replace('/\s+/', '', $message['body'])
        );
    }

    /**
     * With force_mixed the same message is carried as multipart/mixed.
     *
     * @group system
     */
    public function testForceMixedAttachment()
    {
        Config::set('email.force_mixed', true);

        $driver = $this->driver()->string_attach('isi berkas', 'catatan.txt');
        $message = $this->build($driver);

        $this->assertContains('multipart/mixed', $message['header']);
    }

    /**
     * A file attachment reads the file from disk.
     *
     * @group system
     */
    public function testFileAttachment()
    {
        $path = path('storage') . 'lampiran-probe.txt';
        file_put_contents($path, 'isi lampiran');

        try {
            $driver = $this->driver()->attach($path);
            $message = $this->build($driver);

            $this->assertContains('lampiran-probe.txt', $message['body']);
            $this->assertContains(
                base64_encode('isi lampiran'),
                preg_replace('/\s+/', '', $message['body'])
            );
        } catch (\Exception $e) {
            @unlink($path);
            throw $e;
        }

        @unlink($path);
    }

    /**
     * Attaching a file that is not there is refused.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testAttachingMissingFileThrows()
    {
        $this->driver()->attach(path('storage') . 'berkas-yang-tidak-ada.txt');
    }

    // -------------------------------------------------------------------------
    // Misc
    // -------------------------------------------------------------------------

    /**
     * reset() clears the recipients and attachments.
     *
     * @group system
     */
    public function testReset()
    {
        $driver = $this->driver()->cc('cc@example.com')->string_attach('x', 'x.txt');
        $driver->reset();

        $driver->to('lain@example.com');
        $this->build($driver);

        $headers = $this->headers($driver);
        $this->assertEquals('lain@example.com', $headers['To']);
        $this->assertArrayNotHasKey('Cc', $headers);
    }

    /**
     * The log driver actually reports success.
     *
     * @group system
     */
    public function testLogDriverSends()
    {
        $this->assertTrue($this->driver()->send(false));
    }
}
