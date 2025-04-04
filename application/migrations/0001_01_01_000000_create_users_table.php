<?php

defined('DS') or exit('No direct access.');

class Create_Users_Table
{
    /**
     * Buat perubahan di database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('auth.table'), function ($table) {
            $table->increments('id');
            $table->string('name', 191);
            $table->string('email', 191)->unique();
            $table->string('password', 60);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_resets', function ($table) {
            $table->increments('id');
            $table->string('email', 191)->index();
            $table->string('token', 191);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Urungkan perubahan di database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists(config('auth.table'));
        Schema::drop_if_exists('password_resets');
    }
}
