<?php

namespace jfadich\Pinochle\Contracts;


interface Auction
{
    /**
     *
     *
     * @param Player $player
     * @return bool
     */
    public function playerHasPassed(Player $player) : bool;

    /**
     * @return mixed
     */
    public function getCurrentBid() : int;

    /**
     * @param Player $player
     * @param int $bid
     * @return mixed
     */
    public function placeBid(Player $player, $bid);

    /**
     * @param Player $player
     * @return mixed
     */
    public function pass(Player $player);
}