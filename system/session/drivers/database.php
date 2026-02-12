<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database\Connection;

class Database extends Driver
{
    /**
     * Contains the database connection instance.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Load the session based on the given ID.
     * If the session is not found, NULL will be returned.
     *
     * @param string $id
     *
     * @return array
     */
    public function load($id)
    {
        $session = $this->table()->find($id);

        if (!is_null($session)) {
            return [
                'id' => $session->id,
                'last_activity' => $session->last_activity,
                'data' => unserialize($session->data),
            ];
        }
    }

    /**
     * Save the session data.
     *
     * @param array $session
     * @param array $config
     * @param bool  $exists
     */
    public function save(array $session, array $config, $exists)
    {
        if ($exists) {
            $this->table()->where('id', '=', $session['id'])->update([
                'last_activity' => $session['last_activity'],
                'data' => serialize($session['data']),
            ]);
        } else {
            $this->table()->insert([
                'id' => $session['id'],
                'last_activity' => $session['last_activity'],
                'data' => serialize($session['data']),
            ]);
        }
    }

    /**
     * Delete the session with the given ID.
     *
     * @param string $id
     */
    public function delete($id)
    {
        $this->table()->delete($id);
    }

    /**
     * Get a new query builder for the session table.
     *
     * @return Query
     */
    private function table()
    {
        return $this->connection->table(Config::get('session.table'));
    }
}
