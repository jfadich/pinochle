<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHandTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hands', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('player_id');
            $table->unsignedInteger('round_id');
            $table->string('dealt');
            $table->string('current');
            $table->text('analysis')->nullable();
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('round_id')->references('id')->on('rounds')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hands');
    }
}
