<?php

namespace jfadich\Pinochle\Contracts;

use jfadich\Pinochle\HandAnalyser;

interface Hand
{
    public function getCurrentCards() : array;

    public function getDealtCards() : array;

    public function addCards(array $cards);

    public function takeCards(array $cards) : array;

    public function getAnalyser() : HandAnalyser;
}