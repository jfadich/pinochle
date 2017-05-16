<?php

namespace jfadich\Pinochle\Contracts;


interface Player
{
    public function getName() : string;

    /**
     * @return bool
     */
    public function isAuto() : bool;
}