<?php

namespace App\Pinochle\Contracts;


interface Game
{
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
     * @return array
     */
    public function getPlayers();

    public function getCurrentPlayer();

    /**
     * @param int $sameTeam
     * @return Player
     */
    public function setNextPlayer(int $sameTeam = 0);

    /**
     * @return int
     */
    public function getNextSeat();

    /**
     * @return Round
     */
    public function nextRound();
}