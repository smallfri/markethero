<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MwPauseGroups extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('mw_group_paused', function (Blueprint $table)
        {

            $table->increments('group_email_id');
            $table->integer('customer_id');
            $table->integer('pause_customer');
            $table->dateTime('lifted');
            $table->string('paused_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::drop('mw_group_paused');

    }
}
