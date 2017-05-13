<?php

namespace App\Pinochle\Contracts;


interface Player
{
    public function getName();

    /**
     * @return bool
     */
    public function isAuto();
}