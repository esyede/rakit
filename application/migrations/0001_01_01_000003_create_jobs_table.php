<?php

defined('DS') or exit('No direct access.');

class Create_Jobs_Table
{
    /**
     * Buat perubahan di database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('job.table'), function ($table) {
            $table->increments('id');
            $table->string('name', 191)->index();
            $table->text('payloads');
            $table->timestamp('scheduled_at')->index();
            $table->timestamps();
        });

        Schema::create(config('job.failed_table'), function ($table) {
            $table->increments('id');
            $table->integer('job_id')->unsigned()->index();
            $table->string('name', 191)->index();
            $table->text('payloads');
            $table->text('exception');
            $table->timestamp('failed_at')->index();
        });
    }

    /**
     * Urungkan perubahan di database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists(config('job.table'));
        Schema::drop_if_exists(config('job.failed_table'));
    }
}
