<?php

namespace System\Email;

defined('DS') or exit('No direct access.');

use System\Email;
use System\Job;
use System\View;

abstract class Mailable
{
    /**
     * The recipients, as [address, name] pairs.
     *
     * @var array
     */
    public $to = [];

    /**
     * The carbon copy recipients.
     *
     * @var array
     */
    public $cc = [];

    /**
     * The blind carbon copy recipients.
     *
     * @var array
     */
    public $bcc = [];

    /**
     * The sender, [address, name], or NULL for the configured one.
     *
     * @var array|null
     */
    public $from;

    /**
     * The reply-to addresses.
     *
     * @var array
     */
    public $reply_to = [];

    /**
     * The subject.
     *
     * @var string
     */
    public $subject = '';

    /**
     * The view rendered as the HTML body.
     *
     * @var string|null
     */
    public $view;

    /**
     * The data given to the view.
     *
     * @var array
     */
    public $data = [];

    /**
     * The files to attach: [path, name].
     *
     * @var array
     */
    public $attachments = [];

    /**
     * The email driver to send with, NULL for the default one.
     *
     * @var string|null
     */
    public $mailer;

    /**
     * Whether build() ran.
     *
     * @var bool
     */
    protected $built = false;

    /**
     * Set up the message: subject, view, data. Called once, before sending.
     */
    abstract public function build();

    /**
     * Add a recipient.
     *
     * @param string|array $address an address, or a list of them
     * @param string|null  $name
     *
     * @return $this
     */
    public function to($address, $name = null)
    {
        return $this->address('to', $address, $name);
    }

    /**
     * Add a carbon copy recipient.
     *
     * @param string|array $address
     * @param string|null  $name
     *
     * @return $this
     */
    public function cc($address, $name = null)
    {
        return $this->address('cc', $address, $name);
    }

    /**
     * Add a blind carbon copy recipient.
     *
     * @param string|array $address
     * @param string|null  $name
     *
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        return $this->address('bcc', $address, $name);
    }

    /**
     * Add a reply-to address.
     *
     * @param string|array $address
     * @param string|null  $name
     *
     * @return $this
     */
    public function reply_to($address, $name = null)
    {
        return $this->address('reply_to', $address, $name);
    }

    /**
     * Set the sender.
     *
     * @param string      $address
     * @param string|null $name
     *
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->from = [(string) $address, $name];
        return $this;
    }

    /**
     * Set the subject.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = (string) $subject;
        return $this;
    }

    /**
     * Set the view of the HTML body, and data for it.
     *
     * @param string $view
     * @param array  $data
     *
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = (string) $view;
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Give the view a value.
     *
     * @param string|array $key
     * @param mixed        $value
     *
     * @return $this
     */
    public function with($key, $value = null)
    {
        $this->data = is_array($key) ? array_merge($this->data, $key) : array_merge($this->data, [$key => $value]);
        return $this;
    }

    /**
     * Attach a file.
     *
     * @param string      $path
     * @param string|null $name
     *
     * @return $this
     */
    public function attach($path, $name = null)
    {
        $this->attachments[] = [(string) $path, $name];
        return $this;
    }

    /**
     * Send with another email driver.
     *
     * @param string $mailer
     *
     * @return $this
     */
    public function mailer($mailer)
    {
        $this->mailer = (string) $mailer;
        return $this;
    }

    /**
     * Render the HTML body.
     *
     * @return string
     */
    public function render()
    {
        $this->prepare();

        if (is_null($this->view)) {
            throw new \Exception(sprintf('%s::build() must set a view().', get_class($this)));
        }

        return View::make($this->view, $this->data)->render();
    }

    /**
     * Send the message now.
     *
     * @return bool
     */
    public function send()
    {
        $html = $this->render();
        $email = Email::driver($this->mailer);

        $email->reset();

        if (! is_null($this->from)) {
            $email->from($this->from[0], $this->from[1] ?: false);
        }

        foreach (['to' => 'to', 'cc' => 'cc', 'bcc' => 'bcc', 'reply_to' => 'replyto'] as $list => $method) {
            foreach ($this->{$list} as $address) {
                $email->{$method}($address[0], $address[1] ?: false);
            }
        }

        foreach ($this->attachments as $attachment) {
            $email->attach($attachment[0], false, null, null, $attachment[1]);
        }

        return $email->subject($this->subject)->html_body($html)->send();
    }

    /**
     * Send the message from a job, so the request does not wait for the mail server.
     * The mailable is serialized into the job: keep its data serializable (no closures).
     *
     * @param string $queue
     *
     * @return \System\Job\Pending
     */
    public function queue($queue = 'default')
    {
        $this->prepare();

        return Job::dispatch('rakit-mailable', ['mailable' => serialize($this)])->on_queue($queue);
    }

    /**
     * Send a mailable a queued job carries. Registered as the 'rakit-mailable' job.
     *
     * @param array $payload
     */
    public static function deliver(array $payload)
    {
        $mailable = isset($payload['mailable']) ? unserialize($payload['mailable']) : null;

        if (! ($mailable instanceof self)) {
            throw new \Exception('The queued job does not hold a mailable.');
        }

        $mailable->send();
    }

    /**
     * Run build() once.
     */
    protected function prepare()
    {
        if (! $this->built) {
            $this->built = true;
            $this->build();
        }
    }

    /**
     * Add addresses to a list.
     *
     * @param string       $list
     * @param string|array $address
     * @param string|null  $name
     *
     * @return $this
     */
    protected function address($list, $address, $name)
    {
        foreach ((array) $address as $key => $value) {
            $this->{$list}[] = is_string($key) ? [$key, $value] : [(string) $value, is_array($address) ? null : $name];
        }

        return $this;
    }
}
