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
            $table->string('queue', 50)->defaults('default')->index();
            $table->longtext('payloads');
            $table->boolean('without_overlapping')->defaults(false);
            $table->timestamp('scheduled_at')->index();
            $table->timestamps();
            $table->index(['name', 'queue']);
            $table->index(['queue', 'scheduled_at']);
        });

        Schema::create(config('job.failed_table'), function ($table) {
            $table->increments('id');
            $table->integer('job_id')->unsigned()->nullable()->index();
            $table->string('name', 191)->index();
            $table->string('queue', 50)->defaults('default')->index();
            $table->longtext('payloads');
            $table->longtext('exception');
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
