<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Job extends Event
{
    /**
     * Tambahkan sebuah job.
     *
     * @param string      $name
     * @param array       $payloads
     * @param string|null $scheduled_at
     *
     * @return bool
     */
    public static function add($name, array $payloads = [], $scheduled_at = null)
    {
        $config = Config::get('job');
        $payloads = serialize($payloads);
        $scheduled_at = Date::make($scheduled_at)->format('Y-m-d H:i:s');

        Database::table($config['table'])->insert(compact('name', 'payloads', 'scheduled_at'));

        $this->log(sprintf('Job added: %s', $name));
        return true;
    }

    /**
     * Hapus job berdasarkan nama.
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete($name)
    {
        $config = Config::get('job');

        $jobs = Database::table($config['table'])
            ->where('name', $name)
            ->get('id');

        if (empty($jobs)) {
            $this->log(sprintf('No job found with this name: %s', $name));
            return true;
        }

        foreach ($jobs as $job) {
            Database::table($config['table'])->delete($job->id);
        }

        $this->log(sprintf('Jobs deleted: %s', $name));
        return true;
    }

    /**
     * Jalankan antrian job di database.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function run($name)
    {
        $config = Config::get('job');

        if (! $config['enabled']) {
            $this->log('Job is not enabled', 'error');
            return false;
        }

        if (empty($name)) {
            $this->log('No job given', 'error');
            return false;
        }

        if ($config['cli_only'] && ! Request::cli()) {
            $this->log('Job is set to only be executed from the CLI', 'error');
            return false;
        }

        $this->log('Job started!');

        $jobs = Database::table($config['table'])
            ->where('name', $name)
            ->where('executed_at', '<=', Date::now())
            ->where('scheduled_at', '<=', Date::now())
            ->order_by('created_at', 'ASC')
            ->take($config['max_job'])
            ->get();

        foreach ($jobs as $job) {
            try {
                Event::fire($job->name, unserialize($job->payloads));
                Database::table($config['table'])
                    ->where('id', $job->id)
                    ->update(['executed_at' => Date::now()]);

                $this->log(sprintf('Job executed: %s - #%s', $job->name, $job->id));
            } catch (\Throwable $e) {
                $this->log(sprintf(
                    'Job failed: %s - #%s. Reason: %s',
                    $job->name, $job->id, $e->getMessage()
                ));
                return false;
            } catch (\Exception $e) {
                $this->log(sprintf(
                    'Job failed: %s - #%s. Reason: %s',
                    $job->name, $job->id, $e->getMessage()
                ));
                return false;
            }
        }

        $this->log('Job ended!');
        return true;
    }

    /**
     * Jalankan semua job di database.
     *
     * @return bool
     */
    public static function runall()
    {
        $config = Config::get('job');

        if (! $config['enabled']) {
            $this->log('Job is not enabled', 'error');
            return false;
        }

        if ($config['cli_only'] && ! Request::cli()) {
            $this->log('Job is set to only be executed from the CLI', 'error');
            return false;
        }

        $this->log('Job started!');

        $jobs = Database::table($config['table'])
            ->where('executed_at', '<=', Date::now())
            ->where('scheduled_at', '<=', Date::now())
            ->order_by('created_at', 'ASC')
            ->take($config['max_job'])
            ->get();

        foreach ($jobs as $job) {
            try {
                Event::fire($job->name, unserialize($job->payloads));
                Database::table($config['table'])
                    ->where('id', $job->id)
                    ->update(['executed_at' => Date::now()]);

                $this->log(sprintf('Job executed: %s - #%s', $job->name, $job->id));
            } catch (\Throwable $e) {
                $this->log(sprintf(
                    'Job failed: %s - #%s. Reason: %s',
                    $job->name, $job->id, $e->getMessage()
                ));
                return false;
            } catch (\Exception $e) {
                $this->log(sprintf(
                    'Job failed: %s - #%s. Reason: %s',
                    $job->name, $job->id, $e->getMessage()
                ));
                return false;
            }
        }

        $this->log('Job ended!');
        return true;
    }


    private function log($message, $type = 'info')
    {
        $config = Config::get('job');

        if ($config['logging']) {
            Log::{$type}($message);
        }

        if (Request::cli()) {
            echo '['.Date::now().'] ['.strtoupper($type).'] '.$message.PHP_EOL;
        }
    }
}
