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
     * @param int $seat
     * @param array $player
     * @return Player
     */
    public function addPlayer(int $seat, $player);

    /**
     * @return array
     */
    public function getPlayers();

    /**
     * @param int $seat
     * @return mixed
     */
    public function getPlayerAtSeat(int $seat);

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