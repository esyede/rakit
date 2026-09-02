<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

class File extends Driver implements Sweeper
{
    /**
     * Contains the path to the session files.
     *
     * @var string
     */
    private $path;

    /**
     * Constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
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
        if (! is_file($path = $this->path.$this->naming($id))) {
            return;
        }

        $session = @unserialize($this->unguard(file_get_contents($path)), ['allowed_classes' => false]);

        return is_array($session) ? $session : null;
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
        $path = $this->path.$this->naming($session['id']);
        $session = $this->guard(serialize($session));

        file_put_contents($path, $session, LOCK_EX);
    }

    /**
     * Delete the session with the given ID.
     *
     * @param string $id
     */
    public function delete($id)
    {
        if (is_file($path = $this->path.$this->naming($id))) {
            @unlink($path);
        }
    }

    /**
     * Delete all sessions that have expired from storage.
     *
     * @param int $expiration
     */
    public function sweep($expiration)
    {
        $files = glob($this->path.'*.session.php');

        if (false === $files) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $expiration) {
                @unlink($file);
            }
        }
    }

    /**
     * Format the session file name based on the given ID.
     *
     * @param string $id
     *
     * @return string
     */
    protected function naming($id)
    {
        return sha1((string) $id).'.session.php';
    }

    /**
     * Protect the session file from direct access via browser.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function guard($value)
    {
        return "<?php defined('DS') or exit('No direct access.');?>".$value;
    }

    /**
     * Remove the protection from the session file.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function unguard($value)
    {
        return str_replace("<?php defined('DS') or exit('No direct access.');?>", '', $value);
    }
}
