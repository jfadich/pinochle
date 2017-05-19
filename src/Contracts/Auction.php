<?php

namespace jfadich\Pinochle\Contracts;


interface Auction
{
    /**
     *
     *
     * @param Seat $seat
     * @return bool
     */
    public function seatHasPassed(Seat $seat) : bool;

    public function getPassedSeats() : array;

    /**
     * @return mixed
     */
    public function getCurrentBid() : int;

    public function getBidsForSeat(Seat $seat) : array;

    public function getBids() : array;

    /**
     * @param Seat $seat
     * @param int $bid
     * @return mixed
     */
    public function placeBid(Seat $seat, $bid);

    /**
     * @param Seat $seat
     * @return mixed
     */
    public function pass(Seat $seat);

    /**
     * Save current bid as the buy in and end the auction
     *
     * @return void
     */
    public function closeAuction() : void;
}