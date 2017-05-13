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
            $table->unsignedSmallInteger('number')->default(1);
            $table->string('phase')->default('dealing');
            $table->unsignedTinyInteger('lead_seat')->default(0);
            $table->unsignedInteger('game_id');
            $table->unsignedInteger('trump')->nullable();
            $table->integer('score_team_0')->default(0);
            $table->integer('score_team_1')->default(0);
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
