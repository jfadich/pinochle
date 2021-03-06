<?php

namespace jfadich\Pinochle\Contracts;

use jfadich\Pinochle\AutoPlayer;
use jfadich\Pinochle\Cards\Card;

interface Round
{
    const PHASE_SEATING = 'seating';
    const PHASE_DEALING = 'dealing';
    const PHASE_BIDDING = 'bidding';
    const PHASE_CALLING = 'calling';
    const PHASE_PASSING = 'passing';
    const PHASE_MELDING = 'melding';
    const PHASE_PLAYING = 'playing';
    const PHASE_COMPLETE = 'complete';

    /**
     * @param Seat $seat
     * @param array $cards
     * @return Hand
     */
    public function addHand(Seat $seat, array $cards) : Hand;

    /**
     * @return array
     */
    public function getHands() : array;

    public function getHandForSeat(Seat $seat) : Hand;

    /**
     * @param Card $trump
     * @return mixed
     */
    public function setTrump(Card $trump);

    public function getTrump() : int;

    /**
     * @param string $phase
     * @return Round
     */
    public function setPhase($phase);

    public function setLeadSeat(Seat $seat);

    public function getLeadPosition(): int;

    /**
     * @return string
     */
    public function getPhase();

    public function addMeld(Seat $seat, array $meld);

    public function setMeldSeen(Seat $seat);

    public function getMeldedSeats() : array;

    /**
     * @param string $phase
     * @return bool
     */
    public function isPhase($phase) : bool;

    /**
     * @return Auction
     */
    public function getAuction() : Auction;

    public function getAutoPlayerForSeat(Seat $seat) : AutoPlayer;
}