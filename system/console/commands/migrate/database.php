<?php

namespace System\Console\Commands\Migrate;

defined('DS') or exit('No direct access.');

class Database
{
    /**
     * Log a migration into the migrations table.
     *
     * @param string $package
     * @param string $name
     * @param int    $batch
     *
     * @return void
     */
    public function log($package, $name, $batch)
    {
        $this->table()->insert([
            'package' => $package,
            'name' => $name,
            'batch' => $batch,
        ]);
    }

    /**
     * Delete a row from the migrations table.
     *
     * @param string $package
     * @param string $name
     *
     * @return void
     */
    public function delete($package, $name)
    {
        $this->table()
            ->where('package', $package)
            ->where('name', $name)
            ->delete();
    }

    /**
     * Get the last batch of migrations.
     *
     * @return array
     */
    public function last()
    {
        return $this->table()
            ->where('batch', $this->batch())
            ->order_by('name', 'desc')
            ->get();
    }

    /**
     * Get the list of migrations that have been run by a specific package.
     *
     * @param string $package
     *
     * @return array
     */
    public function ran($package)
    {
        return $this->table()
            ->where('package', $package)
            ->lists('name');
    }

    /**
     * Get the ID of the latest batch from the migrations table.
     *
     * @return int
     */
    public function batch()
    {
        return $this->table()->max('batch');
    }

    /**
     * Get an instance of the query builder for the migrations table.
     *
     * @return \System\Database\Query
     */
    protected function table()
    {
        return \System\Database::connection()->table('rakit_migrations');
    }
}
