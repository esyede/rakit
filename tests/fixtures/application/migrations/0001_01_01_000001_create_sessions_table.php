<?php

defined('DS') or exit('No direct access.');

class Create_Sessions_Table
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('session.table'), function ($table) {
            $table->string('id', 191)->primary('session_primary');
            $table->integer('last_activity');
            $table->longtext('data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists(config('session.table'));
    }
}
