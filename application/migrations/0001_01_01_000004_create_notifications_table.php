<?php

defined('DS') or exit('No direct access.');

class Create_Notifications_Table
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function ($table) {
            $table->string('id', 36)->primary();
            $table->string('type', 191);
            $table->string('notifiable_type', 191);
            $table->string('notifiable_id', 36);
            $table->longtext('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists('notifications');
    }
}
