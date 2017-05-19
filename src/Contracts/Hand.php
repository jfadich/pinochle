<?php

namespace jfadich\Pinochle\Contracts;

use jfadich\Pinochle\HandAnalyser;

interface Hand
{
    public function getCurrentCards() : array;

    public function getDealtCards() : array;

    public function getAnalysis() : HandAnalyser;
}