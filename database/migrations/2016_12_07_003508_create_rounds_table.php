<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('round_number');
            $table->string('phase')->default('dealing');
            $table->unsignedInteger('lead_seat');
            $table->unsignedInteger('active_seat');
            $table->unsignedInteger('game_id');
            $table->text('winning_bid');
            $table->text('auction');
            $table->text('hands');
            $table->text('meld');
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('games')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rounds');
    }
}
