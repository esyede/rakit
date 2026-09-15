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

        // send() drops the message once it is on the wire, so the parts it was
        // built from are put back to let the message be rebuilt for inspection.
        $message = [];

        foreach (['to', 'cc', 'bcc', 'replyto', 'attachments', 'extras'] as $part) {
            $property = new \ReflectionProperty('\System\Email\Drivers\Driver', $part);
            PHP_VERSION_ID < 80100 && $property->setAccessible(true);
            $message[$part] = [$property, $property->getValue($driver)];
        }

        $send->invoke($driver, false);

        foreach ($message as $part) {
            $part[0]->setValue($driver, $part[1]);
        }

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
            ->subject('Hello')
            ->body('Message body');
    }

    /**
     * A driver that keeps the message it was asked to transmit.
     *
     * @return \System\Email\Drivers\Driver
     */
    protected function probe()
    {
        EmailProbeDriver::$sent = ['header' => '', 'body' => ''];
        Email::reset();

        Email::extend('probe', function () {
            return new EmailProbeDriver(Config::get('email'));
        });

        return Email::driver('probe')->from('noreply@example.com');
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
        $this->assertEquals('Hello', $headers['Subject']);
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
        $driver = $this->driver()->subject("Hello\r\nBcc: victim@example.com");
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
            ->to("budi@example.com\r\nBcc: victim@example.com", "Budi\r\nX-Evil: 1")
            ->subject('Hello')
            ->body('Body');

        $message = $this->build($driver);

        $this->assertNotContains("\nBcc: victim@example.com", $message['header']);
        $this->assertNotContains("\nX-Evil: 1", $message['header']);
    }

    /**
     * A custom header value cannot smuggle another header either.
     *
     * @group system
     */
    public function testCustomHeaderCannotInject()
    {
        $driver = $this->driver()->header('X-Campaign', "promo\r\nBcc: victim@example.com");
        $message = $this->build($driver);

        $this->assertNotContains("\nBcc: victim@example.com", $message['header']);
    }

    /**
     * The from address is sanitised as well.
     *
     * @group system
     */
    public function testFromCannotInjectHeaders()
    {
        $driver = Email::driver('log')
            ->from("noreply@example.com\r\nBcc: victim@example.com", 'App')
            ->to('budi@example.com')
            ->subject('Hello')
            ->body('Body');

        $message = $this->build($driver);

        $this->assertNotContains("\nBcc: victim@example.com", $message['header']);
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
            ->subject('Hello')
            ->body('Body');

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
            ->subject('Hello')
            ->body('Body');

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
        Email::driver('log')->from('noreply@example.com')->subject('Hello')->body('Body')->send();
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
            ->to('not-an-email')
            ->subject('Hello')
            ->body('Body');

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
        $this->assertContains('Message body', $message['body']);
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
            ->subject('Hello')
            ->html_body('<h1>Title</h1><p>Body <b>bold</b></p>');

        $message = $this->build($driver);

        $this->assertContains('multipart/alternative', $message['header']);
        $this->assertContains('<h1>Title</h1>', $message['body']);
        $this->assertContains('text/plain', $message['body']);
        $this->assertContains('Title', $message['body']);
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
            ->subject('Hello')
            ->html_body('<p>HTML version</p>')
            ->alt_body('Plain text version');

        $message = $this->build($driver);

        $this->assertContains('Plain text version', $message['body']);
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
        $driver = $this->driver()->string_attach('file content', 'notes.txt');
        $message = $this->build($driver);

        // 'plain_attach' is carried as multipart/related unless force_mixed is on.
        $this->assertContains('multipart/related', $message['header']);
        $this->assertContains('notes.txt', $message['body']);
        $this->assertContains('Content-Disposition: attachment', $message['body']);
        $this->assertContains('text/plain', $message['body']);
        $this->assertContains(
            base64_encode('file content'),
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

        $driver = $this->driver()->string_attach('file content', 'notes.txt');
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
        $path = path('storage') . 'attachment-probe.txt';
        file_put_contents($path, 'attachment content');

        try {
            $driver = $this->driver()->attach($path);
            $message = $this->build($driver);

            $this->assertContains('attachment-probe.txt', $message['body']);
            $this->assertContains(
                base64_encode('attachment content'),
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
        $this->driver()->attach(path('storage') . 'missing-file.txt');
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

        $driver->to('other@example.com');
        $this->build($driver);

        $headers = $this->headers($driver);
        $this->assertEquals('other@example.com', $headers['To']);
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

    // -------------------------------------------------------------------------
    // Regression
    // -------------------------------------------------------------------------

    /**
     * The recipients of one email do not become recipients of the next.
     *
     * @group system
     */
    public function testK10RecipientsDoNotSurviveTheSend()
    {
        $driver = $this->probe();
        $driver->to('first@example.com')->subject('First')->body('First body')->send();
        $driver->to('second@example.com')->subject('Second')->body('Second body')->send();

        $this->assertContains('To: second@example.com', EmailProbeDriver::$sent['header']);
        $this->assertNotContains('first@example.com', EmailProbeDriver::$sent['header']);
    }

    /**
     * The custom headers of one email do not survive into the next.
     *
     * @group system
     */
    public function testK10CustomHeadersDoNotSurviveTheSend()
    {
        $driver = $this->probe();
        $driver->to('first@example.com')->header('X-Campaign', 'promo')->subject('First')->body('Body')->send();
        $driver->to('second@example.com')->subject('Second')->body('Body')->send();

        $this->assertNotContains('X-Campaign', EmailProbeDriver::$sent['header']);
    }

    /**
     * The attachments of one email do not survive into the next.
     *
     * @group system
     */
    public function testK10AttachmentsDoNotSurviveTheSend()
    {
        $driver = $this->probe();
        $driver->to('first@example.com')->subject('First')->body('Body')
            ->string_attach('secret content', 'secret.txt')
            ->send();
        $driver->to('second@example.com')->subject('Second')->body('Body')->send();

        $this->assertNotContains('secret.txt', EmailProbeDriver::$sent['body']);
    }

    /**
     * A newline in the attachment name cannot open a header of its own.
     *
     * @group system
     */
    public function testK11AttachmentNameCannotInjectHeaders()
    {
        $path = path('storage') . 'attachment-evil.txt';
        file_put_contents($path, 'content');

        try {
            $driver = $this->probe();
            $driver->to('budi@example.com')->subject('Hello')->body('Body')
                ->attach($path, false, null, null, "notes.txt\"\r\nContent-Type: text/html")
                ->send();

            $body = EmailProbeDriver::$sent['body'];

            preg_match('/name="([^"]*)"/', $body, $matches);

            $this->assertNotRegExp('/[\r\n"<>]/', $matches[1]);
            $this->assertNotContains("\nContent-Type: text/html", $body);
        } catch (\Exception $e) {
            @unlink($path);
            throw $e;
        }

        @unlink($path);
    }

    /**
     * A newline in the content id cannot open a header of its own.
     *
     * @group system
     */
    public function testK11ContentIdCannotInjectHeaders()
    {
        $driver = $this->probe();
        $driver->to('budi@example.com')->subject('Hello')->html_body('<p>Body</p>', false, false)
            ->string_attach('image', 'logo.png', "abc\r\nX-Injected: yes", true)
            ->send();

        $body = EmailProbeDriver::$sent['body'];

        $this->assertContains('Content-ID: <abcX-Injected: yes>', $body);
        $this->assertNotContains("\nX-Injected", $body);
    }

    /**
     * An html email with an inline image and an attachment, but no alternative
     * body, has a content type of its own.
     *
     * @group system
     */
    public function testT23HtmlWithInlineAndAttachmentIsSent()
    {
        $logo = path('storage') . 'attachment-inline.txt';
        $file = path('storage') . 'attachment-plain.txt';

        file_put_contents($logo, 'image');
        file_put_contents($file, 'attachment');

        try {
            $driver = $this->probe();
            $sent = $driver->to('budi@example.com')->subject('Hello')
                ->html_body('<img src="' . $logo . '" />', false)
                ->attach($file)
                ->send();

            $this->assertTrue($sent);
            $this->assertContains('multipart/mixed', EmailProbeDriver::$sent['header']);
            $this->assertContains('attachment-plain.txt', EmailProbeDriver::$sent['body']);
            $this->assertContains('Content-Disposition: inline', EmailProbeDriver::$sent['body']);
        } catch (\Exception $e) {
            @unlink($logo);
            @unlink($file);
            throw $e;
        }

        @unlink($logo);
        @unlink($file);
    }

    /**
     * A single part message says how its body was encoded.
     *
     * @group system
     */
    public function testT24EncodingIsAnnounced()
    {
        Config::set('email.encoding', 'base64');

        $driver = $this->probe();
        $driver->to('budi@example.com')->subject('Hello')->body('Report body')->send();

        $this->assertContains('Content-Transfer-Encoding: base64', EmailProbeDriver::$sent['header']);
        $this->assertContains(
            base64_encode('Report body'),
            preg_replace('/\s+/', '', EmailProbeDriver::$sent['body'])
        );

        Config::set('email.encoding', 'quoted-printable');

        $driver = $this->probe();
        $driver->to('budi@example.com')->subject('Hello')->body('Coffee caf=e')->send();

        $this->assertContains(
            'Content-Transfer-Encoding: quoted-printable',
            EmailProbeDriver::$sent['header']
        );
    }

    /**
     * Stripping comments leaves the markup between two of them alone.
     *
     * @group system
     */
    public function testT25CommentsAreStrippedWithoutEatingTheMarkup()
    {
        $driver = $this->probe();
        $driver->to('budi@example.com')->subject('Hello')
            ->html_body('<!--opening-note--><p>Important text</p><!--closing-note-->', false, false)
            ->send();

        $this->assertContains('<p>Important text</p>', EmailProbeDriver::$sent['body']);
        $this->assertNotContains('opening-note', EmailProbeDriver::$sent['body']);
        $this->assertNotContains('closing-note', EmailProbeDriver::$sent['body']);
    }

    /**
     * A transport that reports a failure is not reported as a success.
     *
     * @group system
     */
    public function testT26SendReportsATransportFailure()
    {
        Email::extend('failing', function () {
            return new EmailFailingDriver(Config::get('email'));
        });

        $driver = Email::driver('failing')->from('noreply@example.com');

        $this->assertFalse($driver->to('budi@example.com')->subject('Hello')->body('Body')->send());
    }

    /**
     * A driver that returns nothing is still taken as a success.
     *
     * @group system
     */
    public function testT26SendAcceptsADriverThatReturnsNothing()
    {
        Email::extend('silent', function () {
            return new EmailSilentDriver(Config::get('email'));
        });

        $driver = Email::driver('silent')->from('noreply@example.com');

        $this->assertTrue($driver->to('budi@example.com')->subject('Hello')->body('Body')->send());
    }

    /**
     * Sending twice does not encode the body twice.
     *
     * @group system
     */
    public function testS24BodyIsNotEncodedTwice()
    {
        Config::set('email.encoding', 'base64');

        $driver = $this->probe();
        $driver->to('first@example.com')->subject('Hello')->body('Original body')->send();
        $first = EmailProbeDriver::$sent['body'];

        $driver->to('second@example.com')->send();

        $this->assertEquals(trim($first), trim(EmailProbeDriver::$sent['body']));
        $this->assertContains(
            base64_encode('Original body'),
            preg_replace('/\s+/', '', EmailProbeDriver::$sent['body'])
        );
    }

    /**
     * A protocol relative url is rewritten with the configured scheme.
     *
     * @group system
     */
    public function testS25ProtocolReplacementUsesTheScheme()
    {
        Config::set('email.protocol_replacement', 'https://');

        $driver = $this->probe();
        $driver->to('budi@example.com')->subject('Hello')
            ->html_body('<img src="//cdn.example.com/logo.png" />', false)
            ->send();

        $this->assertContains('https://cdn.example.com/logo.png', EmailProbeDriver::$sent['body']);
    }

    /**
     * The alternative body is not repeated behind the inline parts.
     *
     * @group system
     */
    public function testS26InlinePartsAreNotFollowedByTheAltBody()
    {
        $logo = path('storage') . 'attachment-inline.txt';
        $file = path('storage') . 'attachment-plain.txt';

        file_put_contents($logo, 'image');
        file_put_contents($file, 'attachment');

        try {
            $driver = $this->probe();
            $driver->to('budi@example.com')->subject('Hello')
                ->html_body('<p>HTML body</p><img src="' . $logo . '" />')
                ->alt_body('Alternative summary')
                ->attach($file)
                ->send();

            $body = EmailProbeDriver::$sent['body'];

            $this->assertContains('multipart/related', $body);
            $this->assertEquals(1, substr_count($body, 'Alternative summary'));
        } catch (\Exception $e) {
            @unlink($logo);
            @unlink($file);
            throw $e;
        }

        @unlink($logo);
        @unlink($file);
    }
}

/**
 * Keeps the message instead of putting it on a wire.
 */
class EmailProbeDriver extends \System\Email\Drivers\Driver
{
    /**
     * The message the last send() handed to the transport.
     *
     * @var array
     */
    public static $sent = ['header' => '', 'body' => ''];

    /**
     * Starts the email transmission.
     *
     * @return bool
     */
    protected function transmit()
    {
        static::$sent = $this->build();
        return true;
    }
}

/**
 * Reports that the transport refused the message.
 */
class EmailFailingDriver extends \System\Email\Drivers\Driver
{
    /**
     * Starts the email transmission.
     *
     * @return bool
     */
    protected function transmit()
    {
        return false;
    }
}

/**
 * A third party driver that returns nothing at all.
 */
class EmailSilentDriver extends \System\Email\Drivers\Driver
{
    /**
     * Starts the email transmission.
     */
    protected function transmit()
    {
        // ..
    }
}
