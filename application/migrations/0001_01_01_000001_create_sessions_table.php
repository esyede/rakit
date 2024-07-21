<?php

defined('DS') or exit('No direct access.');

class Create_Sessions_Table
{
    /**
     * Buat perubahan di database.
     *
     * @return void
     */
    public function up()
    {
        $table = Config::get('session.table');
        Schema::create($table, function ($table) {
            $table->string('id', 191)->primary('session_primary');
            $table->integer('last_activity');
            $table->text('data');
        });
    }

    /**
     * Urungkan perubahan di database.
     *
     * @return void
     */
    public function down()
    {
        $table = Config::get('session.table');
        Schema::drop_if_exists($table);
    }
}
