<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Str;
use System\Config;
use System\Storage;

class Key extends Command
{
    /**
     * Path ke file konfigurasi.
     *
     * @var string
     */
    protected $path;

    /**
     * Buta instance baru.
     *
     * @return void
     */
    public function __construct()
    {
        $this->path = path('app').'config'.DS.'application.php';
    }

    /**
     * Isi secret key aplikasi (jika belum terisi).
     *
     * @return void
     */
    public function generate()
    {
        $key = (string) Config::get('application.key', '');

        if (mb_strlen(trim($key), '8bit') < 32) {
            try {
                $data = Storage::get($this->path);
                $key = Str::random(32);
                $regex = "/(('|\")key('|\"))\h*=>\h*(\'|\")\s?(\'|\")?.*/i";

                if (false !== preg_match($regex, $data)) {
                    $data = preg_replace($regex, "'key' => '".$key."',", $data);
                    Storage::put($this->path, $data);
                }

                Config::set('application.key', $key);

                echo 'Application key set successfully.';
            } catch (\Throwable $e) {
                echo 'Failed to update application key!'.PHP_EOL;
                echo $e->getMessage();
            } catch (\Exception $e) {
                echo 'Failed to update application key!'.PHP_EOL;
                echo $e->getMessage();
            }
        } else {
            echo 'Application key already exists!';
        }
    }
}
