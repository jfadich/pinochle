<?php

namespace App\Pinochle\Contracts;


interface Game
{
    /**
     * @param array $game
     * @return Game
     */
    public static function make($game);

    /**
     * @return Round
     */
    public function getCurrentRound();

    /**
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection
     */
    public function getRounds();

    /**
     * @param array $player
     * @return Player
     */
    public function addPlayer($player);

    /**
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection
     */
    public function getPlayers();

    /**
     * @return Round
     */
    public function nextRound();
}