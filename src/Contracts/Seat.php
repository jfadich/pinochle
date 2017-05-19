<?php

namespace jfadich\Pinochle\Contracts;


interface Seat
{
    public function getPosition() : int;

    public function setPlayer(Player $player);

    public function getPlayer() : Player;
}