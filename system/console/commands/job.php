<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Database\Schema;
use System\Package;
use System\Config;
use System\Log;

class Job extends Command
{
    /**
     * Jalankan satu atau beberapa job berdasarkan nama.
     *
     * @param string|array $names
     *
     * @return void
     */
    public function run($names = [])
    {
        $config = Config::get('job');
        $names = is_array($names) ? $names : [$names];

        if (empty($names)) {
            if (Request::cli()) {
                echo 'Please give at least one job name to execute!'.PHP_EOL;
                exit;
            } else {
                if ($config['logging']) {
                    Log::error('Please give at least one job name to execute!');
                }

                return false;
            }
        }

        foreach ($names as $name) {
            Job::run($name);
        }
    }

    /**
     * Jalankan semua job.
     *
     * @return void
     */
    public function runall()
    {
        Job::runall();
    }

    /**
     * Buat tabel job.
     *
     * @return void
     */
    public function table()
    {
        $config = Config::get('job');

        if (Schema::has_table($config['table'])) {
            throw new \Exception(sprintf('The job table already exists: %s', $config['table']));
        }

        Schema::create($config['table'], function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->index();
            $table->text('payloads');
            $table->timestamp('executed_at');
            $table->timestamp('scheduled_at');
            $table->timestamps();
        });
    }
}
