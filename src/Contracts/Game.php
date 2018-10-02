<?php

namespace jfadich\Pinochle\Contracts;


interface Game
{
    /**
     * Get an array of all rounds.
     *
     * @return array
     */
    public function getRounds() : array;

    /**
     * Get the current round or create the first one if none exist.
     * Return null if game is over.
     *
     * @return Round|null
     */
    public function getCurrentRound() : ?Round;

    /**
     * Create a seat seat for the player at the given position
     *
     * @param int $seat
     * @param Player $player
     * @return Seat|null
     */
    public function seatPlayer(int $seat, Player $player) : ?Seat;

    /**
     * Get the player who is seated at the active seat
     *
     * @return Player|null
     */
    public function getCurrentPlayer() : ?Player;

    public function getActiveSeat() : ?Seat;

    public function getSeatAtPosition(int $position) : ?Seat;

    public function getSeats() : array;

    public function setActiveSeat(Seat $seat);

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

    public function logTurn(Round $round, $action, $seat, $data);
}