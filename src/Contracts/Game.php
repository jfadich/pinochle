<?php

namespace jfadich\Pinochle\Contracts;


interface Game
{
    /**
     * Get the current round or create the first one if none exist.
     * Return null if game is over.
     *
     * @return Round|null
     */
    public function getCurrentRound() : ?Round;

    /**
     * Get an array of all rounds.
     *
     * @return array
     */
    public function getRounds() : array;

    /**
     * Create a new player at the given seat.
     *
     * @param int $seat
     * @param array $player
     * @return Player|null
     */
    public function addPlayer(int $seat, array $player) : ?Player;

    /**
     * Get an array of current players
     *
     * @return array
     */
    public function getPlayers() : array;

    /**
     * Get the player who is seated at the active seat
     *
     * @return Player|null
     */
    public function getCurrentPlayer() : ?Player;

    public function getActiveSeat() : ?Seat;

    public function getSeatAtPosition(int $position) : ?Seat;

    /**
     * Set the active seat to the next player. If $sameTeam is 1, set the active seat to the next player on the same
     * team as the current active player.
     *
     * @param int $sameTeam
     * @return Seat
     */
    public function setNextSeat(int $sameTeam = 0) : ?Seat;

    /**
     * Get the next seat without setting it as the active seat.
     *
     * @param int $sameTeam
     * @return Seat
     */
    public function getNextSeat(int $sameTeam = 0) : ?Seat;

    /**
     * Create the next round of the game. Return null if the game is over.
     *
     * @return Round|null
     */
    public function nextRound() : ?Round;

    public function seatIsActive(Seat $seat) : bool;
}