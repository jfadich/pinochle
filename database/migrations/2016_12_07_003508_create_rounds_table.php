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
            $table->unsignedSmallInteger('round_number')->default(1);
            $table->unsignedTinyInteger('phase')->default(0);
            $table->unsignedTinyInteger('lead_seat')->default(0);
            $table->unsignedTinyInteger('active_seat')->default(0);
            $table->unsignedInteger('game_id');
            $table->text('buy')->nullable();
            $table->text('auction')->nullable();
            $table->text('hands')->nullable();
            $table->text('meld')->nullable();
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
