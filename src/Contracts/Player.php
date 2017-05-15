<?php

namespace jfadich\Pinochle\Contracts;


interface Player
{
    public function getName();

    /**
     * @return bool
     */
    public function isAuto();
}