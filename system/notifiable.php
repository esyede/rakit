<?php

namespace System;

defined('DS') or exit('No direct access.');

trait Notifiable
{
    /**
     * Send a notification to this model.
     *
     * @param Notification $notification
     */
    public function notify(Notification $notification)
    {
        Notification::send($this, $notification);
    }

    /**
     * Get the query of this model's stored notifications, newest first.
     *
     * @return \System\Database\Query
     */
    public function notifications()
    {
        return $this->notification_query()->order_by('created_at', 'desc');
    }

    /**
     * Get the query of this model's unread notifications.
     *
     * @return \System\Database\Query
     */
    public function unread_notifications()
    {
        return $this->notification_query()->where_null('read_at')->order_by('created_at', 'desc');
    }

    /**
     * Mark this model's notifications as read: all of them, or the given IDs.
     *
     * @param array|string|null $ids
     *
     * @return int
     */
    public function mark_notifications_as_read($ids = null)
    {
        $query = $this->notification_query()->where_null('read_at');

        if (! is_null($ids)) {
            $query->where_in('id', (array) $ids);
        }

        return $query->update(['read_at' => Carbon::now()->format('Y-m-d H:i:s')]);
    }

    /**
     * Get the query of this model's notifications, unordered.
     *
     * @return \System\Database\Query
     */
    protected function notification_query()
    {
        return Database::table(Notification::$database_table)
            ->where('notifiable_type', '=', $this->morph_class())
            ->where('notifiable_id', '=', $this->get_key());
    }
}
