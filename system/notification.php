<?php

namespace System;

defined('DS') or exit('No direct access.');

abstract class Notification
{
    /**
     * The table of the 'database' channel.
     *
     * @var string
     */
    public static $database_table = 'notifications';

    /**
     * Contains the custom channels, name => closure($notifiable, $notification).
     *
     * @var array
     */
    protected static $registered_channels = [];

    /**
     * Get the channels to deliver on: 'mail', 'database', or a custom channel.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    abstract public function via($notifiable);

    /**
     * Build the mail of the 'mail' channel. The notifiable's address is added when
     * the mailable names no recipient.
     *
     * @param mixed $notifiable
     *
     * @return \System\Email\Mailable|null
     */
    public function to_mail($notifiable)
    {
        return null;
    }

    /**
     * Get the data the 'database' channel stores, as JSON.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function to_array($notifiable)
    {
        return [];
    }

    /**
     * Register a custom channel: Notification::channel('slack', function ($notifiable, $notification) { ... }).
     *
     * @param string   $name
     * @param \Closure $handler
     */
    public static function channel($name, \Closure $handler)
    {
        static::$registered_channels[$name] = $handler;
    }

    /**
     * Send a notification to one notifiable or several.
     *
     * @param mixed        $notifiables
     * @param Notification $notification
     */
    public static function send($notifiables, Notification $notification)
    {
        $notifiables = ($notifiables instanceof Collection) ? $notifiables->all() : $notifiables;
        $notifiables = is_array($notifiables) ? $notifiables : [$notifiables];

        foreach ($notifiables as $notifiable) {
            foreach ((array) $notification->via($notifiable) as $channel) {
                static::deliver($channel, $notifiable, $notification);
            }
        }
    }

    /**
     * Deliver on one channel.
     *
     * @param string       $channel
     * @param mixed        $notifiable
     * @param Notification $notification
     */
    protected static function deliver($channel, $notifiable, Notification $notification)
    {
        if (isset(static::$registered_channels[$channel])) {
            call_user_func(static::$registered_channels[$channel], $notifiable, $notification);
            return;
        }

        switch ($channel) {
            case 'mail':
                $mail = $notification->to_mail($notifiable);

                if (! is_null($mail)) {
                    if (empty($mail->to)) {
                        $mail->to(static::route($notifiable, 'mail'));
                    }

                    $mail->send();
                }

                return;

            case 'broadcast':
                $data = method_exists($notification, 'to_broadcast')
                    ? $notification->to_broadcast($notifiable)
                    : $notification->to_array($notifiable);

                Broadcast::event(static::route($notifiable, 'broadcast'), get_class($notification), $data);

                return;

            case 'database':
                Database::table(static::$database_table)->insert([
                    'id' => Str::uuid(),
                    'type' => get_class($notification),
                    'notifiable_type' => ($notifiable instanceof Database\Facile\Model) ? $notifiable->morph_class() : get_class($notifiable),
                    'notifiable_id' => ($notifiable instanceof Database\Facile\Model) ? $notifiable->get_key() : $notifiable->id,
                    'data' => json_encode($notification->to_array($notifiable)),
                    'read_at' => null,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                return;
        }

        throw new \Exception(sprintf('Undefined notification channel: %s', $channel));
    }

    /**
     * Get where a notifiable receives a channel: its route_notification_for_<channel>()
     * method, or else its email attribute for mail.
     *
     * @param mixed  $notifiable
     * @param string $channel
     *
     * @return mixed
     */
    protected static function route($notifiable, $channel)
    {
        if (method_exists($notifiable, 'route_notification_for_'.$channel)) {
            return $notifiable->{'route_notification_for_'.$channel}();
        }

        if ('mail' === $channel && isset($notifiable->email)) {
            return $notifiable->email;
        }

        if ('broadcast' === $channel && $notifiable instanceof Database\Facile\Model) {
            return 'private-'.strtolower(str_replace('\\', '.', get_class($notifiable))).'.'.$notifiable->get_key();
        }

        throw new \Exception(sprintf('%s has no route for the %s channel.', get_class($notifiable), $channel));
    }
}
