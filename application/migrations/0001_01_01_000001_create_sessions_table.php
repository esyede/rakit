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
        Schema::create(config('session.table'), function ($table) {
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
        Schema::drop_if_exists(config('session.table'));
    }
}
