<?php

namespace App\Pinochle\Contracts;


interface Round
{
    const PHASE_DEALING = 'dealing';
    const PHASE_BIDDING = 'bidding';
    const PHASE_CALLING = 'calling';
    const PHASE_PASSING = 'passing';
    const PHASE_MELDING = 'melding';
    const PHASE_PLAYING = 'playing';
    const PHASE_COMPLETE = 'complete';

    /**
     * @param array $hand
     * @return Hand
     */
    public function addHand($hand);

    /**
     * @return \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection
     */
    public function getHands();

    /**
     * @param string $phase
     * @return Round
     */
    public function setPhase($phase);

    /**
     * @return string
     */
    public function getPhase();

    /**
     * @param string $phase
     * @return bool
     */
    public function isPhase($phase);

    /**
     * @param int $sameTeam
     * @return int
     */
    public function setNextPlayer(int $sameTeam = 0);

    /**
     * @return int
     */
    public function getNextSeat();
}