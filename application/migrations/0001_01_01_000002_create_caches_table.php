<?php

defined('DS') or exit('No direct access.');

class Create_Caches_Table
{
    /**
     * Buat perubahan di database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('cache.database.table'), function ($table) {
            $table->string('key', 191)->primary('cache_primary');
            $table->longtext('value');
            $table->string('expiration', 30)->index();
        });
    }

    /**
     * Urungkan perubahan di database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists(config('cache.database.table'));
    }
}
