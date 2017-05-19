<?php

namespace jfadich\Pinochle\Contracts;


interface Seat
{
    public function getPosition() : int;

    public function setPlayer(Player $player);

    public function getPlayer() : Player;

    public function getHand() : Hand;

    public function deal(array $cards) : Hand;
}