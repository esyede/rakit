<?php

namespace System\Testing;

defined('DS') or exit('No direct access.');

use System\Auth;
use System\Cookie;
use System\Response;
use System\Worker\Bridge as WorkerBridge;

class Bridge extends WorkerBridge
{
    /**
     * The raw body of the request being made.
     *
     * @var string|null
     */
    public $content;

    /**
     * The user to log in for the request, NULL for none.
     *
     * @var mixed
     */
    public $user;

    /**
     * The last response: [response, prepared foundation response, body].
     *
     * @var array|null
     */
    public $sent;

    /**
     * {@inheritdoc}
     */
    public function wait_request(\Closure $handler)
    {
        throw new \LogicException('The testing bridge serves one request at a time.');
    }

    /**
     * {@inheritdoc}
     */
    protected function content()
    {
        return $this->content;
    }

    /**
     * Build the request state, then log the acting user in for this request only.
     */
    public function capture()
    {
        parent::capture();

        if (! is_null($this->user)) {
            $driver = Auth::driver();
            $driver->user = $this->user;
            $driver->token = (is_object($this->user) && method_exists($this->user, 'get_key'))
                ? $this->user->get_key()
                : (isset($this->user->id) ? $this->user->id : null);
        }
    }

    /**
     * Keep the response instead of sending it.
     *
     * @param \System\Response $response
     * @param int              $level
     */
    public function send_response(Response $response, $level)
    {
        $output = static::output($level);
        $foundation = $this->prepare($response);

        $this->sent = [$response, $foundation, $this->body($foundation, $output)];
        $this->done($response, $level);
    }

    /**
     * Get the cookies the response set, name => sealed value, and the names it removed.
     *
     * @return array
     */
    public static function cookies()
    {
        $set = [];
        $removed = [];

        foreach (Cookie::$jar as $name => $data) {
            if (0 !== (int) $data['expiration'] && (int) $data['expiration'] < time()) {
                $removed[] = $name;
            } else {
                $set[$name] = $data['value'];
            }
        }

        return [$set, $removed];
    }
}
